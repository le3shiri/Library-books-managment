<?php
require_once 'config/database.php';
require_once 'config/auth.php';

$auth = requireAuth();

$database = new Database();
$conn = $database->getConnection();

// Traitement des actions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'payer':
                    $query = "UPDATE penalites SET statut = 'payee', date_paiement = CURRENT_DATE, 
                              mode_paiement = :mode WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        ':mode' => $_POST['mode_paiement'],
                        ':id' => $_POST['penalite_id']
                    ]);
                    
                    logActivity($auth->getCurrentAdminId(), 'PAY_PENALTY', 'penalites', $_POST['penalite_id']);
                    $message = "Pénalité payée avec succès!";
                    break;
                
                case 'annuler':
                    $query = "UPDATE penalites SET statut = 'annulee' WHERE id = :id";
                    $stmt = $conn->prepare($query);
                    $stmt->execute([':id' => $_POST['penalite_id']]);
                    
                    logActivity($auth->getCurrentAdminId(), 'CANCEL_PENALTY', 'penalites', $_POST['penalite_id']);
                    $message = "Pénalité annulée avec succès!";
                    break;
                
                case 'ajouter':
                    $numero_penalite = 'PEN' . date('Y') . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
                    
                    $query = "INSERT INTO penalites 
                              (numero_penalite, utilisateur_id, type_penalite, montant, description, admin_id) 
                              VALUES (:numero, :utilisateur_id, :type, :montant, :description, :admin_id)";
                    
                    $stmt = $conn->prepare($query);
                    $stmt->execute([
                        ':numero' => $numero_penalite,
                        ':utilisateur_id' => $_POST['utilisateur_id'],
                        ':type' => $_POST['type_penalite'],
                        ':montant' => $_POST['montant'],
                        ':description' => $_POST['description'],
                        ':admin_id' => $auth->getCurrentAdminId()
                    ]);
                    
                    logActivity($auth->getCurrentAdminId(), 'CREATE_PENALTY', 'penalites', $conn->lastInsertId());
                    $message = "Pénalité ajoutée avec succès!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupération des pénalités avec filtres
$onglet = $_GET['onglet'] ?? 'impayees';
$terme_recherche = $_GET['recherche'] ?? '';
$filtre_type = $_GET['type'] ?? '';

$query = "SELECT p.*, u.nom, u.prenom, u.email, u.numero_carte, 
                 pr.date_pret, pr.numero_pret, l.titre,
                 a.nom as admin_nom, a.prenom as admin_prenom
          FROM penalites p
          JOIN utilisateurs u ON p.utilisateur_id = u.id
          LEFT JOIN prets pr ON p.pret_id = pr.id
          LEFT JOIN livres l ON pr.livre_id = l.id
          LEFT JOIN admins a ON p.admin_id = a.id
          WHERE 1=1";

$params = [];

// Filtrer par onglet
switch ($onglet) {
    case 'impayees':
        $query .= " AND p.statut = 'impayee'";
        break;
    case 'payees':
        $query .= " AND p.statut = 'payee'";
        break;
    case 'annulees':
        $query .= " AND p.statut = 'annulee'";
        break;
}

// Filtrer par recherche
if ($terme_recherche) {
    $query .= " AND (u.nom LIKE :terme OR u.prenom LIKE :terme OR u.email LIKE :terme OR u.numero_carte LIKE :terme OR p.description LIKE :terme)";
    $params[':terme'] = "%{$terme_recherche}%";
}

// Filtrer par type
if ($filtre_type) {
    $query .= " AND p.type_penalite = :type";
    $params[':type'] = $filtre_type;
}

$query .= " ORDER BY p.date_creation DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
$penalites = $stmt->fetchAll();

// Statistiques
$stats_query = "SELECT 
    COUNT(CASE WHEN statut = 'impayee' THEN 1 END) as impayees_count,
    COUNT(CASE WHEN statut = 'payee' THEN 1 END) as payees_count,
    SUM(CASE WHEN statut = 'impayee' THEN montant ELSE 0 END) as montant_impaye,
    SUM(CASE WHEN statut = 'payee' THEN montant ELSE 0 END) as montant_paye
    FROM penalites";
$stmt_stats = $conn->prepare($stats_query);
$stmt_stats->execute();
$stats = $stmt_stats->fetch();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Pénalités - Bibliothèque</title>
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
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .penalty-unpaid {
            background-color: #fff5f5 !important;
            border-left: 4px solid #dc3545 !important;
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
                <a class="nav-link" href="utilisateurs.php">Utilisateurs</a>
                <a class="nav-link" href="prets.php">Prêts</a>
                <a class="nav-link" href="reservations.php">Réservations</a>
                <a class="nav-link active" href="penalites.php">Pénalités</a>
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
            <h2><i class="fas fa-exclamation-triangle"></i> Gestion des Pénalités</h2>
            <div class="btn-group">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjouter">
                    <i class="fas fa-plus"></i> Nouvelle pénalité
                </button>
                <a href="export-penalites.php" class="btn btn-info">
                    <i class="fas fa-download"></i> Exporter
                </a>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-danger mb-2">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                        <h4 class="mb-0"><?= $stats['impayees_count'] ?></h4>
                        <small class="text-muted">Impayées</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-success mb-2">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                        <h4 class="mb-0"><?= $stats['payees_count'] ?></h4>
                        <small class="text-muted">Payées</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-danger mb-2">
                            <i class="fas fa-euro-sign fa-2x"></i>
                        </div>
                        <h4 class="mb-0"><?= number_format($stats['montant_impaye'], 2) ?>€</h4>
                        <small class="text-muted">Montant impayé</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-success mb-2">
                            <i class="fas fa-coins fa-2x"></i>
                        </div>
                        <h4 class="mb-0"><?= number_format($stats['montant_paye'], 2) ?>€</h4>
                        <small class="text-muted">Montant encaissé</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglets -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $onglet == 'impayees' ? 'active' : '' ?>" href="?onglet=impayees">
                    <i class="fas fa-exclamation-triangle text-danger"></i> Impayées (<?= $stats['impayees_count'] ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $onglet == 'payees' ? 'active' : '' ?>" href="?onglet=payees">
                    <i class="fas fa-check text-success"></i> Payées (<?= $stats['payees_count'] ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $onglet == 'annulees' ? 'active' : '' ?>" href="?onglet=annulees">
                    <i class="fas fa-times text-secondary"></i> Annulées
                </a>
            </li>
        </ul>

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="onglet" value="<?= $onglet ?>">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="recherche" 
                               placeholder="Rechercher par nom, email, numéro de carte, description..." 
                               value="<?= htmlspecialchars($terme_recherche) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="type" class="form-select">
                            <option value="">Tous types</option>
                            <option value="retard" <?= $filtre_type == 'retard' ? 'selected' : '' ?>>Retard</option>
                            <option value="deterioration" <?= $filtre_type == 'deterioration' ? 'selected' : '' ?>>Détérioration</option>
                            <option value="perte" <?= $filtre_type == 'perte' ? 'selected' : '' ?>>Perte</option>
                            <option value="autre" <?= $filtre_type == 'autre' ? 'selected' : '' ?>>Autre</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filtrer
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="?onglet=<?= $onglet ?>" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-times"></i> Effacer
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des pénalités -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php
                    switch ($onglet) {
                        case 'payees':
                            echo '<i class="fas fa-check"></i> Pénalités payées';
                            break;
                        case 'annulees':
                            echo '<i class="fas fa-times"></i> Pénalités annulées';
                            break;
                        default:
                            echo '<i class="fas fa-exclamation-triangle"></i> Pénalités impayées';
                    }
                    ?>
                    (<?= count($penalites) ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>N° Pénalité</th>
                                <th>Utilisateur</th>
                                <th>Type</th>
                                <th>Montant</th>
                                <th>Description</th>
                                <th>Date création</th>
                                <th>Date paiement</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($penalites as $penalite): ?>
                                <tr class="<?= $penalite['statut'] == 'impayee' ? 'penalty-unpaid' : '' ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($penalite['numero_penalite']) ?></strong>
                                        <?php if ($penalite['numero_pret']): ?>
                                            <br><small class="text-muted">Prêt: <?= htmlspecialchars($penalite['numero_pret']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($penalite['prenom'] . ' ' . $penalite['nom']) ?></strong>
                                        <br><small class="text-muted">Carte: <?= htmlspecialchars($penalite['numero_carte']) ?></small>
                                        <br><small class="text-muted"><?= htmlspecialchars($penalite['email']) ?></small>
                                    </td>
                                    <td>
                                        <?php
                                        $type_badges = [
                                            'retard' => 'bg-warning',
                                            'deterioration' => 'bg-danger',
                                            'perte' => 'bg-dark',
                                            'autre' => 'bg-secondary'
                                        ];
                                        $badge_class = $type_badges[$penalite['type_penalite']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?= $badge_class ?>">
                                            <?= ucfirst($penalite['type_penalite']) ?>
                                        </span>
                                        <?php if ($penalite['titre']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($penalite['titre']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong class="text-danger"><?= number_format($penalite['montant'], 2) ?>€</strong>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($penalite['description']) ?>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($penalite['date_creation'])) ?>
                                        <br><small class="text-muted"><?= date('H:i', strtotime($penalite['date_creation'])) ?></small>
                                        <?php if ($penalite['admin_prenom'] && $penalite['admin_nom']): ?>
                                            <br><small class="text-muted">par <?= htmlspecialchars($penalite['admin_prenom'] . ' ' . $penalite['admin_nom']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($penalite['date_paiement']): ?>
                                            <?= date('d/m/Y', strtotime($penalite['date_paiement'])) ?>
                                            <br><small class="text-muted"><?= date('H:i', strtotime($penalite['date_paiement'])) ?></small>
                                            <?php if ($penalite['mode_paiement']): ?>
                                                <br><small class="text-muted"><?= ucfirst($penalite['mode_paiement']) ?></small>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statut_badges = [
                                            'impayee' => 'bg-danger',
                                            'payee' => 'bg-success',
                                            'annulee' => 'bg-secondary',
                                            'remise' => 'bg-info'
                                        ];
                                        $badge_class = $statut_badges[$penalite['statut']] ?? 'bg-secondary';
                                        ?>
                                        <span class="badge <?= $badge_class ?>">
                                            <?= ucfirst($penalite['statut']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($penalite['statut'] == 'impayee'): ?>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-success" data-bs-toggle="modal" 
                                                        data-bs-target="#modalPayer"
                                                        data-penalite-id="<?= $penalite['id'] ?>"
                                                        data-montant="<?= $penalite['montant'] ?>"
                                                        data-utilisateur="<?= htmlspecialchars($penalite['prenom'] . ' ' . $penalite['nom']) ?>"
                                                        title="Marquer comme payée">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="annuler">
                                                    <input type="hidden" name="penalite_id" value="<?= $penalite['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Annuler cette pénalité ?')" 
                                                            title="Annuler">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <a href="penalite-details.php?id=<?= $penalite['id'] ?>" class="btn btn-outline-info btn-sm" title="Voir détails">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (empty($penalites)): ?>
            <div class="text-center py-5">
                <i class="fas fa-exclamation-triangle fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">Aucune pénalité trouvée</h4>
                <p class="text-muted">
                    <?php
                    switch ($onglet) {
                        case 'payees':
                            echo "Aucune pénalité payée pour le moment.";
                            break;
                        case 'annulees':
                            echo "Aucune pénalité annulée.";
                            break;
                        default:
                            echo "Aucune pénalité impayée. C'est une bonne nouvelle !";
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Payer Pénalité -->
    <div class="modal fade" id="modalPayer" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Marquer comme payée</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="payer">
                        <input type="hidden" name="penalite_id" id="payer_penalite_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Utilisateur:</label>
                            <p class="fw-bold" id="payer_utilisateur"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Montant à payer:</label>
                            <p class="fw-bold text-danger" id="payer_montant"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="mode_paiement" class="form-label">Mode de paiement *</label>
                            <select class="form-select" name="mode_paiement" required>
                                <option value="">Sélectionner un mode</option>
                                <option value="especes">Espèces</option>
                                <option value="carte">Carte bancaire</option>
                                <option value="cheque">Chèque</option>
                                <option value="virement">Virement</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">Confirmer le paiement</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter Pénalité -->
    <div class="modal fade" id="modalAjouter" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter une pénalité</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter">
                        
                        <div class="mb-3">
                            <label for="utilisateur_id" class="form-label">ID Utilisateur *</label>
                            <input type="number" class="form-control" name="utilisateur_id" required>
                            <div class="form-text">Entrez l'ID de l'utilisateur concerné</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="type_penalite" class="form-label">Type de pénalité *</label>
                            <select class="form-select" name="type_penalite" required>
                                <option value="">Sélectionner un type</option>
                                <option value="retard">Retard</option>
                                <option value="deterioration">Détérioration</option>
                                <option value="perte">Perte</option>
                                <option value="autre">Autre</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="montant" class="form-label">Montant (€) *</label>
                            <input type="number" class="form-control" name="montant" step="0.01" min="0" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description *</label>
                            <textarea class="form-control" name="description" rows="3" required 
                                      placeholder="Décrivez la raison de cette pénalité..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Ajouter la pénalité</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du modal paiement
        document.getElementById('modalPayer').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const penaliteId = button.getAttribute('data-penalite-id');
            const montant = button.getAttribute('data-montant');
            const utilisateur = button.getAttribute('data-utilisateur');
            
            document.getElementById('payer_penalite_id').value = penaliteId;
            document.getElementById('payer_montant').textContent = montant + '€';
            document.getElementById('payer_utilisateur').textContent = utilisateur;
        });
    </script>
</body>
</html>
