-- ================================================
-- TinyTrack — Complete Database
-- All modules integrated
-- ESPRIT 2A19 — 2025-2026
-- ================================================

DROP DATABASE IF EXISTS tinytrack;

CREATE DATABASE tinytrack
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE tinytrack;

-- ================================================
-- MODULE : Access Management
-- ================================================

CREATE TABLE user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_unique VARCHAR(20) UNIQUE DEFAULT NULL,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL DEFAULT '',
    mdp_temp VARCHAR(255) DEFAULT NULL,
    role ENUM('admin', 'educateur', 'parent') NOT NULL,
    telephone VARCHAR(20) DEFAULT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    date_naissance DATE DEFAULT NULL,
    adresse VARCHAR(255) DEFAULT NULL,
    cin VARCHAR(20) DEFAULT NULL,
    date_embauche DATE DEFAULT NULL,
    specialite VARCHAR(100) DEFAULT NULL,
    diplome VARCHAR(100) DEFAULT NULL,
    statut ENUM('actif', 'inactif', 'en_attente') NOT NULL DEFAULT 'actif',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE groupe (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(50) NOT NULL,
    niveau ENUM('petit', 'moyen', 'grand') NOT NULL,
    capacite INT NOT NULL DEFAULT 20,
    educateur_id INT DEFAULT NULL,
    CONSTRAINT fk_groupe_educateur
        FOREIGN KEY (educateur_id) REFERENCES user(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- MODULE : Child Management
-- ================================================

CREATE TABLE enfant (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code_unique VARCHAR(20) UNIQUE DEFAULT NULL,
    nom VARCHAR(50) NOT NULL,
    prenom VARCHAR(50) NOT NULL,
    date_naissance DATE NOT NULL,
    sexe ENUM('M', 'F') NOT NULL,
    photo VARCHAR(255) DEFAULT NULL,
    groupe_id INT DEFAULT NULL,
    parent_id INT DEFAULT NULL,
    date_inscription DATE NOT NULL DEFAULT (CURRENT_DATE),
    statut ENUM('actif', 'archive') NOT NULL DEFAULT 'actif',
    CONSTRAINT fk_enfant_groupe
        FOREIGN KEY (groupe_id) REFERENCES groupe(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_enfant_parent
        FOREIGN KEY (parent_id) REFERENCES user(id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE dossier_medical (
    id INT AUTO_INCREMENT PRIMARY KEY,
    enfant_id INT NOT NULL UNIQUE,
    groupe_sanguin VARCHAR(5) DEFAULT NULL,
    allergies TEXT DEFAULT NULL,
    maladies_chroniques TEXT DEFAULT NULL,
    vaccinations TEXT DEFAULT NULL,
    medecin_traitant VARCHAR(100) DEFAULT NULL,
    telephone_urgence VARCHAR(20) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    CONSTRAINT fk_dossier_enfant
        FOREIGN KEY (enfant_id) REFERENCES enfant(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- MODULE : Communication
-- ================================================

CREATE TABLE conversation (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    parent_id INT(11) NOT NULL,
    staff_id INT(11) NOT NULL,
    status VARCHAR(20) DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_conversation_parent
        FOREIGN KEY (parent_id) REFERENCES user(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_conversation_staff
        FOREIGN KEY (staff_id) REFERENCES user(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE message (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT(11) NOT NULL,
    sender_id INT(11) NOT NULL,
    sender_role VARCHAR(20) DEFAULT NULL,
    body TEXT NOT NULL,
    read_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_message_conversation
        FOREIGN KEY (conversation_id) REFERENCES conversation(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_message_sender
        FOREIGN KEY (sender_id) REFERENCES user(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- MODULE : Event Management
-- ================================================

CREATE TABLE evenement (
    id INT AUTO_INCREMENT PRIMARY KEY,
    titre VARCHAR(120) NOT NULL,
    description TEXT DEFAULT NULL,
    date DATE NOT NULL,
    heure_debut TIME NOT NULL,
    heure_fin TIME NOT NULL,
    type ENUM('concert','conference','sport','atelier','festival','formation','exposition','autre') NOT NULL,
    lieu VARCHAR(255) NOT NULL,
    capacite_max INT NOT NULL DEFAULT 50,
    prix DECIMAL(8,2) NOT NULL DEFAULT 0,
    groupe_id INT DEFAULT NULL,
    statut ENUM('planifie','en_cours','termine','annule') NOT NULL DEFAULT 'planifie'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE reservation (
    id INT AUTO_INCREMENT PRIMARY KEY,
    evenement_id INT NOT NULL,
    enfant_id INT DEFAULT NULL,
    parent_id INT DEFAULT NULL,
    nb_accompagnants INT NOT NULL DEFAULT 0,
    commentaire TEXT DEFAULT NULL,
    date_reservation DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    statut ENUM('confirmee','en_attente','annulee') NOT NULL DEFAULT 'en_attente',
    paiement ENUM('paye','non_paye') NOT NULL DEFAULT 'non_paye',
    CONSTRAINT fk_reservation_evenement
        FOREIGN KEY (evenement_id) REFERENCES evenement(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- MODULE : Activity Log
-- ================================================

CREATE TABLE activite (
    id_activite INT AUTO_INCREMENT PRIMARY KEY,
    nom_activite VARCHAR(100) NOT NULL,
    description TEXT DEFAULT NULL,
    date_activite DATE NOT NULL,
    heure_activite TIME NOT NULL,
    id_educateur INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rapport (
    id_rapport INT AUTO_INCREMENT PRIMARY KEY,
    contenu_rapport TEXT NOT NULL,
    date_rapport DATE NOT NULL,
    id_activite INT NOT NULL,
    id_educateur INT DEFAULT NULL,
    CONSTRAINT fk_rapport_activite
        FOREIGN KEY (id_activite) REFERENCES activite(id_activite)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ================================================
-- SAMPLE DATA : Users
-- Admin password : 123456 (hashed)
-- Educators / Parents : empty password (registration required)
-- ================================================

INSERT INTO user (code_unique, nom, prenom, email, mot_de_passe, role, telephone, date_naissance, adresse, cin, date_embauche, specialite, diplome) VALUES
('TT-1001', 'Admin', 'TinyTrack',      'admin@tinytrack.tn',            '$2y$10$aIbOx7k.MaCt7qHtxHlhJ.YrPNTOj.O7W00GhdVINrXgHrAIxIpRy', 'admin',     '+216 71 000 000', NULL,         NULL,                          NULL,       NULL,         NULL,                                    NULL),
('TT-1002', 'Hartley', 'Emily',        'emily.hartley@tinytrack.tn',    '', 'educateur', '+216 22 111 222', '1991-04-12', '14 Elm Grove, Ariana',         '09123456', '2023-09-01', 'Early Years & Child Development',       'BA (Hons) Early Childhood Education'),
('TT-1003', 'Bennett', 'Oliver',       'oliver.bennett@tinytrack.tn',   '', 'educateur', '+216 22 333 444', '1988-09-30', '7 Oak Lane, Tunis',            '07654321', '2022-03-01', 'Physical Education & Outdoor Learning', 'MSc Sport & Child Development'),
('TT-2001', 'Whitmore', 'Catherine',   'catherine.whitmore@gmail.com',  '', 'parent',    '+216 55 100 200', NULL,         NULL,                          NULL,       NULL,         NULL,                                    NULL),
('TT-2002', 'Thornton', 'James',       'james.thornton@gmail.com',      '', 'parent',    '+216 55 300 400', NULL,         NULL,                          NULL,       NULL,         NULL,                                    NULL),
('TT-2003', 'Pemberton', 'Sophie',     'sophie.pemberton@gmail.com',    '', 'parent',    '+216 55 500 600', NULL,         NULL,                          NULL,       NULL,         NULL,                                    NULL);

-- ================================================
-- SAMPLE DATA : Groups
-- ================================================

INSERT INTO groupe (nom, niveau, capacite, educateur_id) VALUES
('The Robins', 'petit', 15, 2),
('The Owls',   'moyen', 20, 3),
('The Hawks',  'grand', 20, 2);

-- ================================================
-- SAMPLE DATA : Children
-- ================================================

INSERT INTO enfant (code_unique, nom, prenom, date_naissance, sexe, groupe_id, parent_id, date_inscription, statut) VALUES
('TT-3001', 'Whitmore', 'Alfie',  '2022-06-14', 'M', 1, 4, '2025-09-01', 'actif'),
('TT-3002', 'Thornton', 'Isla',   '2021-11-03', 'F', 2, 5, '2025-09-01', 'actif'),
('TT-3003', 'Pemberton', 'Theo',  '2022-02-28', 'M', 2, 6, '2025-09-15', 'actif'),
('TT-3004', 'Whitmore', 'George', '2023-04-19', 'M', 1, 4, '2026-01-10', 'actif'),
('TT-3005', 'Thornton', 'Poppy',  '2020-08-07', 'F', 3, 5, '2025-09-01', 'actif');

-- ================================================
-- SAMPLE DATA : Medical Records
-- ================================================

INSERT INTO dossier_medical (enfant_id, groupe_sanguin, allergies, maladies_chroniques, vaccinations, medecin_traitant, telephone_urgence, notes) VALUES
(1, 'A+',  'Tree nuts',  '',             'BCG, DTaP, MMR',              'Dr. Eleanor Shaw',  '+216 71 111 222', 'Carries an EpiPen at all times — please ensure it remains accessible.'),
(2, 'O+',  'None',       '',             'BCG, DTaP, MMR, Hepatitis B', 'Dr. Marcus Reid',   '+216 71 333 444', ''),
(3, 'B+',  'Dairy',      'Mild asthma',  'BCG, DTaP',                   'Dr. Fiona Caldwell', '+216 71 555 666', 'Inhaler stored in child''s bag. Please monitor during physical activity.'),
(4, 'AB-', 'None',       '',             'BCG, DTaP, MMR',              'Dr. Eleanor Shaw',  '+216 71 111 222', '');

-- ================================================
-- SAMPLE DATA : Conversations
-- ================================================

INSERT INTO conversation (parent_id, staff_id, status, created_at) VALUES
(4, 2, 'open',   '2026-04-10 08:00:00'),
(5, 3, 'open',   '2026-04-11 09:00:00'),
(6, 1, 'closed', '2026-04-08 10:00:00');

-- ================================================
-- SAMPLE DATA : Messages
-- ================================================

INSERT INTO message (conversation_id, sender_id, sender_role, body, read_at, created_at) VALUES
(1, 4, 'parent',    'Good morning. I wanted to check whether Alfie settled in well this week — he seemed a little anxious on Monday morning.', NULL,                  '2026-04-10 08:15:00'),
(1, 2, 'educateur', 'Good morning, Mrs Whitmore. Alfie had a wonderful week overall. He was a little quiet on Monday but was fully engaged by mid-morning. He particularly enjoyed our nature walk on Wednesday.', '2026-04-10 09:28:00', '2026-04-10 09:30:00'),
(1, 4, 'parent',    'That is reassuring to hear — thank you, Miss Hartley. Please do let me know if anything changes.', '2026-04-10 10:05:00', '2026-04-10 10:00:00'),
(2, 5, 'parent',    'Hello. I noticed Isla mentioned she was tired after her session yesterday. Is everything all right?', NULL,                  '2026-04-11 09:10:00'),
(2, 3, 'educateur', 'Hello, Mr Thornton. Isla was absolutely fine — we had an active outdoor session in the afternoon, which may well account for the tiredness. She was in excellent spirits throughout.', NULL,                  '2026-04-11 09:45:00');

-- ================================================
-- SAMPLE DATA : Events
-- ================================================

INSERT INTO evenement (titre, description, date, heure_debut, heure_fin, type, lieu, capacite_max, prix, statut) VALUES
('Spring Fayre',               'An annual outdoor celebration welcoming the season with games, music, and refreshments for all the family.',     '2026-04-25', '09:00', '12:00', 'festival',   'TinyTrack Garden, Ariana',            50, 0.00, 'planifie'),
('Creative Arts Workshop',     'A hands-on painting and craft session designed to encourage self-expression in young children.',                  '2026-04-22', '14:00', '16:00', 'atelier',    'Creative Room, TinyTrack',            20, 5.00, 'planifie'),
('Junior Sports Morning',      'A fun-filled morning of age-appropriate team activities and mini-Olympics for children aged 3 to 6.',             '2026-05-03', '08:30', '11:00', 'sport',      'Sports Ground, Ariana',               40, 0.00, 'planifie'),
('Parent Information Evening', 'An informative session covering the nursery curriculum, safeguarding policy, and upcoming term highlights.',     '2026-04-15', '18:00', '20:00', 'conference', 'Meeting Room, TinyTrack',             30, 0.00, 'termine');

-- ================================================
-- SAMPLE DATA : Reservations
-- ================================================

INSERT INTO reservation (evenement_id, enfant_id, parent_id, nb_accompagnants, commentaire, statut, paiement) VALUES
(1, 1, 4, 2, 'We will be attending with both sets of grandparents.',         'confirmee',  'paye'),
(1, 2, 5, 1, '',                                                             'confirmee',  'paye'),
(2, 3, 6, 0, 'Theo is particularly enthusiastic about painting.',            'confirmee',  'paye'),
(3, 5, 5, 1, 'Poppy has a mild nut allergy — please note for refreshments.', 'en_attente', 'non_paye');

-- ================================================
-- SAMPLE DATA : Activities
-- ================================================

INSERT INTO activite (nom_activite, description, date_activite, heure_activite, id_educateur) VALUES
('Finger Painting',        'Free expression painting session using child-safe, washable paints.',       '2026-04-10', '09:30', 2),
('Outdoor Nature Walk',    'Guided walk around the nursery garden to observe seasonal changes.',        '2026-04-10', '10:30', 2),
('Interactive Storytime',  'Read-aloud session using puppets to encourage language development.',       '2026-04-11', '09:00', 3),
('Construction & Building','Collaborative play with building blocks to develop spatial awareness.',     '2026-04-12', '09:30', 3);

-- ================================================
-- SAMPLE DATA : Reports
-- ================================================

INSERT INTO rapport (contenu_rapport, date_rapport, id_activite, id_educateur) VALUES
('Alfie demonstrated excellent concentration and showed a strong creative instinct throughout the painting session.',                              '2026-04-10', 1, 2),
('Isla was highly engaged during the nature walk, asking perceptive questions about the plants and insects observed.',                            '2026-04-10', 2, 2),
('Theo listened attentively during storytime and volunteered to retell part of the story unprompted — a very positive sign.',                     '2026-04-11', 3, 3),
('George and Alfie worked collaboratively on the block construction, demonstrating good turn-taking and problem-solving skills.',                 '2026-04-12', 4, 3);
