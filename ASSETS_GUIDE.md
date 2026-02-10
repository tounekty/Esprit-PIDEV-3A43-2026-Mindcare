# Gestion des Assets - Guide Pratique

## Vue d'ensemble

Les assets (CSS, JavaScript, images) sont gérés par Symfony et servent depuis le répertoire `public/`.

## Chemins des Assets

### Front-End
```
public/front_assets/
├── css/              # Fichiers CSS
├── js/               # Fichiers JavaScript
├── img/              # Images
├── fonts/            # Polices de caractères
└── scss/             # Fichiers SCSS (si utilisés)
```

### Admin
```
public/admin_assets/
├── css/              # Fichiers CSS
├── js/               # Fichiers JavaScript
├── vendors/          # Bibliothèques externes
│   ├── css/
│   └── js/
├── images/           # Images
└── ...
```

## Utilisation dans les Templates Twig

### Feuilles de style CSS
```twig
<link rel="stylesheet" href="{{ asset('front_assets/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('front_assets/css/style.css') }}">
```

### Scripts JavaScript
```twig
<script src="{{ asset('front_assets/js/bootstrap.min.js') }}"></script>
<script src="{{ asset('front_assets/js/main.js') }}"></script>
```

### Images
```twig
<img src="{{ asset('front_assets/img/banner/hero.jpg') }}" alt="Hero">
<img src="{{ asset('admin_assets/images/logo.png') }}" alt="Logo">
```

## Structure des Liens d'Images

### Images Front-End
```
front_assets/img/
├── banner/           # Images de bannières
├── blog/             # Images des articles
├── events/           # Images des événements
├── team/             # Photos des équipes
├── icons/            # Icônes
└── ...
```

### Favicon
```twig
<!-- Front -->
<link rel="shortcut icon" href="{{ asset('front_assets/img/favicon.png') }}">

<!-- Admin -->
<link rel="shortcut icon" href="{{ asset('admin_assets/images/favicon.ico') }}">
```

## Chemins Relatifs vs. Absolus

### ✅ Correct - Utiliser la fonction asset()
```twig
{# Dans les templates #}
<link rel="stylesheet" href="{{ asset('front_assets/css/style.css') }}">
<img src="{{ asset('front_assets/img/image.jpg') }}" alt="">
<script src="{{ asset('front_assets/js/script.js') }}"></script>
```

### ❌ Incorrect - Chemins relatifs directs
```twig
{# À ÉVITER #}
<link rel="stylesheet" href="front_assets/css/style.css">
<img src="../img/image.jpg" alt="">
<script src="assets/js/script.js"></script>
```

## Organisation des CSS

### Variables et Couleurs
Définis dans les templates ou dans les fichiers CSS:
```css
.btn-primary {
    background-color: var(--primary);
}

.btn-secondary {
    background-color: var(--secondary);
}
```

## Organisation des JavaScript

### jQuery et Bootstrap
```html
<!-- jQuery (si utilisé) -->
<script src="{{ asset('front_assets/js/vendor/jquery-1.12.4.min.js') }}"></script>

<!-- Bootstrap Bundle (inclut Popper) -->
<script src="{{ asset('front_assets/js/bootstrap.bundle.min.js') }}"></script>

<!-- Scripts personnalisés -->
<script src="{{ asset('front_assets/js/main.js') }}"></script>
```

## Commandes Utiles

### Vérifier les assets disponibles
```bash
# Lister la structure de public/front_assets
ls -R public/front_assets/

# Ou sur Windows
dir /s public\front_assets
```

### Nettoyer le cache des assets
```bash
php bin/console cache:clear
```

### Valider les assets
```bash
# Vérifier que les assets sont trouvables
php bin/console assets:install --symlink
```

## Configuration Webpack (si utilisé)

Si vous utilisez Webpack Encore, les assets doivent être compilés d'abord:

```bash
# Installation
npm install

# Développement (avec watch)
npm run dev-watch

# Production
npm run build
```

Mais actuellement, les assets sont servis directement depuis `public/`, pas besoin de compilation.

## Problèmes Courants

### Images qui ne s'affichent pas
**Solution :** Vérifier que:
1. Le fichier existe dans `public/front_assets/img/`
2. Le chemi dans le template est correct: `{{ asset('front_assets/img/...') }}`
3. Les permissions du fichier permettent la lecture

### CSS/JS non chargés
**Solution :** Vérifier que:
1. Le fichier existe dans le bon répertoire
2. La fonction `asset()` est utilisée
3. Le serveur Symfony est en train de tourner
4. Vider le cache du navigateur (Ctrl+F5)

### Chemins 404
**Cause probable :** Typo dans le chemin d'asset
**Solution :** Vérifier les noms exacts des fichiers et dossiers

## Bonnes Pratiques

✅ Toujours utiliser la fonction `asset()` de Symfony
✅ Garder les images optimisées (compress)
✅ Utiliser des noms de fichiers descriptifs
✅ Organiser les assets par type et section
✅ Versionner les assets importants

## Ressources Supplémentaires

- [Symfony Asset Documentation](https://symfony.com/doc/current/reference/configuration/framework.html#assets)
- [Bootstrap Documentation](https://getbootstrap.com/)
- [Web Asset Management](https://symfony.com/doc/current/frontend.html)
