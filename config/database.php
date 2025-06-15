<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'bibliotheque';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch(PDOException $exception) {
            error_log("Erreur de connexion: " . $exception->getMessage());
            throw new Exception("Erreur de connexion à la base de données");
        }
        return $this->conn;
    }

    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }

    public function commit() {
        return $this->getConnection()->commit();
    }

    public function rollback() {
        return $this->getConnection()->rollback();
    }
}

// Fonction utilitaire pour logger les activités
function logActivity($admin_id, $action, $table, $id_enregistrement = null, $details = null) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "INSERT INTO logs_activite (admin_id, action, table_concernee, id_enregistrement, details, ip_adresse, user_agent) 
                  VALUES (:admin_id, :action, :table_concernee, :id_enregistrement, :details, :ip_adresse, :user_agent)";
        
        $stmt = $conn->prepare($query);
        $stmt->execute([
            ':admin_id' => $admin_id,
            ':action' => $action,
            ':table_concernee' => $table,
            ':id_enregistrement' => $id_enregistrement,
            ':details' => $details ? json_encode($details) : null,
            ':ip_adresse' => $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    } catch (Exception $e) {
        error_log("Erreur lors du logging: " . $e->getMessage());
    }
}

// Fonction pour obtenir un paramètre système
function getParametre($cle, $defaut = null) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT valeur, type FROM parametres WHERE cle_param = :cle";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':cle', $cle);
        $stmt->execute();
        
        $result = $stmt->fetch();
        if ($result) {
            switch ($result['type']) {
                case 'int':
                    return (int)$result['valeur'];
                case 'float':
                    return (float)$result['valeur'];
                case 'boolean':
                    return $result['valeur'] === 'true';
                case 'json':
                    return json_decode($result['valeur'], true);
                default:
                    return $result['valeur'];
            }
        }
        return $defaut;
    } catch (Exception $e) {
        return $defaut;
    }
}
?>
