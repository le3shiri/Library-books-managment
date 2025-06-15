-- Création de la base de données complète
CREATE DATABASE IF NOT EXISTS bibliotheque CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bibliotheque;

-- Table des administrateurs/bibliothécaires
CREATE TABLE admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    role ENUM('admin', 'bibliothecaire') DEFAULT 'bibliothecaire',
    statut ENUM('actif', 'inactif') DEFAULT 'actif',
    derniere_connexion TIMESTAMP NULL,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des utilisateurs/membres
CREATE TABLE utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_carte VARCHAR(20) UNIQUE NOT NULL,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    telephone VARCHAR(20),
    adresse TEXT,
    date_naissance DATE,
    date_inscription DATE DEFAULT CURRENT_DATE,
    date_expiration DATE NOT NULL,
    statut ENUM('actif', 'suspendu', 'expire', 'inactif') DEFAULT 'actif',
    type_abonnement ENUM('standard', 'premium', 'etudiant') DEFAULT 'standard',
    limite_prets INT DEFAULT 3,
    photo VARCHAR(255),
    notes TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des catégories de livres
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    description TEXT,
    code VARCHAR(10) UNIQUE,
    couleur VARCHAR(7) DEFAULT '#007bff',
    actif BOOLEAN DEFAULT TRUE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des éditeurs
CREATE TABLE editeurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(150) NOT NULL,
    adresse TEXT,
    telephone VARCHAR(20),
    email VARCHAR(150),
    site_web VARCHAR(255),
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des auteurs
CREATE TABLE auteurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100),
    biographie TEXT,
    date_naissance DATE,
    date_deces DATE,
    nationalite VARCHAR(50),
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des livres
CREATE TABLE livres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    isbn VARCHAR(20) UNIQUE,
    titre VARCHAR(200) NOT NULL,
    sous_titre VARCHAR(200),
    auteur_principal_id INT,
    editeur_id INT,
    annee_publication YEAR,
    categorie_id INT,
    langue VARCHAR(50) DEFAULT 'Français',
    nombre_pages INT,
    format ENUM('poche', 'broché', 'relié', 'numérique') DEFAULT 'broché',
    nombre_exemplaires INT DEFAULT 1,
    exemplaires_disponibles INT DEFAULT 1,
    exemplaires_reserves INT DEFAULT 0,
    description TEXT,
    resume TEXT,
    mots_cles TEXT,
    emplacement VARCHAR(100),
    prix_achat DECIMAL(10,2),
    date_acquisition DATE DEFAULT CURRENT_DATE,
    etat_general ENUM('excellent', 'bon', 'moyen', 'mauvais') DEFAULT 'bon',
    image_couverture VARCHAR(255),
    actif BOOLEAN DEFAULT TRUE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (auteur_principal_id) REFERENCES auteurs(id),
    FOREIGN KEY (editeur_id) REFERENCES editeurs(id),
    FOREIGN KEY (categorie_id) REFERENCES categories(id)
);

-- Table de liaison livre-auteur (pour les co-auteurs)
CREATE TABLE livre_auteurs (
    livre_id INT,
    auteur_id INT,
    role ENUM('auteur', 'co-auteur', 'traducteur', 'illustrateur') DEFAULT 'auteur',
    PRIMARY KEY (livre_id, auteur_id),
    FOREIGN KEY (livre_id) REFERENCES livres(id) ON DELETE CASCADE,
    FOREIGN KEY (auteur_id) REFERENCES auteurs(id) ON DELETE CASCADE
);

-- Table des prêts
CREATE TABLE prets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_pret VARCHAR(20) UNIQUE NOT NULL,
    utilisateur_id INT NOT NULL,
    livre_id INT NOT NULL,
    admin_id INT,
    date_pret DATE DEFAULT CURRENT_DATE,
    date_retour_prevue DATE NOT NULL,
    date_retour_effective DATE NULL,
    nombre_renouvellements INT DEFAULT 0,
    max_renouvellements INT DEFAULT 2,
    statut ENUM('en_cours', 'retourne', 'en_retard', 'perdu', 'endommage') DEFAULT 'en_cours',
    notes TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (livre_id) REFERENCES livres(id),
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

-- Table des réservations
CREATE TABLE reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_reservation VARCHAR(20) UNIQUE NOT NULL,
    utilisateur_id INT NOT NULL,
    livre_id INT NOT NULL,
    date_reservation DATE DEFAULT CURRENT_DATE,
    date_expiration DATE NOT NULL,
    date_notification DATE NULL,
    priorite INT DEFAULT 1,
    statut ENUM('active', 'satisfaite', 'expiree', 'annulee') DEFAULT 'active',
    notes TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (livre_id) REFERENCES livres(id)
);

-- Table des pénalités
CREATE TABLE penalites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    numero_penalite VARCHAR(20) UNIQUE NOT NULL,
    utilisateur_id INT NOT NULL,
    pret_id INT,
    type_penalite ENUM('retard', 'deterioration', 'perte', 'autre') NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    description TEXT NOT NULL,
    date_creation DATE DEFAULT CURRENT_DATE,
    date_echeance DATE,
    date_paiement DATE NULL,
    mode_paiement ENUM('especes', 'carte', 'cheque', 'virement') NULL,
    statut ENUM('impayee', 'payee', 'annulee', 'remise') DEFAULT 'impayee',
    admin_id INT,
    notes TEXT,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (pret_id) REFERENCES prets(id),
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

-- Table des notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    utilisateur_id INT,
    admin_id INT,
    type ENUM('rappel_retour', 'reservation_disponible', 'penalite', 'expiration_carte', 'autre') NOT NULL,
    titre VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    lu BOOLEAN DEFAULT FALSE,
    date_envoi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_lecture TIMESTAMP NULL,
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id),
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

-- Table des paramètres système
CREATE TABLE parametres (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cle_param VARCHAR(100) UNIQUE NOT NULL,
    valeur TEXT NOT NULL,
    description TEXT,
    type ENUM('string', 'int', 'float', 'boolean', 'json') DEFAULT 'string',
    date_modification TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Table des logs d'activité
CREATE TABLE logs_activite (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT,
    utilisateur_id INT,
    action VARCHAR(100) NOT NULL,
    table_concernee VARCHAR(50),
    id_enregistrement INT,
    details JSON,
    ip_adresse VARCHAR(45),
    user_agent TEXT,
    date_action TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id),
    FOREIGN KEY (utilisateur_id) REFERENCES utilisateurs(id)
);

-- Index pour optimiser les performances
CREATE INDEX idx_prets_statut ON prets(statut);
CREATE INDEX idx_prets_dates ON prets(date_pret, date_retour_prevue);
CREATE INDEX idx_livres_disponibilite ON livres(exemplaires_disponibles);
CREATE INDEX idx_utilisateurs_statut ON utilisateurs(statut);
CREATE INDEX idx_reservations_statut ON reservations(statut);
CREATE INDEX idx_penalites_statut ON penalites(statut);
