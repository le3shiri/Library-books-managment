-- Insertion des données de test
USE bibliotheque;

-- Paramètres système
INSERT INTO parametres (cle_param, valeur, description, type) VALUES
('duree_pret_standard', '14', 'Durée standard d\'un prêt en jours', 'int'),
('duree_pret_premium', '21', 'Durée prêt pour abonnement premium en jours', 'int'),
('penalite_retard_jour', '0.50', 'Montant de la pénalité par jour de retard', 'float'),
('max_prets_standard', '3', 'Nombre maximum de prêts simultanés (standard)', 'int'),
('max_prets_premium', '5', 'Nombre maximum de prêts simultanés (premium)', 'int'),
('duree_reservation', '7', 'Durée de validité d\'une réservation en jours', 'int'),
('nom_bibliotheque', 'Bibliothèque Municipale', 'Nom de la bibliothèque', 'string'),
('email_bibliotheque', 'contact@bibliotheque.fr', 'Email de contact', 'string'),
('telephone_bibliotheque', '01 23 45 67 89', 'Téléphone de la bibliothèque', 'string');

-- Administrateurs (mot de passe: admin123)
INSERT INTO admins (username, password, nom, prenom, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur', 'Système', 'admin@bibliotheque.fr', 'admin'),
('bibliothecaire1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Martin', 'Sophie', 'sophie.martin@bibliotheque.fr', 'bibliothecaire'),
('bibliothecaire2', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Dubois', 'Pierre', 'pierre.dubois@bibliotheque.fr', 'bibliothecaire');

-- Catégories
INSERT INTO categories (nom, description, code, couleur) VALUES
('Fiction', 'Romans et nouvelles de fiction', 'FIC', '#007bff'),
('Science-Fiction', 'Littérature de science-fiction et fantasy', 'SF', '#6f42c1'),
('Histoire', 'Livres d\'histoire et biographies', 'HIST', '#fd7e14'),
('Sciences', 'Livres scientifiques et techniques', 'SCI', '#28a745'),
('Philosophie', 'Ouvrages de philosophie et pensée', 'PHIL', '#dc3545'),
('Jeunesse', 'Livres pour enfants et adolescents', 'JEU', '#ffc107'),
('Art', 'Livres d\'art et beaux-arts', 'ART', '#e83e8c'),
('Informatique', 'Livres techniques informatique', 'INFO', '#17a2b8'),
('Cuisine', 'Livres de cuisine et gastronomie', 'CUIS', '#fd7e14'),
('Voyage', 'Guides de voyage et récits', 'VOY', '#20c997');

-- Éditeurs
INSERT INTO editeurs (nom, adresse, telephone, email, site_web) VALUES
('Gallimard', '5 rue Gaston Gallimard, 75007 Paris', '01 49 54 42 00', 'contact@gallimard.fr', 'www.gallimard.fr'),
('Le Livre de Poche', '43 quai de Grenelle, 75015 Paris', '01 43 92 30 00', 'contact@livredepoche.com', 'www.livredepoche.com'),
('Flammarion', '87 quai Panhard et Levassor, 75013 Paris', '01 40 51 31 00', 'contact@flammarion.fr', 'www.flammarion.fr'),
('Seuil', '25 bd Romain-Rolland, 75014 Paris', '01 40 46 50 50', 'contact@seuil.com', 'www.seuil.com'),
('Larousse', '21 rue du Montparnasse, 75006 Paris', '01 44 39 44 00', 'contact@larousse.fr', 'www.larousse.fr');

-- Auteurs
INSERT INTO auteurs (nom, prenom, biographie, date_naissance, nationalite) VALUES
('Hugo', 'Victor', 'Écrivain français du XIXe siècle, figure du romantisme', '1802-02-26', 'Française'),
('Saint-Exupéry', 'Antoine de', 'Écrivain et aviateur français', '1900-06-29', 'Française'),
('Orwell', 'George', 'Écrivain britannique, auteur de 1984', '1903-06-25', 'Britannique'),
('Camus', 'Albert', 'Écrivain et philosophe français, prix Nobel', '1913-11-07', 'Française'),
('Herbert', 'Frank', 'Écrivain américain de science-fiction', '1920-10-08', 'Américaine'),
('Tolkien', 'J.R.R.', 'Écrivain britannique, créateur du Seigneur des Anneaux', '1892-01-03', 'Britannique'),
('Asimov', 'Isaac', 'Écrivain américain de science-fiction', '1920-01-02', 'Américaine'),
('Christie', 'Agatha', 'Écrivaine britannique de romans policiers', '1890-09-15', 'Britannique');

-- Utilisateurs
INSERT INTO utilisateurs (numero_carte, nom, prenom, email, telephone, adresse, date_naissance, date_expiration, type_abonnement, limite_prets) VALUES
('BIB001', 'Dupont', 'Marie', 'marie.dupont@email.com', '0123456790', '10 Rue des Livres, 75001 Paris', '1985-03-15', '2024-12-31', 'standard', 3),
('BIB002', 'Martin', 'Pierre', 'pierre.martin@email.com', '0123456791', '20 Avenue de la Lecture, 75002 Paris', '1990-07-22', '2024-12-31', 'premium', 5),
('BIB003', 'Bernard', 'Sophie', 'sophie.bernard@email.com', '0123456792', '30 Place du Savoir, 75003 Paris', '1988-11-08', '2024-12-31', 'etudiant', 4),
('BIB004', 'Petit', 'Jean', 'jean.petit@email.com', '0123456793', '40 Rue de la Culture, 75004 Paris', '1975-05-12', '2024-12-31', 'standard', 3),
('BIB005', 'Robert', 'Anne', 'anne.robert@email.com', '0123456794', '50 Boulevard des Arts, 75005 Paris', '1992-09-30', '2024-12-31', 'premium', 5),
('BIB006', 'Moreau', 'Thomas', 'thomas.moreau@email.com', '0123456795', '60 Allée des Sciences, 75006 Paris', '1987-12-03', '2024-12-31', 'standard', 3);

-- Livres
INSERT INTO livres (isbn, titre, sous_titre, auteur_principal_id, editeur_id, annee_publication, categorie_id, nombre_pages, nombre_exemplaires, exemplaires_disponibles, description, emplacement, prix_achat) VALUES
('978-2-07-036057-3', 'Le Petit Prince', NULL, 2, 1, 1943, 1, 96, 3, 3, 'Un conte poétique et philosophique sous l\'apparence d\'un conte pour enfants.', 'A1-001', 8.50),
('978-2-07-040987-2', '1984', NULL, 3, 1, 1949, 2, 415, 2, 2, 'Un roman dystopique qui dépeint une société totalitaire.', 'B2-015', 12.90),
('978-2-253-00611-1', 'Les Misérables', NULL, 1, 2, 1862, 1, 1900, 2, 1, 'Un roman historique, social et philosophique.', 'A1-025', 15.50),
('978-2-07-037127-2', 'Dune', NULL, 5, 3, 1965, 2, 688, 2, 2, 'Un roman de science-fiction épique dans un univers désertique.', 'B2-032', 18.90),
('978-2-07-032929-8', 'L\'Étranger', NULL, 4, 1, 1942, 5, 159, 2, 2, 'Un roman existentialiste sur l\'absurdité de la condition humaine.', 'C3-008', 9.80),
('978-2-07-061217-8', 'Le Seigneur des Anneaux', 'La Communauté de l\'Anneau', 6, 1, 1954, 2, 576, 3, 3, 'Premier tome de la trilogie fantasy épique.', 'B2-045', 22.50),
('978-2-07-040964-3', 'Fondation', NULL, 7, 1, 1951, 2, 280, 2, 2, 'Premier tome du cycle de Fondation.', 'B2-067', 14.20),
('978-2-253-00712-5', 'Le Crime de l\'Orient-Express', NULL, 8, 2, 1934, 1, 256, 2, 1, 'Un des plus célèbres romans policiers d\'Agatha Christie.', 'A1-089', 11.30);

-- Liaison livre-auteurs
INSERT INTO livre_auteurs (livre_id, auteur_id, role) VALUES
(1, 2, 'auteur'),
(2, 3, 'auteur'),
(3, 1, 'auteur'),
(4, 5, 'auteur'),
(5, 4, 'auteur'),
(6, 6, 'auteur'),
(7, 7, 'auteur'),
(8, 8, 'auteur');

-- Prêts en cours
INSERT INTO prets (numero_pret, utilisateur_id, livre_id, admin_id, date_pret, date_retour_prevue, statut) VALUES
('PRET001', 1, 3, 2, '2024-01-15', '2024-01-29', 'en_cours'),
('PRET002', 2, 8, 2, '2024-01-10', '2024-01-31', 'en_cours'),
('PRET003', 3, 1, 3, '2024-01-05', '2024-01-19', 'en_retard');

-- Réservations
INSERT INTO reservations (numero_reservation, utilisateur_id, livre_id, date_reservation, date_expiration, statut) VALUES
('RES001', 4, 3, '2024-01-20', '2024-01-27', 'active'),
('RES002', 5, 8, '2024-01-18', '2024-01-25', 'active');

-- Pénalités
INSERT INTO penalites (numero_penalite, utilisateur_id, pret_id, type_penalite, montant, description, date_creation, statut) VALUES
('PEN001', 3, 3, 'retard', 2.50, 'Retard de 5 jours', '2024-01-20', 'impayee');

-- Notifications
INSERT INTO notifications (utilisateur_id, type, titre, message) VALUES
(3, 'penalite', 'Pénalité de retard', 'Vous avez une pénalité de 2,50€ pour le retard du livre "Le Petit Prince".'),
(4, 'reservation_disponible', 'Livre disponible', 'Le livre "Les Misérables" que vous avez réservé est maintenant disponible.');
