# 📋 Guide de Migration - Nouvelle Structure

## Changements Appliqués

Le projet a été réorganisé pour une meilleure maintenabilité et clarté du code.

### 🔄 Avant → Après

```
AVANT (Structure désorganisée):
koumaz/
├── index.html
├── login.html
├── register.html
├── admin.html
├── client.html
├── script.js
├── auth-manager.js
├── style.css
├── auth.php
├── submit-devis.php
├── accounts.json
├── devis.json
└── ...

APRÈS (Structure organisée):
koumaz/
├── index.html                    # Racine (page d'accueil)
├── api/                         # Tous les fichiers PHP
│   ├── account-manager.php
│   ├── devis-manager.php
│   └── ...
├── assets/
│   ├── css/                     # Tous les CSS
│   │   ├── style.css
│   │   └── ...
│   └── js/                      # Tous les JavaScript
│       ├── script.js
│       └── ...
├── data/                        # Toutes les données JSON
│   ├── accounts.json
│   └── devis.json
├── docs/                        # Documentation
├── pages/                       # Toutes les pages HTML
│   ├── login.html
│   ├── register.html
│   └── ...
└── images/                      # Images du site
```

## 📦 Nouveaux Emplacements

### Fichiers PHP (Backend)
**Emplacement**: `/api/`

Tous les fichiers PHP ont été déplacés dans le dossier `api/`:
- `account-manager.php` - Gestion des comptes
- `devis-manager.php` - Gestion des devis
- `auth.php` - Authentification
- `login_check.php` - Vérification login
- `logout.php` - Déconnexion
- `check_session.php` - Vérification session
- `submit-devis.php` - Soumission devis
- `get-clients.php` - Récupération clients
- `save_clients.php` - Sauvegarde clients
- `update_status.php` - Mise à jour statuts
- `init.php` - Initialisation
- `init-database.php` - Init base de données
- `test-auth.php` - Tests

### Fichiers JavaScript
**Emplacement**: `/assets/js/`

- `script.js` - Script principal
- `auth-manager.js` - Gestion authentification
- `console-manager.js` - Gestion console
- `health-check.js` - Vérification santé système
- `tailwind-config.js` - Configuration Tailwind
- `client-script.js` - Scripts espace client
- `admin-script.js` - Scripts admin
- `login-script.js` - Scripts connexion
- `devis-script.js` - Scripts devis

### Fichiers CSS
**Emplacement**: `/assets/css/`

- `style.css` - Styles principaux
- `login-style.css` - Styles page connexion
- `admin-style.css` - Styles panneau admin

### Pages HTML
**Emplacement**: `/pages/`

**Toutes les pages HTML sauf `index.html`** (qui reste à la racine):
- `login.html` - Page de connexion
- `register.html` - Page d'inscription
- `admin.html` - Panneau administrateur
- `admin-login.html` - Connexion admin
- `client.html` - Espace client
- `devis-form.html` - Formulaire de devis
- `devis-contact.html` - Contact pour devis
- `cgu.html` - Conditions générales d'utilisation
- `cgv.html` - Conditions générales de vente
- `pdc.html` - Politique de confidentialité
- `cookies.html` - Politique des cookies
- `mentions-legales.html` - Mentions légales
- `400.html`, `401.html`, `403.html`, `404.html`, `500.html`, `503.html` - Pages d'erreur

### Fichiers de Données
**Emplacement**: `/data/`

- `accounts.json` - Comptes utilisateurs
- `devis.json` - Demandes de devis
- `clients.json` - Base clients (legacy)

### Documentation
**Emplacement**: `/docs/`

- `README.md` - Documentation principale
- `PROJECT_STRUCTURE.md` - Structure du projet
- `FIXES_APPLIED.md` - Historique des corrections
- `TESTING_REPORT.md` - Rapport de tests

## 🔧 Modifications Automatiques

### Chemins mis à jour dans `index.html`
```html
<!-- AVANT -->
<script src="console-manager.js"></script>
<link rel="stylesheet" href="style.css">
<a href="login.html">Connexion</a>

<!-- APRÈS -->
<script src="assets/js/console-manager.js"></script>
<link rel="stylesheet" href="assets/css/style.css">
<a href="pages/login.html">Connexion</a>
```

### Chemins mis à jour dans les pages (`/pages/*.html`)
```html
<!-- AVANT -->
<link rel="stylesheet" href="style.css">
<script src="script.js"></script>
fetch('auth.php', ...)

<!-- APRÈS -->
<link rel="stylesheet" href="../assets/css/style.css">
<script src="../assets/js/script.js"></script>
fetch('../api/auth.php', ...)
```

### Chemins mis à jour dans les fichiers PHP (`/api/*.php`)
```php
// AVANT
define('ACCOUNTS_FILE', __DIR__ . '/accounts.json');
$clients = json_decode(file_get_contents('clients.json'), true);

// APRÈS
define('ACCOUNTS_FILE', __DIR__ . '/../data/accounts.json');
$clients = json_decode(file_get_contents('../data/clients.json'), true);
```

### Chemins mis à jour dans les fichiers JavaScript
```javascript
// AVANT
fetch('auth.php', { ... })
fetch('submit-devis.php', { ... })

// APRÈS
fetch('api/auth.php', { ... })
fetch('api/submit-devis.php', { ... })
```

## ✅ Vérifications Post-Migration

### 1. Tester la page d'accueil
- [ ] Ouvrir `index.html`
- [ ] Vérifier que les styles s'appliquent correctement
- [ ] Vérifier que le menu fonctionne
- [ ] Vérifier que les liens vers les pages fonctionnent

### 2. Tester l'authentification
- [ ] Aller sur la page de connexion (`pages/login.html`)
- [ ] Vérifier que les styles s'appliquent
- [ ] Tenter une connexion
- [ ] Vérifier que la redirection fonctionne

### 3. Tester l'inscription
- [ ] Aller sur `pages/register.html`
- [ ] Tenter de créer un compte
- [ ] Vérifier l'enregistrement dans `data/accounts.json`

### 4. Tester les devis
- [ ] Soumettre un devis depuis la page d'accueil
- [ ] Vérifier l'enregistrement dans `data/devis.json`

### 5. Tester l'espace admin
- [ ] Se connecter en tant qu'admin
- [ ] Vérifier que le panneau s'affiche correctement
- [ ] Tester la gestion des comptes et devis

## 🔒 Sécurité

Le fichier `.htaccess` a été mis à jour pour:
- ✅ Bloquer l'accès direct aux fichiers JSON dans `/data/`
- ✅ Protéger le dossier `/data/` contre l'accès direct
- ✅ Rediriger les pages d'erreur vers `/pages/`

## 🐛 Dépannage

### Problème: Les styles ne s'appliquent pas
**Solution**: Vider le cache du navigateur (Ctrl + F5)

### Problème: Les formulaires ne fonctionnent pas
**Solution**: Vérifier la console JavaScript (F12) pour les erreurs de chemins

### Problème: Erreur 404 sur les API
**Solution**: Vérifier que tous les fichiers PHP sont bien dans `/api/`

### Problème: Les données ne sont pas sauvegardées
**Solution**: Vérifier les permissions sur le dossier `/data/` (chmod 755)

### Problème: Erreur "Cannot find file"
**Solution**: Vérifier que les chemins dans les fichiers ont été correctement mis à jour

## 📞 Support

En cas de problème persistant:
1. Vérifier la console JavaScript (F12)
2. Vérifier les logs d'erreur PHP
3. Consulter le fichier `docs/FIXES_APPLIED.md`

## 🎉 Avantages de la Nouvelle Structure

✅ **Meilleure organisation**: Code plus facile à naviguer et maintenir
✅ **Séparation des préoccupations**: Frontend, Backend, Données séparés
✅ **Sécurité renforcée**: Fichiers sensibles protégés dans `/data/`
✅ **Scalabilité**: Plus facile d'ajouter de nouvelles fonctionnalités
✅ **Professionnalisme**: Structure conforme aux standards de l'industrie

---

**Date de migration**: Novembre 2024  
**Version**: 2.1.0
