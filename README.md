# TinyTrack – Plateforme de Digitalisation d'un Jardin d'Enfants

> *"Parce que chaque enfant mérite transparence, sécurité et suivi."*

![Esprit School of Engineering](https://img.shields.io/badge/Esprit%20School%20of%20Engineering-Tunisia-green)
![Academic Year](https://img.shields.io/badge/Academic%20Year-2025--2026-blue)
![PHP](https://img.shields.io/badge/Backend-PHP-777BB4)
![MySQL](https://img.shields.io/badge/Database-MySQL-4479A1)
![JavaScript](https://img.shields.io/badge/Frontend-JavaScript-F7DF1E)

---

## Table des Matières

- [Overview](#overview)
- [Problématique](#problématique)
- [Features](#features)
- [Tech Stack](#tech-stack)
- [Architecture](#architecture)
- [Getting Started](#getting-started)
- [Contributors](#contributors)
- [Academic Context](#academic-context)
- [Acknowledgments](#acknowledgments)

---

## Overview

**TinyTrack** est une application web full-stack dédiée à la digitalisation complète d'un jardin d'enfants / crèche en Tunisie.

Elle centralise la gestion des enfants, des présences, des activités quotidiennes et de la communication entre l'établissement et les parents — offrant transparence, traçabilité et sécurité à toutes les parties prenantes.

Developed at **Esprit School of Engineering** – Tunisia, as part of the **Projet Technologies Web (PW)** module, Academic Year 2025–2026.

---

## Problématique

En Tunisie, la majorité des jardins d'enfants fonctionnent encore de manière entièrement manuelle :

- Les parents **ne savent pas** ce que fait leur enfant pendant la journée
- Les **présences** sont gérées sur des cahiers papier
- La **communication** crèche ↔ parents est archaïque (appels, cahiers de liaison)
- Aucun **suivi du développement** de l'enfant n'est disponible
- Les **incidents** ne sont pas tracés ni signalés efficacement

Suite aux scandales récents dans des établissements tunisiens, la question de la **sécurité et de la transparence** est devenue une priorité absolue pour les familles.

**TinyTrack** répond à cette problématique en digitalisant entièrement la gestion de la crèche et en offrant aux parents une **visibilité en temps réel** sur la vie de leur enfant.

---

## Features

- **Gestion des enfants & dossiers** — Inscription, suivi médical, archivage des dossiers
- **Gestion des présences** — Pointage quotidien, historique, alertes d'absence
- **Journal de bord quotidien** — Activités, repas, sieste, humeur de l'enfant
- **Communication parents** — Messagerie interne, notifications, annonces
- **Gestion du personnel** — Fiches employés, planning, affectations
- **Dashboard administratif** — Statistiques globales, rapports, gestion de l'établissement

---

## Tech Stack

### Frontend
- HTML5 / CSS3
- JavaScript (Vanilla JS)

### Backend
- PHP (développement côté serveur)

### Base de Données
- MySQL

### Outils
- XAMPP / WAMP
- phpMyAdmin
- Git & GitHub

---

## Architecture

```
TinyTrack/
├── index.php                        # Point d'entrée principal
├── assets/
│   ├── css/                         # Feuilles de style
│   ├── js/                          # Scripts JavaScript
│   └── images/                      # Ressources visuelles
├── pages/
│   ├── enfants.php                  # Gestion des enfants & dossiers
│   ├── presences.php                # Gestion des présences
│   ├── journal.php                  # Journal de bord quotidien
│   ├── communication.php            # Messagerie & notifications
│   ├── personnel.php                # Gestion du personnel
│   └── dashboard.php                # Dashboard admin & statistiques
├── includes/                        # Composants réutilisables (header, footer, navbar)
├── config/
│   └── db.php                       # Configuration base de données
├── controllers/                     # Logique métier
├── models/                          # Interaction avec la base de données
└── README.md
```

---

## Getting Started

### Prérequis

- XAMPP ou WAMP
- PHP 7.4+
- MySQL 5.7+
- Navigateur web moderne

### Installation

1. **Clonez le repository :**

```bash
git clone https://github.com/eyabelhaj616/Esprit-PW-2A19-2526-TinyTrack.git
cd TinyTrack
```

2. **Configurez l'environnement local :**

   - Placez le projet dans le dossier `www` (WAMP) ou `htdocs` (XAMPP)
   - Démarrez Apache et MySQL depuis l'interface WAMP/XAMPP

3. **Créez la base de données :**

   - Ouvrez phpMyAdmin via `http://localhost/phpmyadmin`
   - Créez une base de données nommée `tinytrack`
   - Importez le fichier `database/tinytrack.sql`

4. **Configurez la connexion :**

   Ouvrez `config/db.php` et renseignez vos identifiants MySQL :

```php
$host = "localhost";
$db   = "tinytrack";
$user = "root";
$pass = "";
```

5. **Accédez à l'application :**

```
http://localhost/TinyTrack
```

---

## Contributors

Ce projet a été réalisé par :

| Nom | Module | Entités |
|---|---|---|
| Belhaj Mabrouk Eya | Gestion des enfants & inscriptions | Enfant ↔ Dossier |
| Ajili Rayen | Gestion des présences | Présence ↔ Enfant |
| Fadhlaoui Mohamed | Journal de bord quotidien | Activité ↔ Rapport |
| Rajhi Amen Allah | Communication parents | Message ↔ Notification |
| Ben Khalifa Youssef | Gestion du personnel | Employé ↔ Planning |
| Ben Slimen Mahdi | Dashboard admin & statistiques | Statistique ↔ Établissement |

---

## Academic Context

Developed at **Esprit School of Engineering** – Tunisia

- **Module :** Projet Technologies Web (PW) – 2A19
- **Année Universitaire :** 2025–2026
- **Encadrante :** Oumeima IBN ELFEKIH

---

## Acknowledgments

Nous remercions notre encadrante **Mme Oumeima IBN ELFEKIH** pour son accompagnement tout au long de ce projet, ainsi qu'**Esprit School of Engineering** pour les ressources pédagogiques mises à notre disposition.

---

© 2026 TinyTrack – Esprit School of Engineering

