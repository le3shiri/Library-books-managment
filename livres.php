<?php
require_once 'config/database.php';
require_once 'config/auth.php';
require_once 'classes/Livre.php';

$auth = requireAuth();
$livre = new Livre();

// Traitement des actions
if ($_POST) {
    try {
        if (isset($_POST['action'])) {
            switch ($_POST['action']) {
                case 'ajouter':
                    $livre->ajouter($_POST, $auth->getCurrentAdminId());
                    $message = "Livre ajouté avec succès!";
                    break;
                
                case 'modifier':
                    $livre->modifier($_POST['id'], $_POST, $auth->getCurrentAdminId());
                    $message = "Livre modifié avec succès!";
                    break;
                
                case 'supprimer':
                    $livre->supprimer($_POST['id'], $auth->getCurrentAdminId());
                    $message = "Livre supprimé avec succès!";
                    break;
                
                case 'ajouter_exemplaires':
                    $livre->ajouterExemplaires($_POST['livre_id'], $_POST['nombre'], $auth->getCurrentAdminId());
                    $message = "Exemplaires ajoutés avec succès!";
                    break;
            }
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupération des données
$filtres = [
    'categorie' => $_GET['categorie'] ?? '',
    'statut' => $_GET['statut'] ?? ''
];

$terme_recherche = $_GET['recherche'] ?? '';
$page = (int)($_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

if ($terme_recherche) {
    $livres = $livre->rechercher($terme_recherche, $filtres);
} else {
    $livres = $livre->listerTous($limit, $offset, $filtres);
}

// Récupération des données pour les formulaires
$database = new Database();
$conn = $database->getConnection();

// Catégories
$query_cat = "SELECT * FROM categories WHERE actif = 1 ORDER BY nom";
$stmt_cat = $conn->prepare($query_cat);
$stmt_cat->execute();
$categories = $stmt_cat->fetchAll();

// Auteurs
$query_aut = "SELECT * FROM auteurs ORDER BY nom, prenom";
$stmt_aut = $conn->prepare($query_aut);
$stmt_aut->execute();
$auteurs = $stmt_aut->fetchAll();

// Éditeurs
$query_edit = "SELECT * FROM editeurs ORDER BY nom";
$stmt_edit = $conn->prepare($query_edit);
$stmt_edit->execute();
$editeurs = $stmt_edit->fetchAll();

// Livre à modifier si demandé
$livre_modifier = null;
if (isset($_GET['modifier']) && $_GET['modifier']) {
    $livre_modifier = $livre->obtenirParId($_GET['modifier']);
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Livres - Bibliothèque</title>
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
        .livre-card {
            transition: transform 0.2s;
        }
        .livre-card:hover {
            transform: translateY(-5px);
        }
        .livre-image {
            height: 200px;
            object-fit: cover;
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
                <a class="nav-link active" href="livres.php">Livres</a>
                <a class="nav-link" href="utilisateurs.php">Utilisateurs</a>
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

        <!-- En-tête avec actions -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-books"></i> Gestion des Livres</h2>
            <div class="btn-group">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalAjouter">
                    <i class="fas fa-plus"></i> Nouveau livre
                </button>
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalImport">
                    <i class="fas fa-upload"></i> Importer
                </button>
                <a href="export-livres.php" class="btn btn-info">
                    <i class="fas fa-download"></i> Exporter
                </a>
            </div>
        </div>

        <!-- Filtres et recherche -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="recherche" 
                               placeholder="Rechercher par titre, auteur, ISBN..." 
                               value="<?= htmlspecialchars($terme_recherche) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="categorie" class="form-select">
                            <option value="">Toutes catégories</option>
                            <?php foreach ($categories as $cat): ?>
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
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-search"></i> Filtrer
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des livres -->
        <div class="row">
            <?php foreach ($livres as $livre_item): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card livre-card h-100">
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                            <?php if ($livre_item['image_couverture']): ?>
                                <img src="<?= htmlspecialchars($livre_item['image_couverture']) ?>" 
                                     class="livre-image" alt="Couverture">
                            <?php else: ?>
                                <i class="fas fa-book fa-4x text-muted"></i>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($livre_item['titre']) ?></h6>
                            <?php if ($livre_item['sous_titre']): ?>
                                <p class="text-muted small"><?= htmlspecialchars($livre_item['sous_titre']) ?></p>
                            <?php endif; ?>
                            
                            <p class="card-text">
                                <strong>Auteur:</strong> 
                                <?php if ($livre_item['auteur_prenom'] && $livre_item['auteur_nom']): ?>
                                    <?= htmlspecialchars($livre_item['auteur_prenom'] . ' ' . $livre_item['auteur_nom']) ?>
                                <?php else: ?>
                                    <span class="text-muted">Non renseigné</span>
                                <?php endif; ?>
                            </p>
                            
                            <p class="card-text">
                                <strong>ISBN:</strong> <?= htmlspecialchars($livre_item['isbn'] ?? 'N/A') ?><br>
                                <strong>Année:</strong> <?= $livre_item['annee_publication'] ?? 'N/A' ?><br>
                                <strong>Emplacement:</strong> <?= htmlspecialchars($livre_item['emplacement'] ?? 'Non défini') ?>
                            </p>
                            
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <?php if ($livre_item['categorie_nom']): ?>
                                        <span class="badge" style="background-color: <?= $livre_item['categorie_couleur'] ?>">
                                            <?= htmlspecialchars($livre_item['categorie_nom']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="badge <?= $livre_item['exemplaires_disponibles'] > 0 ? 'bg-success' : 'bg-danger' ?>">
                                    <?= $livre_item['exemplaires_disponibles'] ?>/<?= $livre_item['nombre_exemplaires'] ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="btn-group w-100">
                                <a href="livre-details.php?id=<?= $livre_item['id'] ?>" class="btn btn-outline-info btn-sm">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="?modifier=<?= $livre_item['id'] ?>" class="btn btn-outline-warning btn-sm">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button class="btn btn-outline-success btn-sm" data-bs-toggle="modal" 
                                        data-bs-target="#modalExemplaires"
                                        data-livre-id="<?= $livre_item['id'] ?>"
                                        data-livre-titre="<?= htmlspecialchars($livre_item['titre']) ?>">
                                    <i class="fas fa-plus"></i>
                                </button>
                                <button class="btn btn-outline-danger btn-sm" 
                                        onclick="confirmerSuppression(<?= $livre_item['id'] ?>, '<?= htmlspecialchars($livre_item['titre']) ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($livres)): ?>
            <div class="text-center py-5">
                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">Aucun livre trouvé</h4>
                <p class="text-muted">Essayez de modifier vos critères de recherche ou ajoutez un nouveau livre.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal Ajouter/Modifier Livre -->
    <div class="modal fade" id="modalAjouter" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <?= $livre_modifier ? 'Modifier le livre' : 'Ajouter un livre' ?>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="<?= $livre_modifier ? 'modifier' : 'ajouter' ?>">
                        <?php if ($livre_modifier): ?>
                            <input type="hidden" name="id" value="<?= $livre_modifier['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label for="titre" class="form-label">Titre *</label>
                                <input type="text" class="form-control" name="titre" required
                                       value="<?= $livre_modifier ? htmlspecialchars($livre_modifier['titre']) : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="isbn" class="form-label">ISBN</label>
                                <input type="text" class="form-control" name="isbn"
                                       value="<?= $livre_modifier ? htmlspecialchars($livre_modifier['isbn']) : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="sous_titre" class="form-label">Sous-titre</label>
                            <input type="text" class="form-control" name="sous_titre"
                                   value="<?= $livre_modifier ? htmlspecialchars($livre_modifier['sous_titre']) : '' ?>">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="auteur_principal_id" class="form-label">Auteur principal</label>
                                <select class="form-select" name="auteur_principal_id">
                                    <option value="">Sélectionner un auteur</option>
                                    <?php foreach ($auteurs as $auteur): ?>
                                        <option value="<?= $auteur['id'] ?>" 
                                                <?= ($livre_modifier && $livre_modifier['auteur_principal_id'] == $auteur['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($auteur['prenom'] . ' ' . $auteur['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="editeur_id" class="form-label">Éditeur</label>
                                <select class="form-select" name="editeur_id">
                                    <option value="">Sélectionner un éditeur</option>
                                    <?php foreach ($editeurs as $editeur): ?>
                                        <option value="<?= $editeur['id'] ?>"
                                                <?= ($livre_modifier && $livre_modifier['editeur_id'] == $editeur['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($editeur['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="categorie_id" class="form-label">Catégorie *</label>
                                <select class="form-select" name="categorie_id" required>
                                    <option value="">Sélectionner une catégorie</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"
                                                <?= ($livre_modifier && $livre_modifier['categorie_id'] == $cat['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cat['nom']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="annee_publication" class="form-label">Année de publication</label>
                                <input type="number" class="form-control" name="annee_publication" min="1000" max="<?= date('Y') ?>"
                                       value="<?= $livre_modifier ? $livre_modifier['annee_publication'] : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="langue" class="form-label">Langue</label>
                                <select class="form-select" name="langue">
                                    <option value="Français" <?= ($livre_modifier && $livre_modifier['langue'] == 'Français') ? 'selected' : '' ?>>Français</option>
                                    <option value="Anglais" <?= ($livre_modifier && $livre_modifier['langue'] == 'Anglais') ? 'selected' : '' ?>>Anglais</option>
                                    <option value="Espagnol" <?= ($livre_modifier && $livre_modifier['langue'] == 'Espagnol') ? 'selected' : '' ?>>Espagnol</option>
                                    <option value="Allemand" <?= ($livre_modifier && $livre_modifier['langue'] == 'Allemand') ? 'selected' : '' ?>>Allemand</option>
                                    <option value="Italien" <?= ($livre_modifier && $livre_modifier['langue'] == 'Italien') ? 'selected' : '' ?>>Italien</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="nombre_pages" class="form-label">Nombre de pages</label>
                                <input type="number" class="form-control" name="nombre_pages" min="1"
                                       value="<?= $livre_modifier ? $livre_modifier['nombre_pages'] : '' ?>">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="format" class="form-label">Format</label>
                                <select class="form-select" name="format">
                                    <option value="broché" <?= ($livre_modifier && $livre_modifier['format'] == 'broché') ? 'selected' : '' ?>>Broché</option>
                                    <option value="poche" <?= ($livre_modifier && $livre_modifier['format'] == 'poche') ? 'selected' : '' ?>>Poche</option>
                                    <option value="relié" <?= ($livre_modifier && $livre_modifier['format'] == 'relié') ? 'selected' : '' ?>>Relié</option>
                                    <option value="numérique" <?= ($livre_modifier && $livre_modifier['format'] == 'numérique') ? 'selected' : '' ?>>Numérique</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="nombre_exemplaires" class="form-label">Nombre d'exemplaires *</label>
                                <input type="number" class="form-control" name="nombre_exemplaires" min="1" required
                                       value="<?= $livre_modifier ? $livre_modifier['nombre_exemplaires'] : '1' ?>">
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="emplacement" class="form-label">Emplacement</label>
                                <input type="text" class="form-control" name="emplacement" 
                                       placeholder="Ex: A1-001"
                                       value="<?= $livre_modifier ? htmlspecialchars($livre_modifier['emplacement']) : '' ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="prix_achat" class="form-label">Prix d'achat (€)</label>
                                <input type="number" class="form-control" name="prix_achat" step="0.01" min="0"
                                       value="<?= $livre_modifier ? $livre_modifier['prix_achat'] : '' ?>">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" name="description" rows="3"><?= $livre_modifier ? htmlspecialchars($livre_modifier['description']) : '' ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="resume" class="form-label">Résumé</label>
                            <textarea class="form-control" name="resume" rows="4"><?= $livre_modifier ? htmlspecialchars($livre_modifier['resume']) : '' ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="mots_cles" class="form-label">Mots-clés</label>
                            <input type="text" class="form-control" name="mots_cles" 
                                   placeholder="Séparez les mots-clés par des virgules"
                                   value="<?= $livre_modifier ? htmlspecialchars($livre_modifier['mots_cles']) : '' ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="etat_general" class="form-label">État général</label>
                            <select class="form-select" name="etat_general">
                                <option value="excellent" <?= ($livre_modifier && $livre_modifier['etat_general'] == 'excellent') ? 'selected' : '' ?>>Excellent</option>
                                <option value="bon" <?= ($livre_modifier && $livre_modifier['etat_general'] == 'bon') ? 'selected' : 'selected' ?>>Bon</option>
                                <option value="moyen" <?= ($livre_modifier && $livre_modifier['etat_general'] == 'moyen') ? 'selected' : '' ?>>Moyen</option>
                                <option value="mauvais" <?= ($livre_modifier && $livre_modifier['etat_general'] == 'mauvais') ? 'selected' : '' ?>>Mauvais</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary">
                            <?= $livre_modifier ? 'Modifier' : 'Ajouter' ?> le livre
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Ajouter Exemplaires -->
    <div class="modal fade" id="modalExemplaires" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Ajouter des exemplaires</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="ajouter_exemplaires">
                        <input type="hidden" name="livre_id" id="exemplaires_livre_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Livre:</label>
                            <p class="fw-bold" id="exemplaires_livre_titre"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre d'exemplaires à ajouter *</label>
                            <input type="number" class="form-control" name="nombre" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">Ajouter les exemplaires</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Formulaire de suppression caché -->
    <form id="formSuppression" method="POST" style="display: none;">
        <input type="hidden" name="action" value="supprimer">
        <input type="hidden" name="id" id="suppression_id">
    </form>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Ouvrir le modal de modification si demandé
        <?php if ($livre_modifier): ?>
            document.addEventListener('DOMContentLoaded', function() {
                new bootstrap.Modal(document.getElementById('modalAjouter')).show();
            });
        <?php endif; ?>

        // Gestion du modal exemplaires
        document.getElementById('modalExemplaires').addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const livreId = button.getAttribute('data-livre-id');
            const livreTitre = button.getAttribute('data-livre-titre');
            
            document.getElementById('exemplaires_livre_id').value = livreId;
            document.getElementById('exemplaires_livre_titre').textContent = livreTitre;
        });

        // Fonction de confirmation de suppression
        function confirmerSuppression(id, titre) {
            if (confirm('Êtes-vous sûr de vouloir supprimer le livre "' + titre + '" ?\n\nCette action est irréversible.')) {
                document.getElementById('suppression_id').value = id;
                document.getElementById('formSuppression').submit();
            }
        }
    </script>
</body>
</html>
