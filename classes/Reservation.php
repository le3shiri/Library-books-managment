<?php
require_once 'config/database.php';

class Reservation {
    private $conn;
    private $table_name = "reservations";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function creerReservation($utilisateur_id, $livre_id) {
        try {
            $this->conn->beginTransaction();

            // Vérifier si le livre est disponible
            $query_check = "SELECT exemplaires_disponibles FROM livres WHERE id = :livre_id AND actif = 1";
            $stmt_check = $this->conn->prepare($query_check);
            $stmt_check->bindParam(':livre_id', $livre_id);
            $stmt_check->execute();
            $livre = $stmt_check->fetch();

            if ($livre && $livre['exemplaires_disponibles'] > 0) {
                throw new Exception("Le livre est actuellement disponible, vous pouvez l'emprunter directement");
            }

            // Vérifier si l'utilisateur n'a pas déjà une réservation active pour ce livre
            $query_existing = "SELECT id FROM " . $this->table_name . " 
                              WHERE utilisateur_id = :utilisateur_id AND livre_id = :livre_id AND statut = 'active'";
            $stmt_existing = $this->conn->prepare($query_existing);
            $stmt_existing->execute([':utilisateur_id' => $utilisateur_id, ':livre_id' => $livre_id]);
            
            if ($stmt_existing->fetch()) {
                throw new Exception("Vous avez déjà une réservation active pour ce livre");
            }

            // Vérifier le statut de l'utilisateur
            $query_user = "SELECT statut FROM utilisateurs WHERE id = :user_id";
            $stmt_user = $this->conn->prepare($query_user);
            $stmt_user->bindParam(':user_id', $utilisateur_id);
            $stmt_user->execute();
            $utilisateur = $stmt_user->fetch();

            if (!$utilisateur || $utilisateur['statut'] !== 'actif') {
                throw new Exception("Utilisateur non autorisé à faire des réservations");
            }

            // Générer le numéro de réservation
            $numero_reservation = 'RES' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);

            // Créer la réservation
            $duree_reservation = getParametre('duree_reservation', 7);
            $date_expiration = date('Y-m-d', strtotime("+{$duree_reservation} days"));
            
            $query = "INSERT INTO " . $this->table_name . " 
                      (numero_reservation, utilisateur_id, livre_id, date_expiration) 
                      VALUES (:numero, :utilisateur_id, :livre_id, :date_expiration)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':numero' => $numero_reservation,
                ':utilisateur_id' => $utilisateur_id,
                ':livre_id' => $livre_id,
                ':date_expiration' => $date_expiration
            ]);

            $reservation_id = $this->conn->lastInsertId();

            // Mettre à jour le nombre de réservations du livre
            $query_update = "UPDATE livres SET exemplaires_reserves = exemplaires_reserves + 1 WHERE id = :livre_id";
            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->bindParam(':livre_id', $livre_id);
            $stmt_update->execute();

            $this->conn->commit();
            return $reservation_id;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function listerReservationsActives() {
        $query = "SELECT r.*, u.nom, u.prenom, u.email, u.numero_carte, 
                         l.titre, l.sous_titre,
                         a.nom as auteur_nom, a.prenom as auteur_prenom,
                         DATEDIFF(r.date_expiration, CURRENT_DATE) as jours_restants
                  FROM " . $this->table_name . " r
                  JOIN utilisateurs u ON r.utilisateur_id = u.id
                  JOIN livres l ON r.livre_id = l.id
                  LEFT JOIN auteurs a ON l.auteur_principal_id = a.id
                  WHERE r.statut = 'active'
                  ORDER BY r.date_reservation";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function listerReservationsUtilisateur($utilisateur_id) {
        $query = "SELECT r.*, l.titre, l.sous_titre,
                         a.nom as auteur_nom, a.prenom as auteur_prenom
                  FROM " . $this->table_name . " r
                  JOIN livres l ON r.livre_id = l.id
                  LEFT JOIN auteurs a ON l.auteur_principal_id = a.id
                  WHERE r.utilisateur_id = :utilisateur_id
                  ORDER BY r.date_reservation DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':utilisateur_id', $utilisateur_id);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function annulerReservation($reservation_id, $admin_id = null) {
        try {
            $this->conn->beginTransaction();

            // Obtenir les informations de la réservation
            $query_res = "SELECT * FROM " . $this->table_name . " WHERE id = :id AND statut = 'active'";
            $stmt_res = $this->conn->prepare($query_res);
            $stmt_res->bindParam(':id', $reservation_id);
            $stmt_res->execute();
            $reservation = $stmt_res->fetch();

            if (!$reservation) {
                throw new Exception("Réservation non trouvée ou déjà traitée");
            }

            // Annuler la réservation
            $query = "UPDATE " . $this->table_name . " SET statut = 'annulee' WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $reservation_id);
            $stmt->execute();

            // Mettre à jour le nombre de réservations du livre
            $query_update = "UPDATE livres SET exemplaires_reserves = GREATEST(0, exemplaires_reserves - 1) WHERE id = :livre_id";
            $stmt_update = $this->conn->prepare($query_update);
            $stmt_update->bindParam(':livre_id', $reservation['livre_id']);
            $stmt_update->execute();

            if ($admin_id) {
                logActivity($admin_id, 'CANCEL_RESERVATION', 'reservations', $reservation_id);
            }

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function satisfaireReservation($reservation_id, $admin_id) {
        try {
            $this->conn->beginTransaction();

            // Marquer la réservation comme satisfaite
            $query = "UPDATE " . $this->table_name . " 
                      SET statut = 'satisfaite', date_notification = CURRENT_DATE 
                      WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $reservation_id);
            $stmt->execute();

            // Mettre à jour le nombre de réservations du livre
            $query_res = "SELECT livre_id FROM " . $this->table_name . " WHERE id = :id";
            $stmt_res = $this->conn->prepare($query_res);
            $stmt_res->bindParam(':id', $reservation_id);
            $stmt_res->execute();
            $reservation = $stmt_res->fetch();

            if ($reservation) {
                $query_update = "UPDATE livres SET exemplaires_reserves = GREATEST(0, exemplaires_reserves - 1) WHERE id = :livre_id";
                $stmt_update = $this->conn->prepare($query_update);
                $stmt_update->bindParam(':livre_id', $reservation['livre_id']);
                $stmt_update->execute();
            }

            logActivity($admin_id, 'SATISFY_RESERVATION', 'reservations', $reservation_id);

            $this->conn->commit();
            return true;

        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function getStatistiques() {
        $stats = [];
        
        // Réservations actives
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE statut = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['reservations_actives'] = $stmt->fetch()['total'];
        
        // Réservations qui expirent bientôt (dans les 2 prochains jours)
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE statut = 'active' AND date_expiration <= DATE_ADD(CURRENT_DATE, INTERVAL 2 DAY)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['reservations_expirent_bientot'] = $stmt->fetch()['total'];
        
        // Réservations ce mois
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                  WHERE MONTH(date_reservation) = MONTH(CURRENT_DATE) AND YEAR(date_reservation) = YEAR(CURRENT_DATE)";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['reservations_ce_mois'] = $stmt->fetch()['total'];
        
        return $stats;
    }

    public function expirerReservations() {
        try {
            $this->conn->beginTransaction();
            
            // Marquer comme expirées les réservations dont la date d'expiration est dépassée
            $query = "UPDATE " . $this->table_name . " 
                      SET statut = 'expiree' 
                      WHERE statut = 'active' AND date_expiration < CURRENT_DATE";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            $reservations_expirees = $stmt->rowCount();
            
            // Mettre à jour le nombre de réservations des livres concernés
            if ($reservations_expirees > 0) {
                $query_update = "UPDATE livres l 
                                SET exemplaires_reserves = (
                                    SELECT COUNT(*) 
                                    FROM reservations r 
                                    WHERE r.livre_id = l.id AND r.statut = 'active'
                                )";
                $stmt_update = $this->conn->prepare($query_update);
                $stmt_update->execute();
            }
            
            $this->conn->commit();
            return $reservations_expirees;
            
        } catch (Exception $e) {
            $this->conn->rollBack();
            throw $e;
        }
    }

    public function obtenirParId($id) {
        $query = "SELECT r.*, u.nom, u.prenom, u.email, u.numero_carte,
                         l.titre, l.sous_titre,
                         a.nom as auteur_nom, a.prenom as auteur_prenom
                  FROM " . $this->table_name . " r
                  JOIN utilisateurs u ON r.utilisateur_id = u.id
                  JOIN livres l ON r.livre_id = l.id
                  LEFT JOIN auteurs a ON l.auteur_principal_id = a.id
                  WHERE r.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }
}
?>
