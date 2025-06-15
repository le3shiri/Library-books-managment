<?php
require_once 'config/database.php';

class Livre {
    private $conn;
    private $table_name = "livres";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function listerTous($limit = null, $offset = 0, $filtres = []) {
        $query = "SELECT l.*, c.nom as categorie_nom, c.couleur as categorie_couleur,
                         a.nom as auteur_nom, a.prenom as auteur_prenom,
                         e.nom as editeur_nom
                  FROM " . $this->table_name . " l 
                  LEFT JOIN categories c ON l.categorie_id = c.id 
                  LEFT JOIN auteurs a ON l.auteur_principal_id = a.id
                  LEFT JOIN editeurs e ON l.editeur_id = e.id
                  WHERE l.actif = 1";
        
        $params = [];
        
        // Appliquer les filtres
        if (!empty($filtres['categorie'])) {
            $query .= " AND l.categorie_id = :categorie";
            $params[':categorie'] = $filtres['categorie'];
        }
        
        if (!empty($filtres['statut'])) {
            switch ($filtres['statut']) {
                case 'disponible':
                    $query .= " AND l.exemplaires_disponibles > 0";
                    break;
                case 'indisponible':
                    $query .= " AND l.exemplaires_disponibles = 0";
                    break;
                case 'reserve':
                    $query .= " AND l.exemplaires_reserves > 0";
                    break;
            }
        }
        
        $query .= " ORDER BY l.titre";
        
        if ($limit) {
            $query .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->conn->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        if ($limit) {
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function rechercher($terme, $filtres = []) {
        $query = "SELECT l.*, c.nom as categorie_nom, c.couleur as categorie_couleur,
                         a.nom as auteur_nom, a.prenom as auteur_prenom,
                         e.nom as editeur_nom
                  FROM " . $this->table_name . " l 
                  LEFT JOIN categories c ON l.categorie_id = c.id 
                  LEFT JOIN auteurs a ON l.auteur_principal_id = a.id
                  LEFT JOIN editeurs e ON l.editeur_id = e.id
                  WHERE l.actif = 1 AND (
                      l.titre LIKE :terme OR 
                      l.isbn LIKE :terme OR 
                      CONCAT(a.prenom, ' ', a.nom) LIKE :terme OR
                      l.description LIKE :terme OR
                      l.mots_cles LIKE :terme
                  )";
        
        $params = [':terme' => "%{$terme}%"];
        
        // Appliquer les filtres additionnels
        if (!empty($filtres['categorie'])) {
            $query .= " AND l.categorie_id = :categorie";
            $params[':categorie'] = $filtres['categorie'];
        }
        
        $query .= " ORDER BY l.titre";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function obtenirParId($id) {
        $query = "SELECT l.*, c.nom as categorie_nom, c.couleur as categorie_couleur,
                         a.nom as auteur_nom, a.prenom as auteur_prenom,
                         e.nom as editeur_nom, e.adresse as editeur_adresse
                  FROM " . $this->table_name . " l 
                  LEFT JOIN categories c ON l.categorie_id = c.id 
                  LEFT JOIN auteurs a ON l.auteur_principal_id = a.id
                  LEFT JOIN editeurs e ON l.editeur_id = e.id
                  WHERE l.id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function ajouter($data, $admin_id) {
        try {
            $this->conn->beginTransaction();
            
            $query = "INSERT INTO " . $this->table_name . " 
                      (isbn, titre, sous_titre, auteur_principal_id, editeur_id, annee_publication, 
                       categorie_id, langue, nombre_pages, format, nombre_exemplaires, 
                       exemplaires_disponibles, description, resume, mots_cles, emplacement, 
                       prix_achat, etat_general) 
                      VALUES (:isbn, :titre, :sous_titre, :auteur_id, :editeur_id, :annee, 
                              :categorie_id, :langue, :pages, :format, :nombre, :disponibles, 
                              :description, :resume, :mots_cles, :emplacement, :prix, :etat)";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':isbn' => $data['isbn'],
                ':titre' => $data['titre'],
                ':sous_titre' => $data['sous_titre'] ?? null,
                ':auteur_id' => $data['auteur_principal_id'] ?? null,
                ':editeur_id' => $data['editeur_id'] ?? null,
                ':annee' => $data['annee_publication'] ?? null,
                ':categorie_id' => $data['categorie_id'],
                ':langue' => $data['langue'] ?? 'Français',
                ':pages' => $data['nombre_pages'] ?? null,
                ':format' => $data['format'] ?? 'broché',
                ':nombre' => $data['nombre_exemplaires'],
                ':disponibles' => $data['nombre_exemplaires'],
                ':description' => $data['description'] ?? null,
                ':resume' => $data['resume'] ?? null,
                ':mots_cles' => $data['mots_cles'] ?? null,
                ':emplacement' => $data['emplacement'] ?? null,
                ':prix' => $data['prix_achat'] ?? null,
                ':etat' => $data['etat_general'] ?? 'bon'
            ]);
            
            $livre_id = $this->conn->lastInsertId();
            
            // Logger l'activité
            logActivity($admin_id, 'CREATE', 'livres', $livre_id, $data);
            
            $this->conn->commit();
            return $livre_id;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function modifier($id, $data, $admin_id) {
        try {
            $this->conn->beginTransaction();
            
            $query = "UPDATE " . $this->table_name . " SET 
                      isbn = :isbn, titre = :titre, sous_titre = :sous_titre,
                      auteur_principal_id = :auteur_id, editeur_id = :editeur_id,
                      annee_publication = :annee, categorie_id = :categorie_id,
                      langue = :langue, nombre_pages = :pages, format = :format,
                      description = :description, resume = :resume, mots_cles = :mots_cles,
                      emplacement = :emplacement, prix_achat = :prix, etat_general = :etat
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':id' => $id,
                ':isbn' => $data['isbn'],
                ':titre' => $data['titre'],
                ':sous_titre' => $data['sous_titre'] ?? null,
                ':auteur_id' => $data['auteur_principal_id'] ?? null,
                ':editeur_id' => $data['editeur_id'] ?? null,
                ':annee' => $data['annee_publication'] ?? null,
                ':categorie_id' => $data['categorie_id'],
                ':langue' => $data['langue'] ?? 'Français',
                ':pages' => $data['nombre_pages'] ?? null,
                ':format' => $data['format'] ?? 'broché',
                ':description' => $data['description'] ?? null,
                ':resume' => $data['resume'] ?? null,
                ':mots_cles' => $data['mots_cles'] ?? null,
                ':emplacement' => $data['emplacement'] ?? null,
                ':prix' => $data['prix_achat'] ?? null,
                ':etat' => $data['etat_general'] ?? 'bon'
            ]);
            
            logActivity($admin_id, 'UPDATE', 'livres', $id, $data);
            
            $this->conn->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function supprimer($id, $admin_id) {
        try {
            // Vérifier s'il y a des prêts en cours
            $query_check = "SELECT COUNT(*) as count FROM prets WHERE livre_id = :id AND statut = 'en_cours'";
            $stmt_check = $this->conn->prepare($query_check);
            $stmt_check->bindParam(':id', $id);
            $stmt_check->execute();
            $result = $stmt_check->fetch();
            
            if ($result['count'] > 0) {
                throw new Exception("Impossible de supprimer ce livre car il y a des prêts en cours");
            }
            
            // Désactiver le livre au lieu de le supprimer
            $query = "UPDATE " . $this->table_name . " SET actif = 0 WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $id);
            $result = $stmt->execute();
            
            logActivity($admin_id, 'DELETE', 'livres', $id);
            
            return $result;
            
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function mettreAJourDisponibilite($livre_id, $changement) {
        $query = "UPDATE " . $this->table_name . " 
                  SET exemplaires_disponibles = exemplaires_disponibles + :changement 
                  WHERE id = :id AND exemplaires_disponibles + :changement >= 0";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':changement' => $changement, 
            ':id' => $livre_id
        ]);
    }

    public function ajouterExemplaires($livre_id, $nombre, $admin_id) {
        try {
            $this->conn->beginTransaction();
            
            $query = "UPDATE " . $this->table_name . " 
                      SET nombre_exemplaires = nombre_exemplaires + :nombre,
                          exemplaires_disponibles = exemplaires_disponibles + :nombre
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $result = $stmt->execute([
                ':nombre' => $nombre,
                ':id' => $livre_id
            ]);
            
            logActivity($admin_id, 'ADD_COPIES', 'livres', $livre_id, ['nombre_ajoute' => $nombre]);
            
            $this->conn->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }

    public function getStatistiques() {
        $stats = [];
        
        // Total des livres
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE actif = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['total_livres'] = $stmt->fetch()['total'];
        
        // Livres disponibles
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE actif = 1 AND exemplaires_disponibles > 0";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['livres_disponibles'] = $stmt->fetch()['total'];
        
        // Livres par catégorie
        $query = "SELECT c.nom, COUNT(l.id) as nombre 
                  FROM categories c 
                  LEFT JOIN " . $this->table_name . " l ON c.id = l.categorie_id AND l.actif = 1
                  GROUP BY c.id, c.nom 
                  ORDER BY nombre DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $stats['par_categorie'] = $stmt->fetchAll();
        
        return $stats;
    }
}
?>
