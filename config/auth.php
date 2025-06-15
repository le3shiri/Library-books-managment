<?php
session_start();

class Auth {
    private $conn;
    
    public function __construct() {
        $database = new Database();
        $this->conn = $database->getConnection();
    }
    
    public function login($username, $password) {
        try {
            $query = "SELECT * FROM admins WHERE username = :username AND statut = 'actif'";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            
            $admin = $stmt->fetch();
            
            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_username'] = $admin['username'];
                $_SESSION['admin_nom'] = $admin['prenom'] . ' ' . $admin['nom'];
                $_SESSION['admin_role'] = $admin['role'];
                $_SESSION['logged_in'] = true;
                
                // Mettre à jour la dernière connexion
                $update_query = "UPDATE admins SET derniere_connexion = NOW() WHERE id = :id";
                $update_stmt = $this->conn->prepare($update_query);
                $update_stmt->bindParam(':id', $admin['id']);
                $update_stmt->execute();
                
                logActivity($admin['id'], 'LOGIN', 'admins', $admin['id']);
                
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Erreur de connexion: " . $e->getMessage());
            return false;
        }
    }
    
    public function logout() {
        if (isset($_SESSION['admin_id'])) {
            logActivity($_SESSION['admin_id'], 'LOGOUT', 'admins', $_SESSION['admin_id']);
        }
        
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public function requireRole($role) {
        $this->requireLogin();
        if ($_SESSION['admin_role'] !== $role && $_SESSION['admin_role'] !== 'admin') {
            header('Location: index.php?error=access_denied');
            exit();
        }
    }
    
    public function getCurrentAdminId() {
        return $_SESSION['admin_id'] ?? null;
    }
    
    public function getCurrentAdminRole() {
        return $_SESSION['admin_role'] ?? null;
    }
}

// Fonction utilitaire pour vérifier l'authentification
function requireAuth() {
    $auth = new Auth();
    $auth->requireLogin();
    return $auth;
}
?>
