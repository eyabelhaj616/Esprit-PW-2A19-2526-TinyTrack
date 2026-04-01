# TinyTrack – Plateforme de Digitalisation d'un Jardin d'Enfants

> *"Chaque petit pas compte."*

![Esprit School of Engineering](https://img.shields.io/badge/Esprit%20School%20of%20Engineering-Tunisia-green)
![Academic Year](https://img.shields.io/badge/Annee%20Universitaire-2025--2026-blue)
![PHP](https://img.shields.io/badge/Backend-PHP-777BB4)
![MySQL](https://img.shields.io/badge/Database-MySQL-4479A1)
![JavaScript](https://img.shields.io/badge/Frontend-JavaScript-F7DF1E)

---

## Table des Matieres

- [Apercu](#apercu)
- [Problematique](#problematique)
- [Fonctionnalites](#fonctionnalites)
- [Stack Technique](#stack-technique)
- [Architecture](#architecture)
- [Installation](#installation)
- [Equipe](#equipe)
- [Contexte Academique](#contexte-academique)
- [Remerciements](#remerciements)

---

## Apercu

**TinyTrack** est une application web full-stack dediee a la digitalisation complete d'un jardin d'enfants / creche en Tunisie.

Elle centralise la gestion des enfants, des presences, des activites quotidiennes et de la communication entre l'etablissement et les parents — offrant transparence, tracabilite et securite a toutes les parties prenantes.

Projet developpe a **Esprit School of Engineering** – Tunisie, dans le cadre du module **Projet Technologies Web (PW)**, Classe 2A19, Annee Universitaire 2025–2026.

---

## Problematique

En Tunisie, la majorite des jardins d'enfants fonctionnent encore de maniere entierement manuelle :

- **2 800+** jardins d'enfants en Tunisie
- **73%** gerent encore avec du papier
- **82%** des parents veulent un suivi digital

Les defis concrets :

- Les parents **ne savent pas** ce que fait leur enfant pendant la journee
- Les **presences** sont gerees sur des cahiers papier
- La **communication** creche ↔ parents est archaique (appels, cahiers de liaison)
- Les **dossiers medicaux** sont sur papier : risque d'erreur en cas d'urgence
- Les **incidents** ne sont pas traces ni signales efficacement
- La **facturation** est manuelle et sujette aux erreurs

Suite aux scandales recents dans des etablissements tunisiens, la question de la **securite et de la transparence** est devenue une priorite absolue pour les familles.

**TinyTrack** repond a cette problematique en digitalisant entierement la gestion de la creche et en offrant aux parents une **visibilite en temps reel** sur la vie de leur enfant.

---

## Fonctionnalites

| Module | Description |
|--------|-------------|
| Gestion des enfants & inscriptions | Inscription, fiche enfant, dossier medical, archivage |
| Gestion des presences & justificatifs | Pointage quotidien, historique, alertes d'absence, justificatifs |
| Journal de bord quotidien | Activites, repas, sieste, humeur de l'enfant |
| Communication parents | Messagerie interne, notifications, annonces, alertes urgence |
| Gestion du personnel | Fiches employes, planning, affectations aux groupes |
| Facturation & Dashboard | Factures mensuelles, paiements, statistiques globales, rapports |

---

## Stack Technique

### Frontend
- HTML5 / CSS3
- JavaScript (Vanilla JS)
- Template Front Office : **Kider** (Bootstrap 5)
- Template Back Office : **AdminLTE**

### Backend
- PHP (developpement cote serveur)

### Base de Donnees
- MySQL (14 tables, 12 relations FK)

### Outils
- XAMPP / WAMP
- phpMyAdmin
- Git & GitHub

---

## Architecture

```
TinyTrack/
├── index.php                        # Point d'entree principal
├── assets/
│   ├── css/                         # Feuilles de style
│   ├── js/                          # Scripts JavaScript
│   └── images/                      # Ressources visuelles
├── pages/
│   ├── enfants.php                  # Gestion des enfants & dossiers
│   ├── presences.php                # Gestion des presences & justificatifs
│   ├── journal.php                  # Journal de bord quotidien
│   ├── communication.php            # Messagerie & notifications
│   ├── personnel.php                # Gestion du personnel & planning
│   └── dashboard.php                # Facturation & dashboard admin
├── includes/                        # Composants reutilisables (header, footer, navbar)
├── config/
│   └── db.php                       # Configuration base de donnees
├── controllers/                     # Logique metier
├── models/                          # Interaction avec la base de donnees
└── README.md
```

---

## Installation

### Prerequis

- XAMPP ou WAMP
- PHP 7.4+
- MySQL 5.7+
- Navigateur web moderne

### Etapes

1. **Clonez le repository :**

```bash
git clone https://github.com/eyabelhaj616/Esprit-PW-2A19-2526-TinyTrack.git
cd TinyTrack
```

2. **Configurez l'environnement local :**

   - Placez le projet dans le dossier `www` (WAMP) ou `htdocs` (XAMPP)
   - Demarrez Apache et MySQL depuis l'interface WAMP/XAMPP

3. **Creez la base de donnees :**

   - Ouvrez phpMyAdmin via `http://localhost/phpmyadmin`
   - Creez une base de donnees nommee `tinytrack`
   - Importez le fichier `database/tinytrack.sql`

4. **Configurez la connexion :**

   Ouvrez `config/db.php` et renseignez vos identifiants MySQL :

```php
$host = "localhost";
$db   = "tinytrack";
$user = "root";
$pass = "";
```

5. **Accedez a l'application :**

```
http://localhost/TinyTrack
```

---

## Equipe

| Membre | Module | Entites (avec jointure) |
|--------|--------|-------------------------|
| Belhaj Mabrouk Eya | Gestion des enfants & inscriptions | Enfant ↔ DossierMedical |
| Ajili Rayen | Gestion des presences & justificatifs | Presence ↔ Justificatif |
| Fadhlaoui Mohamed | Journal de bord quotidien | RapportJournalier ↔ Activite |
| Rajhi Amen Allah | Communication parents | Message ↔ Notification |
| Ben Khalifa Youssef | Gestion du personnel | Employe ↔ Planning |
| Ben Slimene Mahdi | Facturation & Dashboard | Facture ↔ Paiement |

---

## Contexte Academique

Projet developpe a **Esprit School of Engineering** – Tunisie

- **Module :** Projet Technologies Web (PW)
- **Classe :** 2A19
- **Annee Universitaire :** 2025–2026


---



&copy; 2026 TinyTrack – Esprit School of Engineering
