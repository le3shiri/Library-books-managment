-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Hôte : 127.0.0.1
-- Généré le : dim. 15 juin 2025 à 13:37
-- Version du serveur : 10.4.32-MariaDB
-- Version de PHP : 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de données : `bibliotheque`
--

-- --------------------------------------------------------

--
-- Structure de la table `admins`
--

CREATE TABLE `admins` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `role` enum('admin','bibliothecaire') DEFAULT 'bibliothecaire',
  `statut` enum('actif','inactif') DEFAULT 'actif',
  `derniere_connexion` timestamp NULL DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `admins`
--

INSERT INTO `admins` (`id`, `username`, `password`, `nom`, `prenom`, `email`, `role`, `statut`, `derniere_connexion`, `date_creation`) VALUES
(1, 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur', 'Système', 'admin@bibliotheque.fr', 'admin', 'actif', '2025-06-15 11:29:25', '2025-06-05 22:48:57'),
(2, 'bibliothecaire1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Martin', 'Sophie', 'sophie.martin@bibliotheque.fr', 'bibliothecaire', 'actif', NULL, '2025-06-05 22:48:57'),
(3, 'bibliothecaire2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dubois', 'Pierre', 'pierre.dubois@bibliotheque.fr', 'bibliothecaire', 'actif', NULL, '2025-06-05 22:48:57');

-- --------------------------------------------------------

--
-- Structure de la table `auteurs`
--

CREATE TABLE `auteurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) DEFAULT NULL,
  `biographie` text DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `date_deces` date DEFAULT NULL,
  `nationalite` varchar(50) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `auteurs`
--

INSERT INTO `auteurs` (`id`, `nom`, `prenom`, `biographie`, `date_naissance`, `date_deces`, `nationalite`, `date_creation`) VALUES
(1, 'Hugo', 'Victor', 'Écrivain français du XIXe siècle, figure du romantisme', '1802-02-26', NULL, 'Française', '2025-06-05 22:48:57'),
(2, 'Saint-Exupéry', 'Antoine de', 'Écrivain et aviateur français', '1900-06-29', NULL, 'Française', '2025-06-05 22:48:57'),
(3, 'Orwell', 'George', 'Écrivain britannique, auteur de 1984', '1903-06-25', NULL, 'Britannique', '2025-06-05 22:48:57'),
(4, 'Camus', 'Albert', 'Écrivain et philosophe français, prix Nobel', '1913-11-07', NULL, 'Française', '2025-06-05 22:48:57'),
(5, 'Herbert', 'Frank', 'Écrivain américain de science-fiction', '1920-10-08', NULL, 'Américaine', '2025-06-05 22:48:57'),
(6, 'Tolkien', 'J.R.R.', 'Écrivain britannique, créateur du Seigneur des Anneaux', '1892-01-03', NULL, 'Britannique', '2025-06-05 22:48:57'),
(7, 'Asimov', 'Isaac', 'Écrivain américain de science-fiction', '1920-01-02', NULL, 'Américaine', '2025-06-05 22:48:57'),
(8, 'Christie', 'Agatha', 'Écrivaine britannique de romans policiers', '1890-09-15', NULL, 'Britannique', '2025-06-05 22:48:57');

-- --------------------------------------------------------

--
-- Structure de la table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `description` text DEFAULT NULL,
  `code` varchar(10) DEFAULT NULL,
  `couleur` varchar(7) DEFAULT '#007bff',
  `actif` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `categories`
--

INSERT INTO `categories` (`id`, `nom`, `description`, `code`, `couleur`, `actif`, `date_creation`) VALUES
(1, 'Fiction', 'Romans et nouvelles de fiction', 'FIC', '#007bff', 1, '2025-06-05 22:48:57'),
(2, 'Science-Fiction', 'Littérature de science-fiction et fantasy', 'SF', '#6f42c1', 1, '2025-06-05 22:48:57'),
(3, 'Histoire', 'Livres d\'histoire et biographies', 'HIST', '#fd7e14', 1, '2025-06-05 22:48:57'),
(4, 'Sciences', 'Livres scientifiques et techniques', 'SCI', '#28a745', 1, '2025-06-05 22:48:57'),
(5, 'Philosophie', 'Ouvrages de philosophie et pensée', 'PHIL', '#dc3545', 1, '2025-06-05 22:48:57'),
(6, 'Jeunesse', 'Livres pour enfants et adolescents', 'JEU', '#ffc107', 1, '2025-06-05 22:48:57'),
(7, 'Art', 'Livres d\'art et beaux-arts', 'ART', '#e83e8c', 1, '2025-06-05 22:48:57'),
(8, 'Informatique', 'Livres techniques informatique', 'INFO', '#17a2b8', 1, '2025-06-05 22:48:57'),
(9, 'Cuisine', 'Livres de cuisine et gastronomie', 'CUIS', '#fd7e14', 1, '2025-06-05 22:48:57'),
(10, 'Voyage', 'Guides de voyage et récits', 'VOY', '#20c997', 1, '2025-06-05 22:48:57');

-- --------------------------------------------------------

--
-- Structure de la table `editeurs`
--

CREATE TABLE `editeurs` (
  `id` int(11) NOT NULL,
  `nom` varchar(150) NOT NULL,
  `adresse` text DEFAULT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `site_web` varchar(255) DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `editeurs`
--

INSERT INTO `editeurs` (`id`, `nom`, `adresse`, `telephone`, `email`, `site_web`, `date_creation`) VALUES
(1, 'Gallimard', '5 rue Gaston Gallimard, 75007 Paris', '01 49 54 42 00', 'contact@gallimard.fr', 'www.gallimard.fr', '2025-06-05 22:48:57'),
(2, 'Le Livre de Poche', '43 quai de Grenelle, 75015 Paris', '01 43 92 30 00', 'contact@livredepoche.com', 'www.livredepoche.com', '2025-06-05 22:48:57'),
(3, 'Flammarion', '87 quai Panhard et Levassor, 75013 Paris', '01 40 51 31 00', 'contact@flammarion.fr', 'www.flammarion.fr', '2025-06-05 22:48:57'),
(4, 'Seuil', '25 bd Romain-Rolland, 75014 Paris', '01 40 46 50 50', 'contact@seuil.com', 'www.seuil.com', '2025-06-05 22:48:57'),
(5, 'Larousse', '21 rue du Montparnasse, 75006 Paris', '01 44 39 44 00', 'contact@larousse.fr', 'www.larousse.fr', '2025-06-05 22:48:57');

-- --------------------------------------------------------

--
-- Structure de la table `livres`
--

CREATE TABLE `livres` (
  `id` int(11) NOT NULL,
  `isbn` varchar(20) DEFAULT NULL,
  `titre` varchar(200) NOT NULL,
  `sous_titre` varchar(200) DEFAULT NULL,
  `auteur_principal_id` int(11) DEFAULT NULL,
  `editeur_id` int(11) DEFAULT NULL,
  `annee_publication` year(4) DEFAULT NULL,
  `categorie_id` int(11) DEFAULT NULL,
  `langue` varchar(50) DEFAULT 'Français',
  `nombre_pages` int(11) DEFAULT NULL,
  `format` enum('poche','broché','relié','numérique') DEFAULT 'broché',
  `nombre_exemplaires` int(11) DEFAULT 1,
  `exemplaires_disponibles` int(11) DEFAULT 1,
  `exemplaires_reserves` int(11) DEFAULT 0,
  `description` text DEFAULT NULL,
  `resume` text DEFAULT NULL,
  `mots_cles` text DEFAULT NULL,
  `emplacement` varchar(100) DEFAULT NULL,
  `prix_achat` decimal(10,2) DEFAULT NULL,
  `date_acquisition` date DEFAULT curdate(),
  `etat_general` enum('excellent','bon','moyen','mauvais') DEFAULT 'bon',
  `image_couverture` varchar(255) DEFAULT NULL,
  `actif` tinyint(1) DEFAULT 1,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `livres`
--

INSERT INTO `livres` (`id`, `isbn`, `titre`, `sous_titre`, `auteur_principal_id`, `editeur_id`, `annee_publication`, `categorie_id`, `langue`, `nombre_pages`, `format`, `nombre_exemplaires`, `exemplaires_disponibles`, `exemplaires_reserves`, `description`, `resume`, `mots_cles`, `emplacement`, `prix_achat`, `date_acquisition`, `etat_general`, `image_couverture`, `actif`, `date_creation`, `date_modification`) VALUES
(1, '978-2-07-036057-3', 'Le Petit Prince', NULL, 2, 1, '1943', 1, 'Français', 96, 'broché', 3, 3, 0, 'Un conte poétique et philosophique sous l\'apparence d\'un conte pour enfants.', NULL, NULL, 'A1-001', 8.50, '2025-06-05', 'bon', NULL, 1, '2025-06-05 22:48:58', '2025-06-05 22:48:58'),
(2, '978-2-07-040987-2', '1984', NULL, 3, 1, '1949', 2, 'Français', 415, 'broché', 2, 0, 0, 'Un roman dystopique qui dépeint une société totalitaire.', NULL, NULL, 'B2-015', 12.90, '2025-06-05', 'bon', NULL, 1, '2025-06-05 22:48:58', '2025-06-05 23:09:43'),
(3, '978-2-253-00611-1', 'Les Misérables', NULL, 1, 2, '0000', 1, 'Français', 1900, 'broché', 2, 2, 0, 'Un roman historique, social et philosophique.', NULL, NULL, 'A1-025', 15.50, '2025-06-05', 'bon', NULL, 1, '2025-06-05 22:48:58', '2025-06-06 09:43:49'),
(4, '978-2-07-037127-2', 'Dune', NULL, 5, 3, '1965', 2, 'Français', 688, 'broché', 2, 1, 0, 'Un roman de science-fiction épique dans un univers désertique.', NULL, NULL, 'B2-032', 18.90, '2025-06-05', 'bon', NULL, 1, '2025-06-05 22:48:58', '2025-06-06 09:43:17'),
(5, '978-2-07-032929-8', 'L\'Étranger', NULL, 4, 1, '1942', 5, 'Français', 159, 'broché', 2, 2, 0, 'Un roman existentialiste sur l\'absurdité de la condition humaine.', NULL, NULL, 'C3-008', 9.80, '2025-06-05', 'bon', NULL, 1, '2025-06-05 22:48:58', '2025-06-05 22:48:58'),
(6, '978-2-07-061217-8', 'Le Seigneur des Anneaux', 'La Communauté de l\'Anneau', 6, 1, '1954', 2, 'Français', 576, 'broché', 3, 3, 0, 'Premier tome de la trilogie fantasy épique.', NULL, NULL, 'B2-045', 22.50, '2025-06-05', 'bon', NULL, 1, '2025-06-05 22:48:58', '2025-06-05 22:48:58'),
(7, '978-2-07-040964-3', 'Fondation', NULL, 7, 1, '1951', 2, 'Français', 280, 'broché', 2, 2, 0, 'Premier tome du cycle de Fondation.', NULL, NULL, 'B2-067', 14.20, '2025-06-05', 'bon', NULL, 1, '2025-06-05 22:48:58', '2025-06-05 22:48:58'),
(8, '978-2-253-00712-5', 'Le Crime de l\'Orient-Express', NULL, 8, 2, '1934', 1, 'Français', 256, 'broché', 2, 2, 0, 'Un des plus célèbres romans policiers d\'Agatha Christie.', NULL, NULL, 'A1-089', 11.30, '2025-06-05', 'bon', NULL, 1, '2025-06-05 22:48:58', '2025-06-06 09:43:53');

-- --------------------------------------------------------

--
-- Structure de la table `livre_auteurs`
--

CREATE TABLE `livre_auteurs` (
  `livre_id` int(11) NOT NULL,
  `auteur_id` int(11) NOT NULL,
  `role` enum('auteur','co-auteur','traducteur','illustrateur') DEFAULT 'auteur'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `livre_auteurs`
--

INSERT INTO `livre_auteurs` (`livre_id`, `auteur_id`, `role`) VALUES
(1, 2, 'auteur'),
(2, 3, 'auteur'),
(3, 1, 'auteur'),
(4, 5, 'auteur'),
(5, 4, 'auteur'),
(6, 6, 'auteur'),
(7, 7, 'auteur'),
(8, 8, 'auteur');

-- --------------------------------------------------------

--
-- Structure de la table `logs_activite`
--

CREATE TABLE `logs_activite` (
  `id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `table_concernee` varchar(50) DEFAULT NULL,
  `id_enregistrement` int(11) DEFAULT NULL,
  `details` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`details`)),
  `ip_adresse` varchar(45) DEFAULT NULL,
  `user_agent` text DEFAULT NULL,
  `date_action` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `logs_activite`
--

INSERT INTO `logs_activite` (`id`, `admin_id`, `utilisateur_id`, `action`, `table_concernee`, `id_enregistrement`, `details`, `ip_adresse`, `user_agent`, `date_action`) VALUES
(1, 1, NULL, 'LOGIN', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 22:49:59'),
(2, 1, NULL, 'CREATE_LOAN', 'prets', 4, '{\"utilisateur_id\":\"001\",\"livre_id\":\"2\",\"numero_pret\":\"PRET20256492\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 23:09:33'),
(3, 1, NULL, 'CREATE_LOAN', 'prets', 5, '{\"utilisateur_id\":\"001\",\"livre_id\":\"2\",\"numero_pret\":\"PRET20251304\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-05 23:09:43'),
(4, 1, NULL, 'LOGIN', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 09:41:43'),
(5, 1, NULL, 'SUSPEND_USER', 'utilisateurs', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 09:41:58'),
(6, 1, NULL, 'CREATE_LOAN', 'prets', 6, '{\"utilisateur_id\":\"006\",\"livre_id\":\"4\",\"numero_pret\":\"PRET20252226\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 09:43:17'),
(7, 1, NULL, 'RETURN_BOOK', 'prets', 1, '{\"utilisateur_id\":1,\"livre_id\":\"3\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 09:43:49'),
(8, 1, NULL, 'RETURN_BOOK', 'prets', 2, '{\"utilisateur_id\":2,\"livre_id\":\"8\"}', '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 09:43:53'),
(9, 1, NULL, 'CREATE_PENALTY', 'penalites', 4, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 09:44:37'),
(10, 1, NULL, 'PAY_PENALTY', 'penalites', 4, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-06 09:44:53'),
(11, 1, NULL, 'LOGIN', 'admins', 1, NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/137.0.0.0 Safari/537.36', '2025-06-15 11:29:25');

-- --------------------------------------------------------

--
-- Structure de la table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `utilisateur_id` int(11) DEFAULT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `type` enum('rappel_retour','reservation_disponible','penalite','expiration_carte','autre') NOT NULL,
  `titre` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `lu` tinyint(1) DEFAULT 0,
  `date_envoi` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_lecture` timestamp NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `notifications`
--

INSERT INTO `notifications` (`id`, `utilisateur_id`, `admin_id`, `type`, `titre`, `message`, `lu`, `date_envoi`, `date_lecture`) VALUES
(1, 3, NULL, 'penalite', 'Pénalité de retard', 'Vous avez une pénalité de 2,50€ pour le retard du livre \"Le Petit Prince\".', 0, '2025-06-05 22:48:58', NULL),
(2, 4, NULL, 'reservation_disponible', 'Livre disponible', 'Le livre \"Les Misérables\" que vous avez réservé est maintenant disponible.', 0, '2025-06-05 22:48:58', NULL),
(3, 1, NULL, 'penalite', 'Pénalité de retard', 'Vous avez une pénalité de 247.00€ pour le retard du livre.', 0, '2025-06-06 09:43:49', NULL),
(4, 2, NULL, 'penalite', 'Pénalité de retard', 'Vous avez une pénalité de 246.00€ pour le retard du livre.', 0, '2025-06-06 09:43:53', NULL);

-- --------------------------------------------------------

--
-- Structure de la table `parametres`
--

CREATE TABLE `parametres` (
  `id` int(11) NOT NULL,
  `cle_param` varchar(100) NOT NULL,
  `valeur` text NOT NULL,
  `description` text DEFAULT NULL,
  `type` enum('string','int','float','boolean','json') DEFAULT 'string',
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `parametres`
--

INSERT INTO `parametres` (`id`, `cle_param`, `valeur`, `description`, `type`, `date_modification`) VALUES
(1, 'duree_pret_standard', '14', 'Durée standard d\'un prêt en jours', 'int', '2025-06-05 22:48:57'),
(2, 'duree_pret_premium', '21', 'Durée prêt pour abonnement premium en jours', 'int', '2025-06-05 22:48:57'),
(3, 'penalite_retard_jour', '0.50', 'Montant de la pénalité par jour de retard', 'float', '2025-06-05 22:48:57'),
(4, 'max_prets_standard', '3', 'Nombre maximum de prêts simultanés (standard)', 'int', '2025-06-05 22:48:57'),
(5, 'max_prets_premium', '5', 'Nombre maximum de prêts simultanés (premium)', 'int', '2025-06-05 22:48:57'),
(6, 'duree_reservation', '7', 'Durée de validité d\'une réservation en jours', 'int', '2025-06-05 22:48:57'),
(7, 'nom_bibliotheque', 'Bibliothèque Municipale', 'Nom de la bibliothèque', 'string', '2025-06-05 22:48:57'),
(8, 'email_bibliotheque', 'contact@bibliotheque.fr', 'Email de contact', 'string', '2025-06-05 22:48:57'),
(9, 'telephone_bibliotheque', '01 23 45 67 89', 'Téléphone de la bibliothèque', 'string', '2025-06-05 22:48:57');

-- --------------------------------------------------------

--
-- Structure de la table `penalites`
--

CREATE TABLE `penalites` (
  `id` int(11) NOT NULL,
  `numero_penalite` varchar(20) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `pret_id` int(11) DEFAULT NULL,
  `type_penalite` enum('retard','deterioration','perte','autre') NOT NULL,
  `montant` decimal(10,2) NOT NULL,
  `description` text NOT NULL,
  `date_creation` date DEFAULT curdate(),
  `date_echeance` date DEFAULT NULL,
  `date_paiement` date DEFAULT NULL,
  `mode_paiement` enum('especes','carte','cheque','virement') DEFAULT NULL,
  `statut` enum('impayee','payee','annulee','remise') DEFAULT 'impayee',
  `admin_id` int(11) DEFAULT NULL,
  `notes` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `penalites`
--

INSERT INTO `penalites` (`id`, `numero_penalite`, `utilisateur_id`, `pret_id`, `type_penalite`, `montant`, `description`, `date_creation`, `date_echeance`, `date_paiement`, `mode_paiement`, `statut`, `admin_id`, `notes`) VALUES
(1, 'PEN001', 3, 3, 'retard', 2.50, 'Retard de 5 jours', '2024-01-20', NULL, NULL, NULL, 'impayee', NULL, NULL),
(2, 'PEN20253600', 1, 1, 'retard', 247.00, 'Retard de 494 jour(s)', '2025-06-06', NULL, NULL, NULL, 'impayee', 1, NULL),
(3, 'PEN20258793', 2, 2, 'retard', 246.00, 'Retard de 492 jour(s)', '2025-06-06', NULL, NULL, NULL, 'impayee', 1, NULL),
(4, 'PEN20257165', 6, NULL, 'retard', 1200.00, 'RETARD AND', '2025-06-06', NULL, '2025-06-06', 'especes', 'payee', 1, NULL);

-- --------------------------------------------------------

--
-- Structure de la table `prets`
--

CREATE TABLE `prets` (
  `id` int(11) NOT NULL,
  `numero_pret` varchar(20) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `livre_id` int(11) NOT NULL,
  `admin_id` int(11) DEFAULT NULL,
  `date_pret` date DEFAULT curdate(),
  `date_retour_prevue` date NOT NULL,
  `date_retour_effective` date DEFAULT NULL,
  `nombre_renouvellements` int(11) DEFAULT 0,
  `max_renouvellements` int(11) DEFAULT 2,
  `statut` enum('en_cours','retourne','en_retard','perdu','endommage') DEFAULT 'en_cours',
  `notes` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `prets`
--

INSERT INTO `prets` (`id`, `numero_pret`, `utilisateur_id`, `livre_id`, `admin_id`, `date_pret`, `date_retour_prevue`, `date_retour_effective`, `nombre_renouvellements`, `max_renouvellements`, `statut`, `notes`, `date_creation`, `date_modification`) VALUES
(1, 'PRET001', 1, 3, 2, '2024-01-15', '2024-01-29', '2025-06-06', 0, 2, 'retourne', '', '2025-06-05 22:48:58', '2025-06-06 09:43:49'),
(2, 'PRET002', 2, 8, 2, '2024-01-10', '2024-01-31', '2025-06-06', 0, 2, 'retourne', '', '2025-06-05 22:48:58', '2025-06-06 09:43:53'),
(3, 'PRET003', 3, 1, 3, '2024-01-05', '2024-01-19', NULL, 0, 2, 'en_retard', NULL, '2025-06-05 22:48:58', '2025-06-05 22:48:58'),
(4, 'PRET20256492', 1, 2, 1, '2025-06-06', '2025-06-20', NULL, 0, 2, 'en_cours', NULL, '2025-06-05 23:09:33', '2025-06-05 23:09:33'),
(5, 'PRET20251304', 1, 2, 1, '2025-06-06', '2025-06-20', NULL, 0, 2, 'en_cours', NULL, '2025-06-05 23:09:43', '2025-06-05 23:09:43'),
(6, 'PRET20252226', 6, 4, 1, '2025-06-06', '2025-06-20', NULL, 0, 2, 'en_cours', NULL, '2025-06-06 09:43:17', '2025-06-06 09:43:17');

-- --------------------------------------------------------

--
-- Structure de la table `reservations`
--

CREATE TABLE `reservations` (
  `id` int(11) NOT NULL,
  `numero_reservation` varchar(20) NOT NULL,
  `utilisateur_id` int(11) NOT NULL,
  `livre_id` int(11) NOT NULL,
  `date_reservation` date DEFAULT curdate(),
  `date_expiration` date NOT NULL,
  `date_notification` date DEFAULT NULL,
  `priorite` int(11) DEFAULT 1,
  `statut` enum('active','satisfaite','expiree','annulee') DEFAULT 'active',
  `notes` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `reservations`
--

INSERT INTO `reservations` (`id`, `numero_reservation`, `utilisateur_id`, `livre_id`, `date_reservation`, `date_expiration`, `date_notification`, `priorite`, `statut`, `notes`, `date_creation`) VALUES
(1, 'RES001', 4, 3, '2024-01-20', '2024-01-27', NULL, 1, 'expiree', NULL, '2025-06-05 22:48:58'),
(2, 'RES002', 5, 8, '2024-01-18', '2024-01-25', NULL, 1, 'expiree', NULL, '2025-06-05 22:48:58');

-- --------------------------------------------------------

--
-- Structure de la table `utilisateurs`
--

CREATE TABLE `utilisateurs` (
  `id` int(11) NOT NULL,
  `numero_carte` varchar(20) NOT NULL,
  `nom` varchar(100) NOT NULL,
  `prenom` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `telephone` varchar(20) DEFAULT NULL,
  `adresse` text DEFAULT NULL,
  `date_naissance` date DEFAULT NULL,
  `date_inscription` date DEFAULT curdate(),
  `date_expiration` date NOT NULL,
  `statut` enum('actif','suspendu','expire','inactif') DEFAULT 'actif',
  `type_abonnement` enum('standard','premium','etudiant') DEFAULT 'standard',
  `limite_prets` int(11) DEFAULT 3,
  `photo` varchar(255) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `date_creation` timestamp NOT NULL DEFAULT current_timestamp(),
  `date_modification` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Déchargement des données de la table `utilisateurs`
--

INSERT INTO `utilisateurs` (`id`, `numero_carte`, `nom`, `prenom`, `email`, `telephone`, `adresse`, `date_naissance`, `date_inscription`, `date_expiration`, `statut`, `type_abonnement`, `limite_prets`, `photo`, `notes`, `date_creation`, `date_modification`) VALUES
(1, 'BIB001', 'Dupont', 'Marie', 'marie.dupont@email.com', '0123456790', '10 Rue des Livres, 75001 Paris', '1985-03-15', '2025-06-05', '2024-12-31', 'suspendu', 'standard', 3, NULL, NULL, '2025-06-05 22:48:58', '2025-06-06 09:41:58'),
(2, 'BIB002', 'Martin', 'Pierre', 'pierre.martin@email.com', '0123456791', '20 Avenue de la Lecture, 75002 Paris', '1990-07-22', '2025-06-05', '2024-12-31', 'actif', 'premium', 5, NULL, NULL, '2025-06-05 22:48:58', '2025-06-05 22:48:58'),
(3, 'BIB003', 'Bernard', 'Sophie', 'sophie.bernard@email.com', '0123456792', '30 Place du Savoir, 75003 Paris', '1988-11-08', '2025-06-05', '2024-12-31', 'actif', 'etudiant', 4, NULL, NULL, '2025-06-05 22:48:58', '2025-06-05 22:48:58'),
(4, 'BIB004', 'Petit', 'Jean', 'jean.petit@email.com', '0123456793', '40 Rue de la Culture, 75004 Paris', '1975-05-12', '2025-06-05', '2024-12-31', 'actif', 'standard', 3, NULL, NULL, '2025-06-05 22:48:58', '2025-06-05 22:48:58'),
(5, 'BIB005', 'Robert', 'Anne', 'anne.robert@email.com', '0123456794', '50 Boulevard des Arts, 75005 Paris', '1992-09-30', '2025-06-05', '2024-12-31', 'actif', 'premium', 5, NULL, NULL, '2025-06-05 22:48:58', '2025-06-05 22:48:58'),
(6, 'BIB006', 'Moreau', 'Thomas', 'thomas.moreau@email.com', '0123456795', '60 Allée des Sciences, 75006 Paris', '1987-12-03', '2025-06-05', '2024-12-31', 'actif', 'standard', 3, NULL, NULL, '2025-06-05 22:48:58', '2025-06-05 22:48:58');

--
-- Index pour les tables déchargées
--

--
-- Index pour la table `admins`
--
ALTER TABLE `admins`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Index pour la table `auteurs`
--
ALTER TABLE `auteurs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Index pour la table `editeurs`
--
ALTER TABLE `editeurs`
  ADD PRIMARY KEY (`id`);

--
-- Index pour la table `livres`
--
ALTER TABLE `livres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `isbn` (`isbn`),
  ADD KEY `auteur_principal_id` (`auteur_principal_id`),
  ADD KEY `editeur_id` (`editeur_id`),
  ADD KEY `categorie_id` (`categorie_id`),
  ADD KEY `idx_livres_disponibilite` (`exemplaires_disponibles`);

--
-- Index pour la table `livre_auteurs`
--
ALTER TABLE `livre_auteurs`
  ADD PRIMARY KEY (`livre_id`,`auteur_id`),
  ADD KEY `auteur_id` (`auteur_id`);

--
-- Index pour la table `logs_activite`
--
ALTER TABLE `logs_activite`
  ADD PRIMARY KEY (`id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`);

--
-- Index pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `admin_id` (`admin_id`);

--
-- Index pour la table `parametres`
--
ALTER TABLE `parametres`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `cle_param` (`cle_param`);

--
-- Index pour la table `penalites`
--
ALTER TABLE `penalites`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_penalite` (`numero_penalite`),
  ADD KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `pret_id` (`pret_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `idx_penalites_statut` (`statut`);

--
-- Index pour la table `prets`
--
ALTER TABLE `prets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_pret` (`numero_pret`),
  ADD KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `livre_id` (`livre_id`),
  ADD KEY `admin_id` (`admin_id`),
  ADD KEY `idx_prets_statut` (`statut`),
  ADD KEY `idx_prets_dates` (`date_pret`,`date_retour_prevue`);

--
-- Index pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_reservation` (`numero_reservation`),
  ADD KEY `utilisateur_id` (`utilisateur_id`),
  ADD KEY `livre_id` (`livre_id`),
  ADD KEY `idx_reservations_statut` (`statut`);

--
-- Index pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `numero_carte` (`numero_carte`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_utilisateurs_statut` (`statut`);

--
-- AUTO_INCREMENT pour les tables déchargées
--

--
-- AUTO_INCREMENT pour la table `admins`
--
ALTER TABLE `admins`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT pour la table `auteurs`
--
ALTER TABLE `auteurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT pour la table `editeurs`
--
ALTER TABLE `editeurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT pour la table `livres`
--
ALTER TABLE `livres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT pour la table `logs_activite`
--
ALTER TABLE `logs_activite`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT pour la table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `parametres`
--
ALTER TABLE `parametres`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT pour la table `penalites`
--
ALTER TABLE `penalites`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT pour la table `prets`
--
ALTER TABLE `prets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT pour la table `reservations`
--
ALTER TABLE `reservations`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT pour la table `utilisateurs`
--
ALTER TABLE `utilisateurs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Contraintes pour les tables déchargées
--

--
-- Contraintes pour la table `livres`
--
ALTER TABLE `livres`
  ADD CONSTRAINT `livres_ibfk_1` FOREIGN KEY (`auteur_principal_id`) REFERENCES `auteurs` (`id`),
  ADD CONSTRAINT `livres_ibfk_2` FOREIGN KEY (`editeur_id`) REFERENCES `editeurs` (`id`),
  ADD CONSTRAINT `livres_ibfk_3` FOREIGN KEY (`categorie_id`) REFERENCES `categories` (`id`);

--
-- Contraintes pour la table `livre_auteurs`
--
ALTER TABLE `livre_auteurs`
  ADD CONSTRAINT `livre_auteurs_ibfk_1` FOREIGN KEY (`livre_id`) REFERENCES `livres` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `livre_auteurs_ibfk_2` FOREIGN KEY (`auteur_id`) REFERENCES `auteurs` (`id`) ON DELETE CASCADE;

--
-- Contraintes pour la table `logs_activite`
--
ALTER TABLE `logs_activite`
  ADD CONSTRAINT `logs_activite_ibfk_1` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`),
  ADD CONSTRAINT `logs_activite_ibfk_2` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`);

--
-- Contraintes pour la table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `notifications_ibfk_2` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`);

--
-- Contraintes pour la table `penalites`
--
ALTER TABLE `penalites`
  ADD CONSTRAINT `penalites_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `penalites_ibfk_2` FOREIGN KEY (`pret_id`) REFERENCES `prets` (`id`),
  ADD CONSTRAINT `penalites_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`);

--
-- Contraintes pour la table `prets`
--
ALTER TABLE `prets`
  ADD CONSTRAINT `prets_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `prets_ibfk_2` FOREIGN KEY (`livre_id`) REFERENCES `livres` (`id`),
  ADD CONSTRAINT `prets_ibfk_3` FOREIGN KEY (`admin_id`) REFERENCES `admins` (`id`);

--
-- Contraintes pour la table `reservations`
--
ALTER TABLE `reservations`
  ADD CONSTRAINT `reservations_ibfk_1` FOREIGN KEY (`utilisateur_id`) REFERENCES `utilisateurs` (`id`),
  ADD CONSTRAINT `reservations_ibfk_2` FOREIGN KEY (`livre_id`) REFERENCES `livres` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
