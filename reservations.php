<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'classes/Reservation.php';

$auth = requireAuth();
$reservation = new Reservation();

// Traitement des actions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'annuler':
                    $reservation->annulerReservation($_POST['reservation_id'], $auth->getCurrentAdminId());
                    $message = "Réservation annulée avec succès!";
                    break;
                
                case 'satisfaire':
                    $reservation->satisfaireReservation($_POST['reservation_id'], $auth->getCurrentAdminId());
                    $message = "Réservation marquée comme satisfaite!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Expirer automatiquement les réservations
$reservations_expirees = $reservation->expirerReservations();
if ($reservations_expirees > 0) {
    $info = "$reservations_expirees réservation(s) expirée(s) automatiquement.";
}

// Récupération des réservations
$onglet = $_GET['onglet'] ?? 'actives';
$terme_recherche = $_GET['recherche'] ?? '';

$database = new Database();
$conn = $database->getConnection();

switch ($onglet) {
    case 'actives':
        $reservations = $reservation->listerReservationsActives();
        break;
    case 'expirees':
        $query = "SELECT r.*, u.nom, u.prenom, u.email, u.numero_carte, 
                         l.titre, l.sous_titre,
                         a.nom as auteur_nom, a.prenom as auteur_prenom
                  FROM reservations r
                  JOIN utilisateurs u ON r.utilisateur_id = u.id
                  JOIN livres l ON r.livre_id = l.id
                  LEFT JOIN auteurs a ON l.auteur_principal_id = a.id
                  WHERE r.statut = 'expiree'
                  ORDER BY r.date_expiration DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $reservations = $stmt->fetchAll();
        break;
    case 'satisfaites':
        $query = "SELECT r.*, u.nom, u.prenom, u.email, u.numero_carte, 
                         l.titre, l.sous_titre,
                         a.nom as auteur_nom, a.prenom as auteur_prenom
                  FROM reservations r
                  JOIN utilisateurs u ON r.utilisateur_id = u.id
                  JOIN livres l ON r.livre_id = l.id
                  LEFT JOIN auteurs a ON l.auteur_principal_id = a.id
                  WHERE r.statut = 'satisfaite'
                  ORDER BY r.date_notification DESC";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $reservations = $stmt->fetchAll();
        break;
    default:
        $reservations = $reservation->listerReservationsActives();
}

// Filtrer par recherche si nécessaire
if ($terme_recherche && !empty($reservations)) {
    $reservations = array_filter($reservations, function($res) use ($terme_recherche) {
        return stripos($res['nom'], $terme_recherche) !== false ||
               stripos($res['prenom'], $terme_recherche) !== false ||
               stripos($res['email'], $terme_recherche) !== false ||
               stripos($res['titre'], $terme_recherche) !== false;
    });
}

// Statistiques
$stats = $reservation->getStatistiques();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Réservations - Bibliothèque</title>
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
        .expire-soon {
            background-color: #fff3cd !important;
            border-left: 4px solid #ffc107 !important;
        }
        .expired {
            background-color: #f8d7da !important;
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
                <a class="nav-link" href="prets.php">Prêts</a>
                <a class="nav-link active" href="reservations.php">Réservations</a>
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

        <?php if (isset($info)): ?>
            <div class="alert alert-info alert-dismissible fade show">
                <i class="fas fa-info-circle"></i> <?= htmlspecialchars($info) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- En-tête -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-bookmark"></i> Gestion des Réservations</h2>
            <div class="btn-group">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nouvelle réservation
                </a>
                <a href="export-reservations.php" class="btn btn-info">
                    <i class="fas fa-download"></i> Exporter
                </a>
            </div>
        </div>

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-primary mb-2">
                            <i class="fas fa-bookmark fa-2x"></i>
                        </div>
                        <h4 class="mb-0"><?= $stats['reservations_actives'] ?></h4>
                        <small class="text-muted">Réservations actives</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-warning mb-2">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                        <h4 class="mb-0"><?= $stats['reservations_expirent_bientot'] ?></h4>
                        <small class="text-muted">Expirent bientôt</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-info mb-2">
                            <i class="fas fa-calendar fa-2x"></i>
                        </div>
                        <h4 class="mb-0"><?= $stats['reservations_ce_mois'] ?></h4>
                        <small class="text-muted">Ce mois</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Onglets -->
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
                <a class="nav-link <?= $onglet == 'actives' ? 'active' : '' ?>" href="?onglet=actives">
                    <i class="fas fa-bookmark"></i> Actives (<?= $stats['reservations_actives'] ?>)
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $onglet == 'satisfaites' ? 'active' : '' ?>" href="?onglet=satisfaites">
                    <i class="fas fa-check text-success"></i> Satisfaites
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $onglet == 'expirees' ? 'active' : '' ?>" href="?onglet=expirees">
                    <i class="fas fa-times text-danger"></i> Expirées
                </a>
            </li>
        </ul>

        <!-- Filtres -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <input type="hidden" name="onglet" value="<?= $onglet ?>">
                    <div class="col-md-10">
                        <input type="text" class="form-control" name="recherche" 
                               placeholder="Rechercher par nom d'utilisateur, email, titre de livre..." 
                               value="<?= htmlspecialchars($terme_recherche) ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des réservations -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <?php
                    switch ($onglet) {
                        case 'satisfaites':
                            echo '<i class="fas fa-check"></i> Réservations satisfaites';
                            break;
                        case 'expirees':
                            echo '<i class="fas fa-times"></i> Réservations expirées';
                            break;
                        default:
                            echo '<i class="fas fa-bookmark"></i> Réservations actives';
                    }
                    ?>
                    (<?= count($reservations) ?>)
                </h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>N° Réservation</th>
                                <th>Utilisateur</th>
                                <th>Livre</th>
                                <th>Date de réservation</th>
                                <th>Date d'expiration</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($reservations as $res): ?>
                                <?php
                                $jours_restants = isset($res['jours_restants']) ? $res['jours_restants'] : 
                                    (strtotime($res['date_expiration']) - time()) / (60 * 60 * 24);
                                $row_class = '';
                                if ($onglet == 'actives') {
                                    if ($jours_restants <= 0) {
                                        $row_class = 'expired';
                                    } elseif ($jours_restants <= 2) {
                                        $row_class = 'expire-soon';
                                    }
                                }
                                ?>
                                <tr class="<?= $row_class ?>">
                                    <td>
                                        <strong><?= htmlspecialchars($res['numero_reservation']) ?></strong>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($res['prenom'] . ' ' . $res['nom']) ?></strong>
                                        <br><small class="text-muted">Carte: <?= htmlspecialchars($res['numero_carte']) ?></small>
                                        <br><small class="text-muted"><?= htmlspecialchars($res['email']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($res['titre']) ?></strong>
                                        <?php if ($res['sous_titre']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($res['sous_titre']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($res['auteur_prenom'] && $res['auteur_nom']): ?>
                                            <br><small class="text-muted">par <?= htmlspecialchars($res['auteur_prenom'] . ' ' . $res['auteur_nom']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($res['date_reservation'])) ?>
                                        <br><small class="text-muted"><?= date('H:i', strtotime($res['date_reservation'])) ?></small>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($res['date_expiration'])) ?>
                                        <?php if ($onglet == 'actives'): ?>
                                            <br>
                                            <?php if ($jours_restants <= 0): ?>
                                                <small class="text-danger">
                                                    <i class="fas fa-exclamation-triangle"></i> Expirée
                                                </small>
                                            <?php elseif ($jours_restants <= 2): ?>
                                                <small class="text-warning">
                                                    <i class="fas fa-clock"></i> <?= ceil($jours_restants) ?> jour(s) restant(s)
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">
                                                    <?= ceil($jours_restants) ?> jour(s) restant(s)
                                                </small>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                        <?php if ($res['date_notification']): ?>
                                            <br><small class="text-info">
                                                Notifié le <?= date('d/m/Y', strtotime($res['date_notification'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statut = $res['statut'];
                                        $badge_class = 'bg-secondary';
                                        switch ($statut) {
                                            case 'active':
                                                $badge_class = $jours_restants <= 0 ? 'bg-danger' : ($jours_restants <= 2 ? 'bg-warning' : 'bg-primary');
                                                $statut_text = $jours_restants <= 0 ? 'Expirée' : 'Active';
                                                break;
                                            case 'satisfaite':
                                                $badge_class = 'bg-success';
                                                $statut_text = 'Satisfaite';
                                                break;
                                            case 'expiree':
                                                $badge_class = 'bg-danger';
                                                $statut_text = 'Expirée';
                                                break;
                                            case 'annulee':
                                                $badge_class = 'bg-secondary';
                                                $statut_text = 'Annulée';
                                                break;
                                            default:
                                                $statut_text = ucfirst($statut);
                                        }
                                        ?>
                                        <span class="badge <?= $badge_class ?>"><?= $statut_text ?></span>
                                    </td>
                                    <td>
                                        <?php if ($res['statut'] == 'active'): ?>
                                            <div class="btn-group btn-group-sm">
                                                <a href="index.php?emprunter_reservation=<?= $res['id'] ?>" 
                                                   class="btn btn-success" title="Prêter le livre">
                                                    <i class="fas fa-hand-holding"></i>
                                                </a>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="satisfaire">
                                                    <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                                    <button type="submit" class="btn btn-info btn-sm" 
                                                            onclick="return confirm('Marquer cette réservation comme satisfaite ?')" 
                                                            title="Marquer comme satisfaite">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="annuler">
                                                    <input type="hidden" name="reservation_id" value="<?= $res['id'] ?>">
                                                    <button type="submit" class="btn btn-danger btn-sm" 
                                                            onclick="return confirm('Annuler cette réservation ?')" 
                                                            title="Annuler">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <a href="reservation-details.php?id=<?= $res['id'] ?>" class="btn btn-outline-info btn-sm" title="Voir détails">
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

        <?php if (empty($reservations)): ?>
            <div class="text-center py-5">
                <i class="fas fa-bookmark fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">Aucune réservation trouvée</h4>
                <p class="text-muted">
                    <?php
                    switch ($onglet) {
                        case 'satisfaites':
                            echo "Aucune réservation satisfaite pour le moment.";
                            break;
                        case 'expirees':
                            echo "Aucune réservation expirée. C'est une bonne nouvelle !";
                            break;
                        default:
                            echo "Aucune réservation active ne correspond à vos critères.";
                    }
                    ?>
                </p>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh pour les réservations actives
        <?php if ($onglet == 'actives'): ?>
        setInterval(function() {
            location.reload();
        }, 300000); // Refresh toutes les 5 minutes
        <?php endif; ?>
    </script>
</body>
</html>
