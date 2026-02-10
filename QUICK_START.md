# 🚀 DÉMARRAGE RAPIDE - MOODTRACKER

## En 5 minutes...

### Étape 1: Copier les assets
```bash
# Exécuter sur Windows
copy_assets.bat

# Ou copier manuellement les dossiers
# - templates/templates/front/css → public/front_assets/css
# - templates/templates/front/js → public/front_assets/js
# - templates/templates/front/img → public/front_assets/img
# - templates/templates/back/assets → public/admin_assets
```

### Étape 2: Lancer le serveur
```bash
# Exécuter une de ces commandes dans le terminal
php bin/console server:run
# ou
symfony server:start
# ou
php -S localhost:8000 -t public/
```

### Étape 3: Accéder au site
Ouvrez un navigateur et visitez:
- **Accueil:** http://localhost:8000/
- **Admin:** http://localhost:8000/admin (si connecté comme admin)

---

## Structure des Pages Créées

### 🌍 Pages Publiques (Front-end)
```
/ ................................. Accueil
/about .............................. À propos
/blog ............................... Blog
/events ............................. Événements
/contact ............................ Contact
/dashboard .......................... Tableau de bord utilisateur (authentifié)
```

### 🔐 Pages Admin
```
/admin ............................. Tableau de bord admin
/admin/users ....................... Gestion des utilisateurs
/admin/reports ..................... Rapports
/admin/settings .................... Paramètres
```

---

## Fichiers Créés

```
✅ Templates Twig:
   - templates/base_front.html.twig
   - templates/admin/base_admin.html.twig
   - templates/front/ (6 pages)
   - templates/admin/ (4 pages)

✅ Contrôleurs:
   - src/Controller/FrontController.php
   - src/Controller/AdminController.php

✅ Documentation:
   - TEMPLATE_INTEGRATION.md ← Guide complet
   - ASSETS_GUIDE.md ← Gestion des assets
   - CHECKLIST.md ← Plan d'intégration
   - INTEGRATION_SUMMARY.txt ← Résumé
   - QUICK_START.md ← Ce fichier

✅ Scripts:
   - copy_assets.bat ← Copier les assets
```

---

## Personnalisation Rapide

### Changer le nom du site
**Fichier:** `templates/base_front.html.twig` (ligne 8)
```twig
<title>Votre Nom de Site</title>
```

### Changer le logo
**Fichier:** `templates/base_front.html.twig` (ligne 58)
```twig
<a href="{{ path('app_home') }}">
    <span>Votre Logo</span>
</a>
```

### Ajouter un lien de menu
**Fichier:** `templates/base_front.html.twig` (ligne 62)
```html
<li><a href="{{ path('app_votre_route') }}">Mon Lien</a></li>
```

### Changer les couleurs
Modifiez les fichiers CSS dans:
- `public/front_assets/css/style.css`
- `public/admin_assets/css/theme.min.css`

---

## Dépannage Rapide

### ❌ Erreur 404 sur les routes
**Solution:** Assurez-vous que le serveur Symfony tourne et que vous utilisez le bon port (8000)

### ❌ Les images ne s'affichent pas
**Solution:** Vérifiez que:
1. Vous avez exécuté `copy_assets.bat`
2. Les fichiers existent dans `public/front_assets/`
3. Le serveur peut accéder à `public/`

### ❌ CSS/JS ne sont pas appliqués
**Solution:** 
1. Videz le cache du navigateur (Ctrl+Shift+Del)
2. Vérifiez que les fichiers CSS/JS existent
3. Vérifiez les chemins dans les templates

### ❌ Accès admin refusé
**Solution:** Vous devez être connecté comme admin. Pour tester:
1. Créez un utilisateur avec ROLE_ADMIN
2. Connectez-vous
3. Accédez à /admin

---

## Ajouter une Nouvelle Page

### 1. Créer le template
Créez `templates/front/ma-page.html.twig`:
```twig
{% extends "base_front.html.twig" %}

{% block title %}Ma Page - MoodTracker{% endblock %}

{% block content %}
  <div class="container py-5">
    <h1>Ma nouvelle page</h1>
    <p>Contenu de ma page</p>
  </div>
{% endblock %}
```

### 2. Ajouter la route
Éditez `src/Controller/FrontController.php` et ajoutez:
```php
#[Route('/ma-page', name: 'ma_page')]
public function maPage(): Response
{
    return $this->render('front/ma-page.html.twig');
}
```

### 3. Ajouter au menu
Éditez `templates/base_front.html.twig` et ajoutez:
```twig
<li><a href="{{ path('app_ma_page') }}">Ma Page</a></li>
```

---

## Configuration Optionnelle

### Utiliser une Base de Données

**1. Configurer dans `.env`:**
```
DATABASE_URL="postgresql://user:password@localhost/moodtracker"
```

**2. Créer les migrations:**
```bash
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Activer les Emails

**1. Configurer dans `.env`:**
```
MAILER_DSN=symfony+sendmail://default
# ou
MAILER_DSN=smtp://user:pass@smtp.gmail.com:587
```

---

## Prochaines Étapes

1. ✅ **Copier les assets** (si pas fait)
2. ✅ **Tester les routes** (si pas fait)
3. ⬜ Customiser le contenu
4. ⬜ Connecter la base de données
5. ⬜ Ajouter des fonctionnalités
6. ⬜ Déployer

---

## Ressources Utiles

- [Symfony Documentation](https://symfony.com/doc)
- [Twig Documentation](https://twig.symfony.com/)
- [Bootstrap Documentation](https://getbootstrap.com/)
- [PHP Manual](https://www.php.net/manual/)

---

## Support

Si vous avez des questions:
1. Consultez **TEMPLATE_INTEGRATION.md** pour le détail complet
2. Consultez **CHECKLIST.md** pour la liste des tâches
3. Consultez **ASSETS_GUIDE.md** pour les assets
4. Vérifiez les fichiers de documentation déjà créés

---

💡 **Astuce:** Gardez cette page comme point de référence rapide!

Bon développement! 🎉
