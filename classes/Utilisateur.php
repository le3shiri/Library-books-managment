<?php
require_once 'config/database.php';

class Utilisateur {
    private $conn;
    private $table_name = "utilisateurs";

    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }

    public function listerTous() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nom, prenom";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function obtenirParId($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function ajouter($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (nom, prenom, email, telephone, adresse, type_utilisateur) 
                  VALUES (:nom, :prenom, :email, :telephone, :adresse, :type)";
        
        $stmt = $this->conn->prepare($query);
        return $stmt->execute([
            ':nom' => $data['nom'],
            ':prenom' => $data['prenom'],
            ':email' => $data['email'],
            ':telephone' => $data['telephone'],
            ':adresse' => $data['adresse'],
            ':type' => $data['type_utilisateur'] ?? 'membre'
        ]);
    }

    public function obtenirPenalitesImpayees($utilisateur_id) {
        $query = "SELECT SUM(montant) as total FROM penalites 
                  WHERE utilisateur_id = :id AND statut = 'impayee'";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $utilisateur_id);
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'] ?? 0;
    }
}
?>
