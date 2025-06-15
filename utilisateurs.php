<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'classes/Utilisateur.php';

$auth = requireAuth();

$database = new Database();
$conn = $database->getConnection();

// Traitement des actions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'ajouter':
                    // Générer un numéro de carte unique
                    $numero_carte = 'BIB' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    $_POST['numero_carte'] = $numero_carte;
                    $_POST['date_expiration'] = date('Y-m-d', strtotime('+1 year'));
                    
                    $query = "INSERT INTO utilisateurs 
                              (numero_carte, nom, prenom, email, telephone, adresse, date_naissance, 
                               date_expiration, type_abonnement, limite_prets) 
                              VALUES (:numero_carte, :nom, :prenom, :email, :telephone, :adresse, 
                                      :date_naissance, :date_expiration, :type_abonnement, :limite_prets)";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        ':numero_carte' => $_POST['numero_carte'],
                        ':nom' => $_POST['nom'],
                        ':prenom' => $_POST['prenom'],
                        ':email' => $_POST['email'],
                        ':telephone' => $_POST['telephone'] ?? null,
                        ':adresse' => $_POST['adresse'] ?? null,
                        ':date_naissance' => $_POST['date_naissance'] ?? null,
                        ':date_expiration' => $_POST['date_expiration'],
                        ':type_abonnement' => $_POST['type_abonnement'],
                        ':limite_prets' => $_POST['limite_prets']
                    ]);
                    
                    logActivity($auth->getCurrentAdminId(), 'CREATE_USER', 'utilisateurs', $conn->lastInsertId());
                    $message = "Utilisateur ajouté avec succès! Numéro de carte: " . $numero_carte;
                    break;
                
                case 'modifier':
                    $query = "UPDATE utilisateurs SET 
                              nom = :nom, prenom = :prenom, email = :email, telephone = :telephone,
                              adresse = :adresse, date_naissance = :date_naissance, 
                              type_abonnement = :type_abonnement, limite_prets = :limite_prets,
                              statut = :statut
                              WHERE id = :id";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        ':id' => $_POST['id'],
                        ':nom' => $_POST['nom'],
                        ':prenom' => $_POST['prenom'],
                        ':email' => $_POST['email'],
                        ':telephone' => $_POST['telephone'] ?? null,
                        ':adresse' => $_POST['adresse'] ?? null,
                        ':date_naissance' => $_POST['date_naissance'] ?? null,
                        ':type_abonnement' => $_POST['type_abonnement'],
                        ':limite_prets' => $_POST['limite_prets'],
                        ':statut' => $_POST['statut']
                    ]);
                    
                    logActivity($auth->getCurrentAdminId(), 'UPDATE_USER', 'utilisateurs', $_POST['id']);
                    $message = "Utilisateur modifié avec succès!";
                    break;
                
                case 'suspendre':
                    $query = "UPDATE utilisateurs SET statut = 'suspendu' WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([':id' => $_POST['id']]);
                    
                    logActivity($auth->getCurrentAdminId(), 'SUSPEND_USER', 'utilisateurs', $_POST['id']);
                    $message = "Utilisateur suspendu avec succès!";
                    break;
                
                case 'reactiver':
                    $query = "UPDATE utilisateurs SET statut = 'actif' WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([':id' => $_POST['id']]);
                    
                    logActivity($auth->getCurrentAdminId(), 'REACTIVATE_USER', 'utilisateurs', $_POST['id']);
                    $message = "Utilisateur réactivé avec succès!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupération des utilisateurs avec filtres
$terme_recherche = $_GET['recherche'] ?? '';
$filtre_statut = $_GET['statut'] ?? '';
$filtre_type = $_GET['type'] ?? '';

$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM prets WHERE utilisateur_id = u.id AND statut = 'en_cours') as prets_en_cours,
          (SELECT SUM(montant) FROM penalites WHERE utilisateur_id = u.id AND statut = 'impayee') as penalites_impayees
          FROM utilisateurs u WHERE 1=1";

$params = [];

if ($terme_recherche) {
    $query .= " AND (u.nom LIKE :terme OR u.prenom LIKE :terme OR u.email LIKE :terme OR u.numero_carte LIKE :terme)";
    $params[':terme'] = "%{$terme_recherche}%";
}

if ($filtre_statut) {
    $query .= " AND u.statut = :statut";
    $params[':statut'] = $filtre_statut;
}

if ($filtre_type) {
    $query .= " AND u.type_abonnement = :type";
    $params[':type'] = $filtre_type;
}

$query .= " ORDER BY u.nom, u.prenom";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$utilisateurs = $stmt->fetchAll();

// Utilisateur à modifier si demandé
$utilisateur_modifier = null;
if (isset($_GET['modifier']) && $_GET['modifier']) {
    $query_mod = "SELECT * FROM utilisateurs WHERE id = :id";
    $stmt_mod = $conn->prepare($query_mod);
    $stmt_mod->bindParam(':id', $_GET['modifier']);
    $stmt_mod->execute();
    $utilisateur_modifier = $stmt_mod->fetch();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Utilisateurs - Bibliothèque</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-book"></i> <?= getParametre('nom_bibliotheque', 'Bibliothèque') ?>
            </a>
            <div class="navbar-nav me-auto">
                <a class="nav-link" href="index.php">Tableau de bord</a>
                <a class="nav-link" href="livres.php">Livres</a>
                <a class="nav-link active" href="utilisateurs.php">Utilisateurs</a>
                <a class="nav-link" href="prets.php">Prêts</a>
                <a class="nav-link" href="reservations.php">Réservations</a>
                <a class="nav-link" href="penalites.php">Pénalités</a>
            </div>
            <div class="navbar-nav">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user"></i> <?= $_SESSION['admin_nom'] ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-users"></i> Gestion des Utilisateurs</h2>
            <div class="btn-group">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjouter">
                    <i class="fas fa-user-plus"></i> Nouvel utilisateur
                </button>
                <a href="export-utilisateurs.php" class="btn btn-info">
                    <i class="fas fa-download"></i> Exporter
                </a>
            </div>
        </div>

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="recherche" 
                               placeholder="Rechercher par nom, email, numéro de carte..." 
                               value="<?= htmlspecialchars($terme_recherche) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="statut" class="form-select">
                            <option value="">Tous statuts</option>
                            <option value="actif" <?= $filtre_statut == 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="suspendu" <?= $filtre_statut == 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                            <option value="expire" <?= $filtre_statut == 'expire' ? 'selected' : '' ?>>Expiré</option>
                            <option value="inactif" <?= $filtre_statut == 'inactif' ? 'selected' : '' ?>>Inactif</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="type" class="form-select">
                            <option value="">Tous types</option>
                            <option value="standard" <?= $filtre_type == 'standard' ? 'selected' : '' ?>>Standard</option>
                            <option value="premium" <?= $filtre_type == 'premium' ? 'selected' : '' ?>>Premium</option>
                            <option value="etudiant" <?= $filtre_type == 'etudiant' ? 'selected' : '' ?>>Étudiant</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des utilisateurs -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Liste des Utilisateurs (<?= count($utilisateurs) ?>)</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Utilisateur</th>
                                <th>Contact</th>
                                <th>Abonnement</th>
                                <th>Prêts</th>
                                <th>Pénalités</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($utilisateurs as $user): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="user-avatar me-3">
                                                <?= strtoupper(substr($user['prenom'], 0, 1) . substr($user['nom'], 0, 1)) ?>
                                            </div>
                                            <div>
                                                <strong><?= htmlspecialchars($user['prenom'] . ' ' . $user['nom']) ?></strong>
                                                <br><small class="text-muted">Carte: <?= htmlspecialchars($user['numero_carte']) ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($user['email']) ?>
                                        <?php if ($user['telephone']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($user['telephone']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $user['type_abonnement'] == 'premium' ? 'warning' : ($user['type_abonnement'] == 'etudiant' ? 'info' : 'secondary') ?>">
                                            <?= ucfirst($user['type_abonnement']) ?>
                                        </span>
                                        <br><small class="text-muted">Limite: <?= $user['limite_prets'] ?> prêts</small>
                                        <br><small class="text-muted">Expire: <?= date('d/m/Y', strtotime($user['date_expiration'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $user['prets_en_cours'] > 0 ? 'primary' : 'secondary' ?>">
                                            <?= $user['prets_en_cours'] ?> en cours
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($user['penalites_impayees'] > 0): ?>
                                            <span class="badge bg-danger">
                                                <?= number_format($user['penalites_impayees'], 2) ?>€
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Aucune</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $user['statut'] == 'actif' ? 'success' : ($user['statut'] == 'suspendu' ? 'danger' : 'secondary') ?>">
                                            <?= ucfirst($user['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="utilisateur-details.php?id=<?= $user['id'] ?>" class="btn btn-outline-info" title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?modifier=<?= $user['id'] ?>" class="btn btn-outline-warning" title="Modifier">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <?php if ($user['statut'] == 'actif'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="suspendre">
                                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-danger btn-sm" 
                                                            onclick="return confirm('Suspendre cet utilisateur ?')" title="Suspendre">
                                                        <i class="fas fa-ban"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($user['statut'] == 'suspendu'): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="reactiver">
                                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-success btn-sm" 
                                                            onclick="return confirm('Réactiver cet utilisateur ?')" title="Réactiver">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (empty($utilisateurs)): ?>
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">Aucun utilisateur trouvé</h4>
                <p class="text-muted">Essayez de modifier vos critères de recherche ou ajoutez un nouvel utilisateur.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Ajouter/Modifier Utilisateur -->
    <div class="modal fade" id="modalAjouter" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <?= $utilisateur_modifier ? 'Modifier l\'utilisateur' : 'Ajouter un utilisateur' ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?= $utilisateur_modifier ? 'modifier' : 'ajouter' ?>">
                        <?php if ($utilisateur_modifier): ?>
                            <input type="hidden" name="id" value="<?= $utilisateur_modifier['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="nom" class="form-label">Nom *</label>
                                <input type="text" class="form-control" name="nom" required
                                       value="<?= $utilisateur_modifier ? htmlspecialchars($utilisateur_modifier['nom']) : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prenom" class="form-label">Prénom *</label>
                                <input type="text" class="form-control" name="prenom" required
                                       value="<?= $utilisateur_modifier ? htmlspecialchars($utilisateur_modifier['prenom']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required
                                       value="<?= $utilisateur_modifier ? htmlspecialchars($utilisateur_modifier['email']) : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="telephone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" name="telephone"
                                       value="<?= $utilisateur_modifier ? htmlspecialchars($utilisateur_modifier['telephone']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="adresse" class="form-label">Adresse</label>
                            <textarea class="form-control" name="adresse" rows="2"><?= $utilisateur_modifier ? htmlspecialchars($utilisateur_modifier['adresse']) : '' ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="date_naissance" class="form-label">Date de naissance</label>
                                <input type="date" class="form-control" name="date_naissance"
                                       value="<?= $utilisateur_modifier ? $utilisateur_modifier['date_naissance'] : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="type_abonnement" class="form-label">Type d'abonnement *</label>
                                <select class="form-select" name="type_abonnement" required>
                                    <option value="standard" <?= ($utilisateur_modifier && $utilisateur_modifier['type_abonnement'] == 'standard') ? 'selected' : '' ?>>Standard</option>
                                    <option value="premium" <?= ($utilisateur_modifier && $utilisateur_modifier['type_abonnement'] == 'premium') ? 'selected' : '' ?>>Premium</option>
                                    <option value="etudiant" <?= ($utilisateur_modifier && $utilisateur_modifier['type_abonnement'] == 'etudiant') ? 'selected' : '' ?>>Étudiant</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="limite_prets" class="form-label">Limite de prêts *</label>
                                <input type="number" class="form-control" name="limite_prets" min="1" max="10" required
                                       value="<?= $utilisateur_modifier ? $utilisateur_modifier['limite_prets'] : '3' ?>">
                            </div>
                        </div>
                        
                        <?php if ($utilisateur_modifier): ?>
                            <div class="mb-3">
                                <label for="statut" class="form-label">Statut</label>
                                <select class="form-select" name="statut">
                                    <option value="actif" <?= $utilisateur_modifier['statut'] == 'actif' ? 'selected' : '' ?>>Actif</option>
                                    <option value="suspendu" <?= $utilisateur_modifier['statut'] == 'suspendu' ? 'selected' : '' ?>>Suspendu</option>
                                    <option value="expire" <?= $utilisateur_modifier['statut'] == 'expire' ? 'selected' : '' ?>>Expiré</option>
                                    <option value="inactif" <?= $utilisateur_modifier['statut'] == 'inactif' ? 'selected' : '' ?>>Inactif</option>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <?= $utilisateur_modifier ? 'Modifier' : 'Ajouter' ?> l'utilisateur
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Ouvrir le modal de modification si demandé
        <?php if ($utilisateur_modifier): ?>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('modalAjouter')).show();
            });
        <?php endif; ?>
    </script>
</body>
</html>
