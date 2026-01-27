# 🚗 NEXT DRIVE IMPORT

Plateforme web professionnelle pour l'importation de véhicules premium depuis l'Europe.

## 📁 Structure du Projet

```
koumaz/
├── index.html                  # Page d'accueil principale
├── robots.txt                  # Configuration SEO
├── sitemap.xml                # Plan du site
├── .htaccess                  # Configuration serveur
├── .editorconfig              # Configuration éditeur
│
├── api/                       # 🔧 Backend PHP
│   ├── account-manager.php    # Gestion comptes utilisateurs
│   ├── devis-manager.php      # Gestion des devis
│   ├── auth.php               # Authentification (legacy)
│   ├── login_check.php        # Vérification login
│   ├── logout.php             # Déconnexion
│   ├── check_session.php      # Vérification session
│   ├── submit-devis.php       # Soumission devis
│   ├── get-clients.php        # Récupération clients
│   ├── save_clients.php       # Sauvegarde clients
│   ├── update_status.php      # Mise à jour statuts
│   ├── init.php               # Initialisation
│   ├── init-database.php      # Init base de données
│   └── test-auth.php          # Tests authentification
│
├── assets/                    # 🎨 Ressources Frontend
│   ├── css/
│   │   ├── style.css          # Styles principaux
│   │   ├── login-style.css    # Styles connexion
│   │   └── admin-style.css    # Styles admin
│   │
│   └── js/
│       ├── script.js          # Script principal
│       ├── auth-manager.js    # Gestion authentification
│       ├── console-manager.js # Gestion console
│       ├── health-check.js    # Vérification santé
│       ├── tailwind-config.js # Config Tailwind
│       ├── client-script.js   # Scripts espace client
│       ├── admin-script.js    # Scripts admin
│       ├── login-script.js    # Scripts login
│       └── devis-script.js    # Scripts devis
│
├── data/                      # 💾 Données JSON
│   ├── accounts.json          # Comptes utilisateurs
│   ├── devis.json            # Demandes de devis
│   └── clients.json          # Clients (legacy)
│
├── docs/                      # 📚 Documentation
│   ├── README.md             # Ce fichier
│   ├── PROJECT_STRUCTURE.md  # Structure projet
│   ├── FIXES_APPLIED.md      # Corrections appliquées
│   └── TESTING_REPORT.md     # Rapport de tests
│
├── images/                    # 🖼️ Images du site
│   ├── 350z.jpg
│   ├── m3_f50.webp
│   └── focusRS.webp
│
└── pages/                     # 📄 Pages HTML
    ├── login.html             # Connexion
    ├── register.html          # Inscription
    ├── admin.html            # Panneau admin
    ├── admin-login.html      # Login admin
    ├── client.html           # Espace client
    ├── devis-form.html       # Formulaire devis
    ├── devis-contact.html    # Contact devis
    ├── cgu.html              # CGU
    ├── cgv.html              # CGV
    ├── pdc.html              # Politique confidentialité
    ├── cookies.html          # Politique cookies
    ├── mentions-legales.html # Mentions légales
    └── 4xx/5xx.html          # Pages erreurs
```

## 🚀 Fonctionnalités

### Authentification
- ✅ Système de comptes utilisateurs
- ✅ Rôles : Admin / Client
- ✅ Sessions PHP sécurisées
- ✅ Gestion des permissions

### Gestion des Devis
- ✅ Formulaire de demande de devis
- ✅ Suivi des demandes
- ✅ Statuts : En attente, En cours, Complété, Annulé
- ✅ Notifications

### Espace Client
- ✅ Historique des demandes
- ✅ Suivi en temps réel
- ✅ Gestion du profil

### Administration
- ✅ Gestion des comptes
- ✅ Attribution des rôles
- ✅ Gestion des devis
- ✅ Statistiques

## 🔧 Technologies

- **Frontend**: HTML5, Tailwind CSS, Vanilla JavaScript
- **Backend**: PHP 7.4+
- **Base de données**: JSON (fichiers plats)
- **Hébergement**: InfinityFree / Serveur compatible PHP

## 📝 Configuration

### Prérequis
- PHP 7.4 ou supérieur
- Permissions d'écriture sur `/data`
- Module PHP JSON activé

### Installation

1. **Téléverser les fichiers** sur votre serveur

2. **Configurer les permissions** :
```bash
chmod 644 data/*.json
chmod 755 api/
```

3. **Initialiser la base de données** :
   - Accéder à `/api/init-database.php` dans votre navigateur
   - Créer le compte admin par défaut
   - **Supprimer init-database.php après utilisation**

4. **Compte admin par défaut** :
   - Email: `admin@nextdriveimport.fr`
   - Mot de passe: `Admin@2024`
   - ⚠️ **Changez le mot de passe immédiatement !**

## 🔒 Sécurité

- ✅ Validation des entrées utilisateur
- ✅ Échappement des données
- ✅ Sessions sécurisées
- ✅ Protection CSRF
- ✅ Permissions fichiers strictes
- ✅ Logs d'erreurs désactivés en production

## 📞 Contact

- **Email**: nextdriveimport@gmail.com
- **Instagram**: @nextdriveimport
- **Téléphone**: +33 1 23 45 67 89

## 📄 Licence

© 2024 NEXT DRIVE IMPORT - Tous droits réservés

## 🛠️ Maintenance

### Sauvegarde
Sauvegarder régulièrement le dossier `/data` contenant les fichiers JSON.

### Mise à jour
Les mises à jour sont documentées dans `/docs/FIXES_APPLIED.md`.

### Support
Pour toute question technique, consulter la documentation dans `/docs/`.

---

**Version**: 2.1.0  
**Dernière mise à jour**: Novembre 2024
