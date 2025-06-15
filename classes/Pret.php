<?php
require_once 'config/database.php';

class Pret {
    private $conn;
    private $table_name = "prets";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function creerPret($utilisateur_id, $livre_id, $admin_id, $duree_jours = null) {
        try {
            $this->conn->beginTransaction();

            // Vérifier la disponibilité du livre
            $query_check = "SELECT exemplaires_disponibles FROM livres WHERE id = :livre_id AND actif = 1";
            $stmt_check = $this->conn->prepare($query_check);
            $stmt_check->bindParam(':livre_id', $livre_id);
            $stmt_check->execute();
            $livre = $stmt_check->fetch();

            if (!$livre || $livre['exemplaires_disponibles'] <= 0) {
                throw new Exception("Livre non disponible");
            }

            // Vérifier les limites de prêt de l'utilisateur
            $query_user = "SELECT type_abonnement, limite_prets, statut FROM utilisateurs WHERE id = :user_id";
            $stmt_user = $this->conn->prepare($query_user);
            $stmt_user->bindParam(':user_id', $utilisateur_id);
            $stmt_user->execute();
            $utilisateur = $stmt_user->fetch();

            if (!$utilisateur || $utilisateur['statut'] !== 'actif') {
                throw new Exception("Utilisateur non autorisé à emprunter");
            }

            // Vérifier le nombre de prêts en cours
            $query_count = "SELECT COUNT(*) as count FROM " . $this->table_name . " 
                           WHERE utilisateur_id = :user_id AND statut = 'en_cours'";
            $stmt_count = $this->conn->prepare($query_count);
            $stmt_count->bindParam(':user_id', $utilisateur_id);
            $stmt_count->execute();
            $count_prets = $stmt_count->fetch()['count'];

            if ($count_prets >= $utilisateur['limite_prets']) {
                throw new Exception("Limite de prêts atteinte ({$utilisateur['limite_prets']} maximum)");
            }

            // Vérifier les pénalités impayées
            $query_penalites = "SELECT SUM(montant) as total FROM penalites 
                               WHERE utilisateur_id = :user_id AND statut = 'impayee'";
            $stmt_penalites = $this->conn->prepare($query_penalites);
            $stmt_penalites->bindParam(':user_id', $utilisateur_id);
            $stmt_penalites->execute();
            $penalites = $stmt_penalites->fetch()['total'] ?? 0;

            if ($penalites > 0) {
                throw new Exception("Pénalités impayées: " . number_format($penalites, 2) . "€");
            }

            // Déterminer la durée du prêt
            if (!$duree_jours) {
                $duree_jours = $utilisateur['type_abonnement'] === 'premium' ? 
                              getParametre('duree_pret_premium', 21) : 
                              getParametre('duree_pret_standard', 14);
            }

            // Générer le numéro de prêt
            $numero_pret = 'PRET' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Créer le prêt
            $date_retour = date('Y-m-d', strtotime("+{$duree_jours} days"));
            $query = "INSERT INTO " . $this->table_name . " 
                      (numero_pret, utilisateur_id, livre_id, admin_id, date_retour_prevue) 
                      VALUES (:numero, :utilisateur_id, :livre_id, :admin_id, :date_retour)";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute([
                ':numero' => $numero_pret,
                ':utilisateur_id' => $utilisateur_id,
                ':livre_id' => $livre_id,
                ':admin_id' => $admin_id,
                ':date_retour' => $date_retour
            ]);

            $pret_id = $this->conn->lastInsertId();

            // Mettre à jour la disponibilité
            $query_update = "UPDATE livres SET exemplaires_disponibles = exemplaires_disponibles - 1 WHERE id = :livre_id";
            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->bindParam(':livre_id', $livre_id);
            $stmt_update->execute();

            // Logger l'activité
            logActivity($admin_id, 'CREATE_LOAN', 'prets', $pret_id, [
                'utilisateur_id' => $utilisateur_id,
                'livre_id' => $livre_id,
                'numero_pret' => $numero_pret
            ]);

            $this->conn->commit();
            return $pret_id;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function retournerLivre($pret_id, $admin_id, $notes = null) {
        try {
            $this->conn->beginTransaction();

            // Obtenir les informations du prêt
            $query_pret = "SELECT * FROM " . $this->table_name . " WHERE id = :pret_id AND statut = 'en_cours'";
            $stmt_pret = $this->conn->prepare($query_pret);
            $stmt_pret->bindParam(':pret_id', $pret_id);
            $stmt_pret->execute();
            $pret = $stmt_pret->fetch();

            if (!$pret) {
                throw new Exception("Prêt non trouvé ou déjà retourné");
            }

            // Marquer comme retourné
            $query_update = "UPDATE " . $this->table_name . " 
                            SET date_retour_effective = CURRENT_DATE, 
                                statut = 'retourne',
                                notes = :notes
                            WHERE id = :pret_id";
            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->execute([
                ':pret_id' => $pret_id,
                ':notes' => $notes
            ]);

            // Remettre le livre en disponibilité
            $query_livre = "UPDATE livres SET exemplaires_disponibles = exemplaires_disponibles + 1 WHERE id = :livre_id";
            $stmt_livre = $this->conn->prepare($query_livre);
            $stmt_livre->bindParam(':livre_id', $pret['livre_id']);
            $stmt_livre->execute();

            // Vérifier si retard et créer pénalité
            $date_retour_prevue = new DateTime($pret['date_retour_prevue']);
            $date_actuelle = new DateTime();
            
            if ($date_actuelle > $date_retour_prevue) {
                $jours_retard = $date_actuelle->diff($date_retour_prevue)->days;
                $montant_penalite = $jours_retard * getParametre('penalite_retard_jour', 0.50);

                $numero_penalite = 'PEN' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

                $query_penalite = "INSERT INTO penalites (numero_penalite, utilisateur_id, pret_id, type_penalite, montant, description, admin_id) 
                                  VALUES (:numero, :utilisateur_id, :pret_id, 'retard', :montant, :description, :admin_id)";
                $stmt_penalite = $this->conn->prepare($query_penalite);
                $stmt_penalite->execute([
                    ':numero' => $numero_penalite,
                    ':utilisateur_id' => $pret['utilisateur_id'],
                    ':pret_id' => $pret_id,
                    ':montant' => $montant_penalite,
                    ':description' => "Retard de {$jours_retard} jour(s)",
                    ':admin_id' => $admin_id
                ]);

                // Créer une notification
                $query_notif = "INSERT INTO notifications (utilisateur_id, type, titre, message) 
                               VALUES (:user_id, 'penalite', 'Pénalité de retard', :message)";
                $stmt_notif = $this->conn->prepare($query_notif);
                $stmt_notif->execute([
                    ':user_id' => $pret['utilisateur_id'],
                    ':message' => "Vous avez une pénalité de " . number_format($montant_penalite, 2) . "€ pour le retard du livre."
                ]);
            }

            // Vérifier s'il y a des réservations en attente pour ce livre
            $query_reservation = "SELECT * FROM reservations 
                                 WHERE livre_id = :livre_id AND statut = 'active' 
                                 ORDER BY date_reservation ASC LIMIT 1";
            $stmt_reservation = $this->conn->prepare($query_reservation);
            $stmt_reservation->bindParam(':livre_id', $pret['livre_id']);
            $stmt_reservation->execute();
            $reservation = $stmt_reservation->fetch();

            if ($reservation) {
                // Notifier l'utilisateur que son livre réservé est disponible
                $query_notif_res = "INSERT INTO notifications (utilisateur_id, type, titre, message) 
                                   VALUES (:user_id, 'reservation_disponible', 'Livre disponible', :message)";
                $stmt_notif_res = $this->conn->prepare($query_notif_res);
                $stmt_notif_res->execute([
                    ':user_id' => $reservation['utilisateur_id'],
                    ':message' => "Le livre que vous avez réservé est maintenant disponible. Vous avez 7 jours pour venir le récupérer."
                ]);

                // Mettre à jour la réservation
                $query_update_res = "UPDATE reservations SET statut = 'satisfaite', date_notification = CURRENT_DATE 
                                    WHERE id = :res_id";
                $stmt_update_res = $this->conn->prepare($query_update_res);
                $stmt_update_res->bindParam(':res_id', $reservation['id']);
                $stmt_update_res->execute();
            }

            logActivity($admin_id, 'RETURN_BOOK', 'prets', $pret_id, [
                'utilisateur_id' => $pret['utilisateur_id'],
                'livre_id' => $pret['livre_id']
            ]);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function renouvelerPret($pret_id, $admin_id) {
        try {
            $this->conn->beginTransaction();

            // Obtenir les informations du prêt
            $query_pret = "SELECT p.*, u.type_abonnement FROM " . $this->table_name . " p
                          JOIN utilisateurs u ON p.utilisateur_id = u.id
                          WHERE p.id = :pret_id AND p.statut = 'en_cours'";
            $stmt_pret = $this->conn->prepare($query_pret);
            $stmt_pret->bindParam(':pret_id', $pret_id);
            $stmt_pret->execute();
            $pret = $stmt_pret->fetch();

            if (!$pret) {
                throw new Exception("Prêt non trouvé ou déjà retourné");
            }

            if ($pret['nombre_renouvellements'] >= $pret['max_renouvellements']) {
                throw new Exception("Nombre maximum de renouvellements atteint");
            }

            // Vérifier s'il y a des réservations pour ce livre
            $query_res = "SELECT COUNT(*) as count FROM reservations 
                         WHERE livre_id = :livre_id AND statut = 'active'";
            $stmt_res = $this->conn->prepare($query_res);
            $stmt_res->bindParam(':livre_id', $pret['livre_id']);
            $stmt_res->execute();
            $reservations_count = $stmt_res->fetch()['count'];

            if ($reservations_count > 0) {
                throw new Exception("Impossible de renouveler: livre réservé par d'autres utilisateurs");
            }

            // Calculer la nouvelle date de retour
            $duree_jours = $pret['type_abonnement'] === 'premium' ? 
                          getParametre('duree_pret_premium', 21) : 
                          getParametre('duree_pret_standard', 14);
            
            $nouvelle_date = date('Y-m-d', strtotime("+{$duree_jours} days"));

            // Mettre à jour le prêt
            $query_update = "UPDATE " . $this->table_name . " 
                            SET date_retour_prevue = :nouvelle_date,
                                nombre_renouvellements = nombre_renouvellements + 1
                            WHERE id = :pret_id";
            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->execute([
                ':nouvelle_date' => $nouvelle_date,
                ':pret_id' => $pret_id
            ]);

            logActivity($admin_id, 'RENEW_LOAN', 'prets', $pret_id);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function listerPretsActifs($filtres = []) {
        $query = "SELECT p.*, u.nom, u.prenom, u.numero_carte, u.email, l.titre, l.auteur_principal_id,
                         a.nom as auteur_nom, a.prenom as auteur_prenom,
                         CASE 
                             WHEN p.date_retour_prevue < CURRENT_DATE THEN 'en_retard'
                             ELSE p.statut
                         END as statut_reel,
                         DATEDIFF(CURRENT_DATE, p.date_retour_prevue) as jours_retard
                  FROM " . $this->table_name . " p
                  JOIN utilisateurs u ON p.utilisateur_id = u.id
                  JOIN livres l ON p.livre_id = l.id
                  LEFT JOIN auteurs a ON l.auteur_principal_id = a.id
                  WHERE p.statut = 'en_cours'";
        
        $params = [];
        
        if (!empty($filtres['utilisateur_id'])) {
            $query .= " AND p.utilisateur_id = :utilisateur_id";
            $params[':utilisateur_id'] = $filtres['utilisateur_id'];
        }
        
        if (!empty($filtres['en_retard'])) {
            $query .= " AND p.date_retour_prevue < CURRENT_DATE";
        }
        
        $query .= " ORDER BY p.date_retour_prevue";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listerHistoriquePrets($utilisateur_id = null, $limit = 50) {
        $query = "SELECT p.*, u.nom, u.prenom, u.numero_carte, l.titre,
                         a.nom as auteur_nom, a.prenom as auteur_prenom
                  FROM " . $this->table_name . " p
                  JOIN utilisateurs u ON p.utilisateur_id = u.id
                  JOIN livres l ON p.livre_id = l.id
                  LEFT JOIN auteurs a ON l.auteur_principal_id = a.id";
        
        $params = [];
        
        if ($utilisateur_id) {
            $query .= " WHERE p.utilisateur_id = :utilisateur_id";
            $params[':utilisateur_id'] = $utilisateur_id;
        }
        
        $query .= " ORDER BY p.date_pret DESC LIMIT :limit";
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getStatistiques() {
        $stats = [];
        
        // Prêts en cours
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE statut = 'en_cours'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['prets_en_cours'] = $stmt->fetch()['total'];
        
        // Prêts en retard
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE statut = 'en_cours' AND date_retour_prevue < CURRENT_DATE";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['prets_en_retard'] = $stmt->fetch()['total'];
        
        // Prêts ce mois
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE MONTH(date_pret) = MONTH(CURRENT_DATE) AND YEAR(date_pret) = YEAR(CURRENT_DATE)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['prets_ce_mois'] = $stmt->fetch()['total'];
        
        return $stats;
    }
}
?>
