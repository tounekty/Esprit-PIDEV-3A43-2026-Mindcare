✅ CHECKLIST D'INTÉGRATION - MOODTRACKER

═══════════════════════════════════════════════════════════════

## PHASE 1: PRÉPARATION (✅ COMPLÉTÉE AUTOMATIQUEMENT)
  
  ✅ Structures Twig créées
  ✅ Templates front générés
  ✅ Templates admin générés
  ✅ Contrôleurs Symfony créés
  ✅ Répertoires d'assets préparés
  ✅ Documentation générée

═══════════════════════════════════════════════════════════════

## PHASE 2: COPIE DES ASSETS (À FAIRE MANUELLEMENT)

### Option A: Exécuter le script (Recommandé)
  ⭕ Double-cliquer sur: copy_assets.bat
     (Cela copiera tous les assets automatiquement)

### Option B: Copie manuelle
  ⭕ CSS Front-end
     Source: templates/templates/front/css/*
     Destination: public/front_assets/css/
  
  ⭕ JavaScript Front-end
     Source: templates/templates/front/js/*
     Destination: public/front_assets/js/
  
  ⭕ Images Front-end
     Source: templates/templates/front/img/*
     Destination: public/front_assets/img/
  
  ⭕ Polices Front-end (si présentes)
     Source: templates/templates/front/fonts/*
     Destination: public/front_assets/fonts/
  
  ⭕ Assets Admin (tous les fichiers)
     Source: templates/templates/back/assets/*
     Destination: public/admin_assets/

═══════════════════════════════════════════════════════════════

## PHASE 3: TEST DES ROUTES

### Lancer le serveur Symfony
  ⭕ Exécuter: php bin/console server:run
     ou: symfony server:start
     Serveur: http://localhost:8000

### Tester les routes Front-end
  ⭕ Accueil: http://localhost:8000/
  ⭕ À propos: http://localhost:8000/about
  ⭕ Blog: http://localhost:8000/blog
  ⭕ Événements: http://localhost:8000/events
  ⭕ Contact: http://localhost:8000/contact
  ⭕ Tableau de bord: http://localhost:8000/dashboard
     (Nécessite l'authentification)

### Tester les routes Admin
  ⭕ Dashboard: http://localhost:8000/admin
     (Nécessite le rôle ROLE_ADMIN)
  ⭕ Utilisateurs: http://localhost:8000/admin/users
  ⭕ Rapports: http://localhost:8000/admin/reports
  ⭕ Paramètres: http://localhost:8000/admin/settings

═══════════════════════════════════════════════════════════════

## PHASE 4: CONFIGURATION DE LA SÉCURITÉ

### Configurer les contrôles d'accès
  ⭕ Ouvrir: config/packages/security.yaml
  
  ⭕ Ajouter après "access_control:":
  ```yaml
  access_control:
      - { path: ^/admin, roles: ROLE_ADMIN }
      - { path: ^/dashboard, roles: IS_AUTHENTICATED_FULLY }
  ```

### Configurer les rôles
  ⭕ Vérifier les rôles dans la classe User
  ⭕ S'assurer que ROLE_ADMIN est assigné correctement

═══════════════════════════════════════════════════════════════

## PHASE 5: CUSTOMISATION

### Mettre à jour les informations du site
  ⭕ base_front.html.twig
     - Logo et favicon
     - Nom du site
     - Liens sociaux
     - Informations de contact
  
  ⭕ admin/base_admin.html.twig
     - Logo admin
     - Nom de l'application
     - Information de profil

### Remplacer les images
  ⭕ Bannières (public/front_assets/img/banner/)
  ⭕ Articles blog (public/front_assets/img/blog/)
  ⭕ Équipe (public/front_assets/img/team/)
  ⭕ Événements (public/front_assets/img/events/)
  ⭕ Logo admin (public/admin_assets/images/)

### Mettre à jour le contenu
  ⭕ Textes de la page d'accueil
  ⭕ Contenu de "À propos"
  ⭕ Articles de blog
  ⭕ Événements
  ⭕ Information de contact

═══════════════════════════════════════════════════════════════

## PHASE 6: INTÉGRATION AVEC LA BASE DE DONNÉES

### Connecter les entités
  ⭕ Mettre à jour les contrôleurs pour utiliser les Entity
  
  Exemple pour FrontController:
  ```php
  public function __construct(private UserRepository $userRepo) {}
  
  #[Route('/')]
  public function home(): Response {
      $users = $this->userRepo->findAll();
      return $this->render('front/home.html.twig', [
          'users' => $users,
      ]);
  }
  ```

### Connecter les formulaires
  ⭕ Créer des Form Types si nécessaire
  ⭕ Intégrer les validations
  ⭕ Tester l'envoi de données

### Créer les services
  ⭕ Services métier pour les calculs d'humeur
  ⭕ Services email pour les notifications
  ⭕ Services de rapports

═══════════════════════════════════════════════════════════════

## PHASE 7: AJOUT DE FONCTIONNALITÉS

### Pages Front-end à compléter
  ⭕ Formulaire de contact fonctionnel
  ⭕ Inscription utilisateur
  ⭕ Connexion
  ⭕ Récupération de mot de passe
  ⭕ Profil utilisateur

### Dashboard utilisateur
  ⭕ Afficher les données réelles
  ⭕ Graphiques des émotions
  ⭕ Historique des entrées
  ⭕ Prise de rendez-vous

### Fonctionnalités Admin
  ⭕ CRUD Utilisateurs
  ⭕ CRUD Psychologues
  ⭕ Génération de rapports réels
  ⭕ Gestion des paramètres fonctionnels

═══════════════════════════════════════════════════════════════

## PHASE 8: DÉPLOIEMENT

### Avant le déploiement
  ⭕ Vérifier les fichiers .env
  ⭕ Configurer la base de données production
  ⭕ Activer le mode HTTPS
  ⭕ Configurer les emails
  ⭕ Tester toutes les routes

### Générer les assets production
  ⭕ php bin/console assets:install

### Vérifier les permissions
  ⭕ var/cache/ doit être writable
  ⭕ var/log/ doit être writable
  ⭕ public/front_assets/ accessible en lecture

═══════════════════════════════════════════════════════════════

## POINTS D'ATTAQUE COURANTS

### Les images ne s'affichent pas?
  ⭕ Vérifier les chemins avec {{ asset() }}
  ⭕ Vérifier que les fichiers existent
  ⭕ Vérifier la structure des répertoires
  ⭕ Purger le cache navigateur

### Les styles CSS ne s'appliquent pas?
  ⭕ Vérifier le chemin du CSS
  ⭕ Vérifier que le fichier existe
  ⭕ Vérifier la syntaxe du fichier CSS
  ⭕ Vérifier que font-awesome est bien chargé

### Les routes ne fonctionnent pas?
  ⭕ Vérifier que le contrôleur existe
  ⭕ Vérifier la syntaxe des attributs #[Route]
  ⭕ Vérifier les imports utilisé (use)
  ⭕ Vérifier que le serveur est lancé

### L'accès admin est bloqué?
  ⭕ Vérifier que l'utilisateur a ROLE_ADMIN
  ⭕ Vérifier la configuration security.yaml
  ⭕ Vérifier que l'utilisateur est authentifié

═══════════════════════════════════════════════════════════════

## DOCUMENTATION À CONSULTER

✅ TEMPLATE_INTEGRATION.md ← Guide complet
✅ ASSETS_GUIDE.md ← Gestion des assets
✅ INTEGRATION_SUMMARY.txt ← Résumé de l'intégration
✅ Cette checklist

═══════════════════════════════════════════════════════════════

## FEUILLE DE ROUTE RECOMMANDÉE

Jour 1: Copie des assets + test des routes

Jour 2: Customisation du contenu + mise à jour des images

Jour 3: Intégration avec la base de données

Jour 4+: Développement des fonctionnalités spécifiques

═══════════════════════════════════════════════════════════════

Bonne chance avec votre intégration! 🚀

Si vous avez besoin d'aide:
1. Consultez les documentations
2. Vérifiez la structure des fichiers
3. Inspectez la console du navigateur (F12)
4. Vérifiez les logs Symfony (var/log/)
