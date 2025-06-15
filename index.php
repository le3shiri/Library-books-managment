<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'classes/Livre.php';
require_once 'classes/Pret.php';
require_once 'classes/Reservation.php';

$auth = requireAuth();

$livre = new Livre();
$pret = new Pret();
$reservation = new Reservation();

// Traitement des actions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'emprunter':
                    $pret->creerPret($_POST['utilisateur_id'], $_POST['livre_id'], $auth->getCurrentAdminId());
                    $message = "Livre emprunté avec succès!";
                    break;
                
                case 'retourner':
                    $pret->retournerLivre($_POST['pret_id'], $auth->getCurrentAdminId(), $_POST['notes'] ?? null);
                    $message = "Livre retourné avec succès!";
                    break;
                
                case 'renouveler':
                    $pret->renouvelerPret($_POST['pret_id'], $auth->getCurrentAdminId());
                    $message = "Prêt renouvelé avec succès!";
                    break;
                
                case 'reserver':
                    $reservation->creerReservation($_POST['utilisateur_id'], $_POST['livre_id']);
                    $message = "Réservation créée avec succès!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupération des données avec filtres
$filtres = [
    'categorie' => $_GET['categorie'] ?? '',
    'statut' => $_GET['statut'] ?? ''
];

$terme_recherche = $_GET['recherche'] ?? '';
if ($terme_recherche) {
    $livres = $livre->rechercher($terme_recherche, $filtres);
} else {
    $livres = $livre->listerTous(20, 0, $filtres);
}

$prets_actifs = $pret->listerPretsActifs();
$reservations_actives = $reservation->listerReservationsActives();

// Statistiques pour le tableau de bord
$stats_livres = $livre->getStatistiques();
$stats_prets = $pret->getStatistiques();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de Bord - Système de Gestion de Bibliothèque</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #5a6fd8 0%, #6a4190 100%);
        }
        .status-badge {
            font-size: 0.8em;
        }
        .overdue {
            background-color: #dc3545 !important;
        }
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        .quick-actions {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-book"></i> <?= getParametre('nom_bibliotheque', 'Bibliothèque') ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="navbar-nav me-auto">
                    <a class="nav-link active" href="index.php">Tableau de bord</a>
                    <a class="nav-link" href="livres.php">Livres</a>
                    <a class="nav-link" href="utilisateurs.php">Utilisateurs</a>
                    <a class="nav-link" href="prets.php">Prêts</a>
                    <a class="nav-link" href="reservations.php">Réservations</a>
                    <a class="nav-link" href="penalites.php">Pénalités</a>
                    <?php if ($auth->getCurrentAdminRole() === 'admin'): ?>
                        <a class="nav-link" href="administration.php">Administration</a>
                    <?php endif; ?>
                </div>
                <div class="navbar-nav">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?= $_SESSION['admin_nom'] ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profil.php">Mon profil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
                        </ul>
                    </div>
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

        <!-- Statistiques -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-primary mb-2">
                            <i class="fas fa-book fa-2x"></i>
                        </div>
                        <h4 class="mb-0"><?= $stats_livres['total_livres'] ?></h4>
                        <small class="text-muted">Total Livres</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-success mb-2">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                        <h4 class="mb-0"><?= $stats_livres['livres_disponibles'] ?></h4>
                        <small class="text-muted">Disponibles</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <div class="text-info mb-2">
                            <i class="fas fa-hand-holding fa-2x"></i>
                        </div>
                        <h4 class="mb-0"><?= $stats_prets['prets_en_cours'] ?></h4>
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
                        <h4 class="mb-0"><?= $stats_prets['prets_en_retard'] ?></h4>
                        <small class="text-muted">En retard</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Recherche et Livres -->
        <div class="card mb-4" id="livres">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-search"></i> Recherche et Catalogue</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-4">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" name="recherche" 
                                       placeholder="Rechercher par titre, auteur, ISBN..." 
                                       value="<?= htmlspecialchars($terme_recherche) ?>">
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select name="categorie" class="form-select">
                                <option value="">Toutes catégories</option>
                                <?php
                                $database = new Database();
                                $conn = $database->getConnection();
                                $query = "SELECT * FROM categories WHERE actif = 1 ORDER BY nom";
                                $stmt = $conn->prepare($query);
                                $stmt->execute();
                                $categories = $stmt->fetchAll();
                                foreach ($categories as $cat):
                                ?>
                                    <option value="<?= $cat['id'] ?>" <?= $filtres['categorie'] == $cat['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="statut" class="form-select">
                                <option value="">Tous statuts</option>
                                <option value="disponible" <?= $filtres['statut'] == 'disponible' ? 'selected' : '' ?>>Disponible</option>
                                <option value="indisponible" <?= $filtres['statut'] == 'indisponible' ? 'selected' : '' ?>>Indisponible</option>
                                <option value="reserve" <?= $filtres['statut'] == 'reserve' ? 'selected' : '' ?>>Réservé</option>
                            </select>
                        </div>
                    </div>
                    <?php if ($terme_recherche || $filtres['categorie'] || $filtres['statut']): ?>
                        <div class="mt-2">
                            <a href="index.php" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-times"></i> Effacer les filtres
                            </a>
                        </div>
                    <?php endif; ?>
                </form>

                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Titre</th>
                                <th>Auteur</th>
                                <th>Catégorie</th>
                                <th>Disponibles</th>
                                <th>Emplacement</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($livres as $livre_item): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($livre_item['titre']) ?></strong>
                                        <?php if ($livre_item['sous_titre']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($livre_item['sous_titre']) ?></small>
                                        <?php endif; ?>
                                        <?php if ($livre_item['isbn']): ?>
                                            <br><small class="text-muted">ISBN: <?= htmlspecialchars($livre_item['isbn']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($livre_item['auteur_prenom'] && $livre_item['auteur_nom']): ?>
                                            <?= htmlspecialchars($livre_item['auteur_prenom'] . ' ' . $livre_item['auteur_nom']) ?>
                                        <?php else: ?>
                                            <span class="text-muted">Non renseigné</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($livre_item['categorie_nom']): ?>
                                            <span class="badge" style="background-color: <?= $livre_item['categorie_couleur'] ?>">
                                                <?= htmlspecialchars($livre_item['categorie_nom']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Non classé</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge <?= $livre_item['exemplaires_disponibles'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $livre_item['exemplaires_disponibles'] ?>/<?= $livre_item['nombre_exemplaires'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small class="text-muted"><?= htmlspecialchars($livre_item['emplacement'] ?? 'Non défini') ?></small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if ($livre_item['exemplaires_disponibles'] > 0): ?>
                                                <button class="btn btn-primary" data-bs-toggle="modal" 
                                                        data-bs-target="#modalEmprunter" 
                                                        data-livre-id="<?= $livre_item['id'] ?>"
                                                        data-livre-titre="<?= htmlspecialchars($livre_item['titre']) ?>">
                                                    <i class="fas fa-hand-holding"></i>
                                                </button>
                                            <?php else: ?>
                                                <button class="btn btn-warning" data-bs-toggle="modal" 
                                                        data-bs-target="#modalReserver"
                                                        data-livre-id="<?= $livre_item['id'] ?>"
                                                        data-livre-titre="<?= htmlspecialchars($livre_item['titre']) ?>">
                                                    <i class="fas fa-bookmark"></i>
                                                </button>
                                            <?php endif; ?>
                                            <a href="livre-details.php?id=<?= $livre_item['id'] ?>" class="btn btn-outline-info">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Section Prêts Actifs -->
        <div class="card mb-4" id="prets">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-book-open"></i> Prêts Actifs</h5>
                <a href="prets.php" class="btn btn-light btn-sm">Voir tout</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Emprunteur</th>
                                <th>Livre</th>
                                <th>Date d'emprunt</th>
                                <th>Date de retour</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $prets_limites = array_slice($prets_actifs, 0, 10);
                            foreach ($prets_limites as $pret_item): 
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($pret_item['prenom'] . ' ' . $pret_item['nom']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($pret_item['numero_carte']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($pret_item['titre']) ?></strong>
                                        <?php if ($pret_item['auteur_prenom'] && $pret_item['auteur_nom']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($pret_item['auteur_prenom'] . ' ' . $pret_item['auteur_nom']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($pret_item['date_pret'])) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($pret_item['date_retour_prevue'])) ?>
                                        <?php if ($pret_item['statut_reel'] == 'en_retard'): ?>
                                            <br><small class="text-danger">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <?= $pret_item['jours_retard'] ?> jour(s) de retard
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge status-badge <?= $pret_item['statut_reel'] == 'en_retard' ? 'bg-danger overdue' : 'bg-success' ?>">
                                            <?= $pret_item['statut_reel'] == 'en_retard' ? 'En retard' : 'En cours' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button class="btn btn-success" data-bs-toggle="modal" 
                                                    data-bs-target="#modalRetourner"
                                                    data-pret-id="<?= $pret_item['id'] ?>"
                                                    data-livre-titre="<?= htmlspecialchars($pret_item['titre']) ?>"
                                                    data-utilisateur-nom="<?= htmlspecialchars($pret_item['prenom'] . ' ' . $pret_item['nom']) ?>">
                                                <i class="fas fa-undo"></i>
                                            </button>
                                            <?php if ($pret_item['nombre_renouvellements'] < $pret_item['max_renouvellements']): ?>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="action" value="renouveler">
                                                    <input type="hidden" name="pret_id" value="<?= $pret_item['id'] ?>">
                                                    <button type="submit" class="btn btn-warning btn-sm" 
                                                            onclick="return confirm('Renouveler ce prêt ?')">
                                                        <i class="fas fa-redo"></i>
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

        <!-- Section Réservations -->
        <div class="card mb-4" id="reservations">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-bookmark"></i> Réservations Actives</h5>
                <a href="reservations.php" class="btn btn-light btn-sm">Voir tout</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Livre</th>
                                <th>Date de réservation</th>
                                <th>Expire le</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $reservations_limitees = array_slice($reservations_actives, 0, 10);
                            foreach ($reservations_limitees as $reservation_item): 
                            ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($reservation_item['prenom'] . ' ' . $reservation_item['nom']) ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($reservation_item['email']) ?></small>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($reservation_item['titre']) ?></strong>
                                        <?php if ($reservation_item['auteur_prenom'] && $reservation_item['auteur_nom']): ?>
                                            <br><small class="text-muted"><?= htmlspecialchars($reservation_item['auteur_prenom'] . ' ' . $reservation_item['auteur_nom']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($reservation_item['date_reservation'])) ?></td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($reservation_item['date_expiration'])) ?>
                                        <?php 
                                        $jours_restants = (strtotime($reservation_item['date_expiration']) - time()) / (60 * 60 * 24);
                                        if ($jours_restants <= 1): 
                                        ?>
                                            <br><small class="text-danger">
                                                <i class="fas fa-clock"></i> Expire bientôt
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" 
                                                data-bs-target="#modalEmprunter"
                                                data-livre-id="<?= $reservation_item['livre_id'] ?>"
                                                data-utilisateur-id="<?= $reservation_item['utilisateur_id'] ?>"
                                                data-livre-titre="<?= htmlspecialchars($reservation_item['titre']) ?>"
                                                data-utilisateur-nom="<?= htmlspecialchars($reservation_item['prenom'] . ' ' . $reservation_item['nom']) ?>">
                                            <i class="fas fa-hand-holding"></i> Prêter
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Actions rapides flottantes -->
    <div class="quick-actions">
        <div class="btn-group-vertical">
            <button class="btn btn-primary rounded-circle mb-2" data-bs-toggle="modal" data-bs-target="#modalEmprunterRapide" title="Nouveau prêt">
                <i class="fas fa-plus"></i>
            </button>
            <a href="livres.php?action=add" class="btn btn-success rounded-circle mb-2" title="Ajouter un livre">
                <i class="fas fa-book-medical"></i>
            </a>
            <a href="utilisateurs.php?action=add" class="btn btn-info rounded-circle" title="Ajouter un utilisateur">
                <i class="fas fa-user-plus"></i>
            </a>
        </div>
    </div>

    <!-- Modal Emprunter -->
    <div class="modal fade" id="modalEmprunter" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Emprunter un livre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="emprunter">
                        <input type="hidden" name="livre_id" id="emprunter_livre_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Livre sélectionné:</label>
                            <p class="fw-bold" id="emprunter_livre_titre"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="utilisateur_id" class="form-label">Utilisateur:</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="utilisateur_id" id="utilisateur_id" 
                                       placeholder="ID utilisateur" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="rechercherUtilisateur()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div class="form-text">Entrez l'ID de l'utilisateur ou recherchez par nom</div>
                            <div id="utilisateur_info" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">Confirmer l'emprunt</button>
                    </div>
                </form>
            </div>
        </div>
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
                            <label for="notes_retour" class="form-label">Notes (optionnel):</label>
                            <textarea class="form-control" name="notes" id="notes_retour" rows="3" 
                                      placeholder="État du livre, commentaires..."></textarea>
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

    <!-- Modal Réserver -->
    <div class="modal fade" id="modalReserver" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Réserver un livre</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="reserver">
                        <input type="hidden" name="livre_id" id="reserver_livre_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Livre sélectionné:</label>
                            <p class="fw-bold" id="reserver_livre_titre"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reserver_utilisateur_id" class="form-label">Utilisateur:</label>
                            <div class="input-group">
                                <input type="number" class="form-control" name="utilisateur_id" id="reserver_utilisateur_id" 
                                       placeholder="ID utilisateur" required>
                                <button type="button" class="btn btn-outline-secondary" onclick="rechercherUtilisateurReservation()">
                                    <i class="fas fa-search"></i>
                                </button>
                            </div>
                            <div id="utilisateur_info_reservation" class="mt-2"></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-warning">Confirmer la réservation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Gestion des modals
        document.getElementById('modalEmprunter').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const livreId = button.getAttribute('data-livre-id');
            const livreTitre = button.getAttribute('data-livre-titre');
            const utilisateurId = button.getAttribute('data-utilisateur-id');
            const utilisateurNom = button.getAttribute('data-utilisateur-nom');
            
            document.getElementById('emprunter_livre_id').value = livreId;
            document.getElementById('emprunter_livre_titre').textContent = livreTitre;
            
            if (utilisateurId) {
                document.getElementById('utilisateur_id').value = utilisateurId;
                if (utilisateurNom) {
                    document.getElementById('utilisateur_info').innerHTML = 
                        '<div class="alert alert-info"><i class="fas fa-user"></i> ' + utilisateurNom + '</div>';
                }
            }
        });

        document.getElementById('modalRetourner').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const pretId = button.getAttribute('data-pret-id');
            const livreTitre = button.getAttribute('data-livre-titre');
            const utilisateurNom = button.getAttribute('data-utilisateur-nom');
            
            document.getElementById('retourner_pret_id').value = pretId;
            document.getElementById('retourner_livre_titre').textContent = livreTitre;
            document.getElementById('retourner_utilisateur_nom').textContent = utilisateurNom;
        });

        document.getElementById('modalReserver').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const livreId = button.getAttribute('data-livre-id');
            const livreTitre = button.getAttribute('data-livre-titre');
            
            document.getElementById('reserver_livre_id').value = livreId;
            document.getElementById('reserver_livre_titre').textContent = livreTitre;
        });

        // Fonction de recherche d'utilisateur
        function rechercherUtilisateur() {
            const userId = document.getElementById('utilisateur_id').value;
            if (userId) {
                fetch('api/utilisateur.php?id=' + userId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('utilisateur_info').innerHTML = 
                                '<div class="alert alert-info">' +
                                '<i class="fas fa-user"></i> ' + data.utilisateur.prenom + ' ' + data.utilisateur.nom +
                                '<br><small>' + data.utilisateur.email + '</small>' +
                                '</div>';
                        } else {
                            document.getElementById('utilisateur_info').innerHTML = 
                                '<div class="alert alert-warning">Utilisateur non trouvé</div>';
                        }
                    })
                    .catch(error => {
                        document.getElementById('utilisateur_info').innerHTML = 
                            '<div class="alert alert-danger">Erreur de recherche</div>';
                    });
            }
        }

        function rechercherUtilisateurReservation() {
            const userId = document.getElementById('reserver_utilisateur_id').value;
            if (userId) {
                fetch('api/utilisateur.php?id=' + userId)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            document.getElementById('utilisateur_info_reservation').innerHTML = 
                                '<div class="alert alert-info">' +
                                '<i class="fas fa-user"></i> ' + data.utilisateur.prenom + ' ' + data.utilisateur.nom +
                                '<br><small>' + data.utilisateur.email + '</small>' +
                                '</div>';
                        } else {
                            document.getElementById('utilisateur_info_reservation').innerHTML = 
                                '<div class="alert alert-warning">Utilisateur non trouvé</div>';
                        }
                    });
            }
        }

        // Auto-refresh des statistiques toutes les 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);
    </script>
</body>
</html>
