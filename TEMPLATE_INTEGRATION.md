# Guide d'Intégration des Templates - MoodTracker

## 📋 Aperçu

Ce projet a été intégré avec deux templates complets :
- **Template Front-End** : Pour les pages publiques et le tableau de bord utilisateur
- **Template Admin** : Pour les pages d'administration

## 🏗️ Structure des Répertoires

```
templates/
├── base_front.html.twig          # Layout principal du front-end
├── admin/
│   ├── base_admin.html.twig      # Layout principal de l'admin
│   ├── dashboard.html.twig       # Dashboard admin
│   ├── users.html.twig           # Gestion des utilisateurs
│   ├── reports.html.twig         # Rapports
│   └── settings.html.twig        # Paramètres
└── front/
    ├── home.html.twig            # Accueil
    ├── about.html.twig           # À propos
    ├── blog.html.twig            # Blog
    ├── contact.html.twig         # Contact
    ├── events.html.twig          # Événements
    ├── dashboard.html.twig       # Tableau de bord utilisateur
    └── ...

src/Controller/
├── FrontController.php           # Contrôleur des pages publiques
└── AdminController.php           # Contrôleur de l'administration
```

## 🎨 Intégration des Assets

Les assets (CSS, JavaScript, images) doivent être copiés dans les répertoires correspondants :

### Front-End Assets

Copiez tous les fichiers du template front vers :
```
public/front_assets/
├── css/
│   ├── bootstrap.min.css
│   ├── style.css
│   └── ...
├── js/
│   ├── bootstrap.min.js
│   ├── main.js
│   └── ...
└── img/
    ├── banner/
    ├── blog/
    └── ...
```

**Depuis :** `templates/templates/front/`

### Admin Assets

Copiez tous les fichiers du template admin vers :
```
public/admin_assets/
├── css/
│   ├── bootstrap.min.css
│   ├── theme.min.css
│   └── ...
├── js/
│   ├── bootstrap.bundle.min.js
│   ├── theme.js
│   └── ...
├── vendors/
│   ├── css/
│   └── js/
└── images/
    └── ...
```

**Depuis :** `templates/templates/back/assets/`

## 🚀 Routes Disponibles

### Pages Frontend
- `/` → Accueil
- `/about` → À propos
- `/blog` → Blog
- `/events` → Événements
- `/contact` → Contact
- `/dashboard` → Mon Tableau de Bord (authentifié)

### Pages Admin
- `/admin/` → Tableau de Bord Admin
- `/admin/users` → Gestion des Utilisateurs
- `/admin/reports` → Rapports
- `/admin/settings` → Paramètres

## 📦 Installation

### 1. Copier les assets (CSS, JS, images)

**Front-End :**
```bash
# Depuis le répertoire du projet
cp -r templates/templates/front/css public/front_assets/
cp -r templates/templates/front/js public/front_assets/
cp -r templates/templates/front/img public/front_assets/
cp -r templates/templates/front/fonts public/front_assets/
cp -r templates/templates/front/scss public/front_assets/
```

**Admin :**
```bash
cp -r templates/templates/back/assets/* public/admin_assets/
```

### 2. Mettre à jour les routes (si nécessaire)

Les contrôleurs `FrontController` et `AdminController` sont déjà configurés. Vérifiez les namespaces et les routes.

### 3. Configurer la sécurité

Assurez-vous que l'admin est protégé. Dans `config/packages/security.yaml` :

```yaml
security:
    role_hierarchy:
        ROLE_ADMIN: [ROLE_USER]
    
    access_control:
        - { path: ^/admin, roles: ROLE_ADMIN }
        - { path: ^/dashboard, roles: IS_AUTHENTICATED_FULLY }
```

### 4. Customiser les templates

Les templates utilisent des variables Symfony :
- `{{ app.user }}` - Utilisateur actuellement connecté
- `{{ path('route_name') }}` - URLs des routes
- `{{ asset('path') }}` - Chemin des assets

## 🎯 Étapes Suivantes

1. **Copier les assets** des templates originaux
2. **Tester les routes** et vérifier l'affichage
3. **Adapter les formulaires** aux models Doctrine
4. **Intégrer la base de données** avec les Entity/Repository existants
5. **Customiser le CSS** selon vos besoins
6. **Ajouter les contrôleurs de logique métier**

## 📝 Notes Importantes

- Tous les chemins d'assets utilisent la fonction `asset()` de Symfony
- Les templates héritent correctement de `base_front.html.twig` ou `base_admin.html.twig`
- Les routes sont configurées avec les préfixes et noms appropriés
- Les sauvegardes des templates originaux restent dans `templates/templates/`

## ⚙️ Configuration Supplémentaire

### Pour modifier le domaine du site
Mettez à jour dans les templates :
- Logo et favicon
- URLs des réseaux sociaux
- Informations de contact
- Textes et contenu

### Pour ajouter de nouvelles pages
1. Créez un fichier `templates/front/nouvelle-page.html.twig`
2. Ajoutez une route dans `FrontController`
3. Ajoutez le lien dans le menu de `base_front.html.twig`

## 🆘 Support

Pour des questions spécifiques sur :
- **Templates :** Consultez les fichiers HTML originaux dans `templates/templates/`
- **Symfony :** Consultez la [documentation Symfony](https://symfony.com/doc)
- **Bootstrap :** Consultez la [documentation Bootstrap](https://getbootstrap.com/)
