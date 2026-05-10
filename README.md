# PI_3A43 - MindCare Platform

Application web de suivi et d'accompagnement psychologique pour etudiants, construite avec Symfony.

## General Project Description

This project was developed as part of the PIDEV - 3rd Year Engineering Program at Esprit School of Engineering (Academic Year 2025-2026).

PI_3A43 (MindCare Platform) is a web application designed to support student mental health follow-up through appointment management, patient files, AI-assisted moderation and insights, and online consultation integration.

## Description du projet

MindCare centralise les besoins d'un cabinet universitaire de psychologie:

- gestion des comptes et des roles (etudiant, psychologue, admin)
- prise de rendez-vous (cabinet / en ligne)
- suivi via dossier patient
- moderation IA des commentaires
- statistiques d'usage (Google Analytics 4)
- integration Zoom pour les rendez-vous en ligne

Ce depot sert de base pour un atelier de developpement, de collaboration GitHub et de standardisation de livrables.

## Objectifs de l'atelier

- comprendre l'architecture d'un projet Symfony full-stack
- executer le projet en local avec Docker ou environnement natif
- manipuler les flux metier principaux (auth, rendez-vous, dossiers)
- configurer des integrations externes (GA4, Zoom)
- appliquer des bonnes pratiques GitHub (README, issues, PR, topics)

## Stack technique

- Backend: PHP 8.1+ / Symfony
- Frontend: Twig, JS, CSS, Webpack Encore / Asset Mapper
- Base de donnees: MySQL / MariaDB
- Conteneurisation: Docker Compose
- Services externes: Zoom API, Google Analytics Data API, OpenAI Moderation

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

## Installation rapide

1. Cloner le depot

```bash
git clone <repo_url>
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

## Donnees de test

- Script de population: `populate_database.sql`
- Guide: `DATABASE_IMPORT_GUIDE.md`

## Documentation complementaire

- Installation detaillee: `README_INSTALL.md`
- Zoom quick start: `ZOOM_QUICK_START.md`
- Zoom integration guide: `ZOOM_INTEGRATION_GUIDE.md`

## Cas d'usage a demontrer pendant l'atelier

- inscription / connexion utilisateur
- creation et validation d'un rendez-vous
- affichage d'un dossier patient
- generation d'insights IA
- creation auto d'un lien Zoom sur un rendez-vous en ligne
- visualisation des statistiques administrateur

## Standardisation GitHub (atelier)

- Branches: `main`, `develop`, `feature/*`, `fix/*`
- Commits: convention claire (ex: `feat:`, `fix:`, `docs:`)
- Pull Requests: template avec contexte, captures, tests effectues
- Issues: labels (`bug`, `enhancement`, `documentation`, `question`)
- Protection de branche: review obligatoire + checks CI

## Proposition de structure README (reference)

1. Titre + slogan projet
2. Description metier
3. Objectifs
4. Stack technique
5. Architecture / arborescence
6. Installation et configuration
7. Utilisation (scenarios)
8. Donnees de test
9. Qualite (tests, lint, CI)
10. Workflow GitHub
11. Equipe et contributions
12. Licence

## Equipe

Renseigner ici:

- nom des membres
- roles
- liens GitHub

## Licence

A definir (MIT, Apache-2.0, ou licence ecole/interne).
