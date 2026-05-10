# 🧠 MindCare - Plateforme de Gestion Médicale

---

## 📌 Description

MindCare est une application JavaFX desktop complète pour la gestion des rendez-vous médicaux, dossiers patients et ressources éducatives.

---

## Description metier

MindCare centralise les besoins d'un cabinet universitaire de psychologie:

- gestion des comptes et des roles (etudiant, psychologue, admin)
- prise de rendez-vous (cabinet / en ligne)
- suivi via dossier patient
- moderation IA des commentaires
- statistiques d'usage
- integration Zoom pour les rendez-vous en ligne

---

## 🚀 Technologies Utilisées

- ☕ symfony
- 🎨 docker
- 🗄️ MySQL
- 📦 twig
- 🔐 BCrypt
- 📧 SMTP
- 🎥 Zoom API
- 🤖 Ollama AI

---

## 🎯 Fonctionnalités

### 👤 Client
- ✅ Prendre un rendez-vous
- ✅ Voir ses rendez-vous
- ✅ Gérer son dossier patient

### 🏥 Psychologue
- ✅ Accepter ou refuser des rendez-vous
- ✅ Consulter les dossiers patients
- ✅ Voir les statistiques

### 🔑 Administrateur
- ✅ Gestion des utilisateurs
- ✅ Dashboard global
- ✅ Gestion complète du système

---

## Structure du depot

```text
assets/          Frontend assets (JS, CSS)
bin/             Commandes Symfony (console, phpunit)
config/          Configuration framework, services, routes
migrations/      Migrations Doctrine
public/          Point d'entree web + assets publics
src/             Code applicatif (Controller, Entity, Service, etc.)
templates/       Vues Twig
tests/           Tests unitaires et fonctionnels
var/             Cache et logs
vendor/          Dependances Composer
```

---

## Installation rapide

1. Cloner le depot

```bash
git clone https://github.com/tounekty/Esprit-PIDEV-3A43-2026-Mindcare.git
cd PI_3A43
```

2. Installer les dependances

```bash
composer install
```

3. Configurer l'environnement

```bash
cp .env .env.local
```

Renseigner au minimum:

- `APP_ENV`
- `APP_SECRET`
- `DATABASE_URL`
- `OPENAI_API_KEY` (si moderation active)
- `GA4_*` (si dashboard analytics actif)
- `ZOOM_*` (si rendez-vous en ligne actifs)

4. Initialiser la base

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
```

5. Lancer l'application

```bash
symfony serve -d
```

Application disponible sur `http://127.0.0.1:8000/home`.

---

## Donnees de test

- Script de population: `populate_database.sql`
- Guide: `DATABASE_IMPORT_GUIDE.md`

---

## Equipe et contributions

- Tounekty Haythem
- roles: Chef de projet
- liens GitHub: https://github.com/tounekty
- --------------------------
- Ben Brahim Mohamed Aziz
- roles: Dev backend / base de donnees
- liens GitHub: https://github.com/aziz98798465
- --------------------------
- Ahmed Omri
- roles: Dev frontend JavaFX / UI
- liens GitHub: https://github.com/ahmedomridev
- --------------------------
- Abdellaoui Nader
- roles: Dev services / integration API
- liens GitHub: https://github.com/nader0abdellaoui
- --------------------------
- Nawress Hichri
- roles:QA / tests et validation
- liens GitHub: https://github.com/nawress01
- --------------------------
- Laarousi Sarah
- roles: Documentation / support
- liens GitHub: https://github.com/sarahlaroussi

---

## 📜 Licence

Projet académique ESPRIT — PIDEV 2026
