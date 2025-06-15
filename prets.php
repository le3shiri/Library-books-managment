<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'classes/Pret.php';

$auth = requireAuth();
$pret = new Pret();

// Traitement des actions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'retourner':
                    $pret->retournerLivre($_POST['pret_id'], $auth->getCurrentAdminId(), $_POST['notes'] ?? null);
                    $message = "Livre retourné avec succès!";
                    break;
                
                case 'renouveler':
                    $pret->renouvelerPret($_POST['pret_id'], $auth->getCurrentAdminId());
                    $message = "Prêt renouvelé avec succès!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupération des prêts avec filtres
$filtres = [
    'utilisateur_id' => $_GET['utilisateur_id'] ?? '',
    'en_retard' => isset($_GET['en_retard']) ? true : false
];

$terme_recherche = $_GET['recherche'] ?? '';
$onglet = $_GET['onglet'] ?? 'actifs';

switch ($onglet) {
    case 'actifs':
        $prets = $pret->listerPretsActifs($filtres);
        break;
    case 'historique':
        $prets = $pret->listerHistoriquePrets(null, 100);
        break;
    case 'retard':
        $filtres['en_retard'] = true;
        $prets = $pret->listerPretsActifs($filtres);
        break;
    default:
        $prets = $pret->listerPretsActifs($filtres);
}

// Statistiques
$stats = $pret->getStatistiques();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Prêts - Bibliothèque</title>
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
        .overdue {
            background-color: #fff5f5 !important;
            border-left: 4px solid #dc3545 !important;
        }
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
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
                <a class="nav-link active" href="prets.php">Prêts</a>
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
            <h2><i class="fas fa-hand-holding"></i> Gestion des Prêts</h2>
            <div class="btn-group">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouveau prêt
                </a>
                <a href="export-prets.php" class="btn btn-info">
                    <i class="fas fa-download"></i> Exporter
                </a>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-primary mb-2">
                            <i class="fas fa-book-open fa-2x"></i>
                        </div>
                        <h4 class="mb-0"><?= $stats['prets_en_cours'] ?></h4>
                        <small class="text-muted">Prêts en cours</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-danger mb-2">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                        <h4 class="mb-0"><?= $stats['prets_en_retard'] ?></h4>
                        <small class="text-muted">En retard</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-info mb-2">
                            <i class="fas fa-calendar fa-2x"></i>
                        </div>
                        <h4 class="mb-0"><?= $stats['prets_ce_mois'] ?></h4>
                        <small class="text-muted">Ce mois</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-success mb-2">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                        <h4 class="mb-0">
                            <?= $stats['prets_en_cours'] > 0 ? round(($stats['prets_en_cours'] - $stats['prets_en_retard']) / $stats['prets_en_cours'] * 100) : 100 ?>%
                        </h4>
                        <small class="text-muted">À jour</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglets -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $onglet == 'actifs' ? 'active' : '' ?>" href="?onglet=actifs">
                    <i class="fas fa-book-open"></i> Prêts actifs (<?= $stats['prets_en_cours'] ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $onglet == 'retard' ? 'active' : '' ?>" href="?onglet=retard">
                    <i class="fas fa-exclamation-triangle text-danger"></i> En retard (<?= $stats['prets_en_retard'] ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $onglet == 'historique' ? 'active' : '' ?>" href="?onglet=historique">
                    <i class="fas fa-history"></i> Historique
                </a>
            </li>
        </ul>

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="onglet" value="<?= $onglet ?>">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="recherche" 
                               placeholder="Rechercher par nom d'utilisateur, titre de livre..." 
                               value="<?= htmlspecialchars($terme_recherche) ?>">
                    </div>
                    <div class="col-md-3">
                        <input type="number" class="form-control" name="utilisateur_id" 
                               placeholder="ID Utilisateur" 
                               value="<?= $filtres['utilisateur_id'] ?>">
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des prêts -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php
                    switch ($onglet) {
                        case 'retard':
                            echo '<i class="fas fa-exclamation-triangle"></i> Prêts en retard';
                            break;
                        case 'historique':
                            echo '<i class="fas fa-history"></i> Historique des prêts';
                            break;
                        default:
                            echo '<i class="fas fa-book-open"></i> Prêts actifs';
                    }
                    ?>
                    (<?= count($prets) ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>N° Prêt</th>
                                <th>Emprunteur</th>
                                <th>Livre</th>
                                <th>Date d'emprunt</th>
                                <th>Date de retour</th>
                                <th>Statut</th>
                                <th>Renouvellements</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($prets as $pret_item): ?>
                                <tr class="<?= ($pret_item['statut_reel'] ?? $pret_item['statut']) == 'en_retard' ? 'overdue' : '' ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($pret_item['numero_pret']) ?></strong>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($pret_item['prenom'] . ' ' . $pret_item['nom']) ?></strong>
                                        <br><small class="text-muted">Carte: <?= htmlspecialchars($pret_item['numero_carte']) ?></small>
                                        <?php if ($pret_item['email']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($pret_item['email']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($pret_item['titre']) ?></strong>
                                        <?php if ($pret_item['auteur_prenom'] && $pret_item['auteur_nom']): ?>
                                            <br><small class="text-muted">par <?= htmlspecialchars($pret_item['auteur_prenom'] . ' ' . $pret_item['auteur_nom']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($pret_item['date_pret'])) ?>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($pret_item['date_retour_prevue'])) ?>
                                        <?php if (isset($pret_item['jours_retard']) && $pret_item['jours_retard'] > 0): ?>
                                            <br><small class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <?= $pret_item['jours_retard'] ?> jour(s) de retard
                                            </small>
                                        <?php endif; ?>
                                        <?php if ($pret_item['date_retour_effective']): ?>
                                            <br><small class="text-success">
                                                Retourné le <?= date('d/m/Y', strtotime($pret_item['date_retour_effective'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statut = $pret_item['statut_reel'] ?? $pret_item['statut'];
                                        $badge_class = 'bg-secondary';
                                        switch ($statut) {
                                            case 'en_cours':
                                                $badge_class = 'bg-primary';
                                                $statut_text = 'En cours';
                                                break;
                                            case 'en_retard':
                                                $badge_class = 'bg-danger';
                                                $statut_text = 'En retard';
                                                break;
                                            case 'retourne':
                                                $badge_class = 'bg-success';
                                                $statut_text = 'Retourné';
                                                break;
                                            case 'perdu':
                                                $badge_class = 'bg-dark';
                                                $statut_text = 'Perdu';
                                                break;
                                            case 'endommage':
                                                $badge_class = 'bg-warning';
                                                $statut_text = 'Endommagé';
                                                break;
                                            default:
                                                $statut_text = ucfirst($statut);
                                        }
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= $statut_text ?></span>
                                    </td>
                                    <td>
                                        <?= $pret_item['nombre_renouvellements'] ?>/<?= $pret_item['max_renouvellements'] ?>
                                    </td>
                                    <td>
                                        <?php if ($pret_item['statut'] == 'en_cours'): ?>
                                            <div class="btn-group btn-group-sm">
                                                <button class="btn btn-success" data-bs-toggle="modal" 
                                                        data-bs-target="#modalRetourner"
                                                        data-pret-id="<?= $pret_item['id'] ?>"
                                                        data-livre-titre="<?= htmlspecialchars($pret_item['titre']) ?>"
                                                        data-utilisateur-nom="<?= htmlspecialchars($pret_item['prenom'] . ' ' . $pret_item['nom']) ?>"
                                                        title="Retourner">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                                <?php if ($pret_item['nombre_renouvellements'] < $pret_item['max_renouvellements']): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="renouveler">
                                                        <input type="hidden" name="pret_id" value="<?= $pret_item['id'] ?>">
                                                        <button type="submit" class="btn btn-warning btn-sm" 
                                                                onclick="return confirm('Renouveler ce prêt ?')" title="Renouveler">
                                                            <i class="fas fa-redo"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="pret-details.php?id=<?= $pret_item['id'] ?>" class="btn btn-outline-info btn-sm" title="Détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        <?php else: ?>
                                            <a href="pret-details.php?id=<?= $pret_item['id'] ?>" class="btn btn-outline-info btn-sm" title="Voir détails">
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

        <?php if (empty($prets)): ?>
            <div class="text-center py-5">
                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">Aucun prêt trouvé</h4>
                <p class="text-muted">
                    <?php if ($onglet == 'retard'): ?>
                        Aucun prêt en retard. C'est une bonne nouvelle !
                    <?php else: ?>
                        Aucun prêt ne correspond à vos critères de recherche.
                    <?php endif; ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Retourner -->
    <div class="modal fade" id="modalRetourner" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Retourner un livre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="retourner">
                        <input type="hidden" name="pret_id" id="retourner_pret_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Livre:</label>
                            <p class="fw-bold" id="retourner_livre_titre"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Emprunteur:</label>
                            <p id="retourner_utilisateur_nom"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="notes_retour" class="form-label">Notes sur l'état du livre (optionnel):</label>
                            <textarea class="form-control" name="notes" id="notes_retour" rows="3" 
                                      placeholder="État du livre, dommages éventuels, commentaires..."></textarea>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Information:</strong> Si le livre est retourné en retard, une pénalité sera automatiquement calculée et ajoutée au compte de l'utilisateur.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">Confirmer le retour</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion du modal retour
        document.getElementById('modalRetourner').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const pretId = button.getAttribute('data-pret-id');
            const livreTitre = button.getAttribute('data-livre-titre');
            const utilisateurNom = button.getAttribute('data-utilisateur-nom');
            
            document.getElementById('retourner_pret_id').value = pretId;
            document.getElementById('retourner_livre_titre').textContent = livreTitre;
            document.getElementById('retourner_utilisateur_nom').textContent = utilisateurNom;
        });

        // Auto-refresh pour les prêts en retard
        <?php if ($onglet == 'retard'): ?>
        setInterval(function() {
            location.reload();
        }, 300000); // Refresh toutes les 5 minutes
        <?php endif; ?>
    </script>
</body>
</html>
