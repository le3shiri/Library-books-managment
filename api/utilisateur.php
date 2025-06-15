<?php
require_once '../config/database.php';
require_once '../config/auth.php';

header('Content-Type: application/json');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non autorisé']);
    exit();
}

if (isset($_GET['id'])) {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        $query = "SELECT id, numero_carte, nom, prenom, email, telephone, statut, type_abonnement, limite_prets
                  FROM utilisateurs WHERE id = :id";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':id', $_GET['id']);
        $stmt->execute();
        
        $utilisateur = $stmt->fetch();
        
        if ($utilisateur) {
            // Vérifier les prêts en cours
            $query_prets = "SELECT COUNT(*) as count FROM prets WHERE utilisateur_id = :id AND statut = 'en_cours'";
            $stmt_prets = $conn->prepare($query_prets);
            $stmt_prets->bindParam(':id', $_GET['id']);
            $stmt_prets->execute();
            $prets_count = $stmt_prets->fetch()['count'];
            
            // Vérifier les pénalités
            $query_penalites = "SELECT SUM(montant) as total FROM penalites WHERE utilisateur_id = :id AND statut = 'impayee'";
            $stmt_penalites = $conn->prepare($query_penalites);
            $stmt_penalites->bindParam(':id', $_GET['id']);
            $stmt_penalites->execute();
            $penalites_total = $stmt_penalites->fetch()['total'] ?? 0;
            
            $utilisateur['prets_en_cours'] = $prets_count;
            $utilisateur['penalites_impayees'] = $penalites_total;
            
            echo json_encode(['success' => true, 'utilisateur' => $utilisateur]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Utilisateur non trouvé']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'ID utilisateur requis']);
}
?>
