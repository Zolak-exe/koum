# NEXT DRIVE IMPORT - Site Web Amélioré

## 🚀 Améliorations Apportées

### ✅ Corrections et Améliorations

1. **Images des Véhicules**
   - Remplacement des images Facebook par des images locales stables
   - Nissan 350Z: `images/350z.jpg`
   - BMW M3 F80: `images/m3_f50.webp`
   - Ford Focus RS: `images/focusRS.webp`

2. **Nouvelle Interface Client**
   - Page `client.html` avec authentification par email/téléphone
   - Dashboard client avec suivi des demandes en temps réel
   - Statistiques personnalisées (demandes actives, devis reçus, véhicules importés)
   - Interface responsive et moderne

3. **Page Admin Améliorée**
   - Statistiques en temps réel
   - Gestion complète des clients et demandes
   - Modification des devis
   - Export Excel
   - Filtres et recherche avancée

4. **Sécurité Renforcée**
   - Fichier `.htaccess` complet avec protection des fichiers sensibles
   - Protection contre les injections SQL
   - Headers de sécurité (X-Frame-Options, X-XSS-Protection, etc.)
   - Blocage de l'accès direct aux fichiers .json, .log, .txt

5. **Liens et Navigation**
   - Correction du lien politique de confidentialité (pdc.html)
   - Ajout de l'Espace Client dans la navigation
   - Navigation mobile mise à jour

6. **Optimisations**
   - Compression GZIP activée
   - Cache navigateur configuré
   - Images optimisées (WebP pour BMW et Ford)

## 📁 Structure des Fichiers

```
koum-website/
├── index.html              # Page d'accueil
├── client.html             # Interface client (NOUVEAU)
├── client-script.js        # Script interface client (NOUVEAU)
├── admin.html              # Dashboard admin
├── admin-script.js         # Script admin amélioré
├── admin-style.css         # Styles admin
├── login.html              # Page de connexion admin
├── login-script.js         # Script connexion
├── login-style.css         # Styles connexion
├── style.css               # Styles principaux
├── script.js               # Scripts principaux
├── .htaccess               # Configuration sécurité (NOUVEAU)
├── images/                 # Dossier images (NOUVEAU)
│   ├── 350z.jpg
│   ├── m3_f50.webp
│   └── focusRS.webp
├── PHP Backend:
│   ├── submit-devis.php    # Traitement formulaire
│   ├── get-clients.php     # Récupération clients
│   ├── save_clients.php    # Sauvegarde clients
│   ├── update_status.php   # Mise à jour statuts
│   ├── login_check.php     # Authentification admin
│   ├── check_session.php   # Vérification session
│   ├── logout.php          # Déconnexion
│   └── init.php            # Initialisation
├── Pages légales:
│   ├── cgu.html
│   ├── cgv.html
│   ├── pdc.html
│   ├── mentions-legales.html
│   └── cookies.html
└── Pages d'erreur:
    ├── 400.html
    ├── 401.html
    ├── 403.html
    ├── 404.html
    ├── 500.html
    └── 503.html
```

## 🔧 Installation

1. **Télécharger tous les fichiers** sur votre serveur web

2. **Vérifier les permissions**
   ```bash
   chmod 755 *.php
   chmod 644 *.html *.css *.js
   chmod 755 images/
   ```

3. **Créer le fichier clients.json**
   - Accéder à `init.php` dans votre navigateur
   - Ou créer manuellement un fichier `clients.json` avec le contenu: `[]`

4. **Configurer les identifiants admin**
   - Éditer `login_check.php`
   - Modifier le mot de passe (ligne 58)
   - Utiliser `password_hash()` pour sécuriser

5. **Configurer l'email**
   - Éditer `submit-devis.php`
   - Modifier `ADMIN_EMAIL` (ligne 9)

## 🎯 Fonctionnalités

### Interface Publique
- ✅ Présentation des véhicules avec économies
- ✅ Formulaire de demande de devis
- ✅ Modal détaillé pour chaque véhicule
- ✅ FAQ interactive
- ✅ Avis clients
- ✅ Section garanties

### Espace Client (NOUVEAU)
- ✅ Connexion sécurisée (email + téléphone)
- ✅ Dashboard personnalisé
- ✅ Suivi des demandes en temps réel
- ✅ Statistiques personnelles
- ✅ Historique complet

### Dashboard Admin
- ✅ Vue d'ensemble avec statistiques
- ✅ Liste complète des demandes
- ✅ Modification des devis
- ✅ Changement de statut rapide
- ✅ Recherche et filtres
- ✅ Export Excel
- ✅ Gestion des clients

## 🔐 Sécurité

### Protections Actives
- Protection des fichiers sensibles (.json, .log, .txt)
- Rate limiting sur les formulaires
- Validation côté serveur
- Protection CSRF
- Headers de sécurité
- Sanitization des données
- Protection contre les injections SQL

### Fichiers Protégés
- `clients.json` - Données clients
- `*.log` - Fichiers de logs
- `rate_limit_*.txt` - Fichiers de rate limiting
- `init.php` - Script d'initialisation

## 📱 Responsive Design

Le site est entièrement responsive et optimisé pour:
- 📱 Mobile (320px+)
- 📱 Tablette (768px+)
- 💻 Desktop (1024px+)
- 🖥️ Large Desktop (1440px+)

## 🎨 Design

### Palette de Couleurs
- Primary: `#FF6B35` (Orange)
- Secondary: `#F7931E` (Orange clair)
- Dark: `#0a0a0a` (Noir)
- Gray Custom: `#1a1a1a` (Gris foncé)

### Typographie
- Titres: Orbitron (Bold, Black)
- Texte: Inter (Regular, Medium, Bold)

## 🚀 Déploiement

### Prérequis
- PHP 7.4+
- Apache/Nginx avec mod_rewrite
- Support .htaccess (Apache)

### Configuration Production
1. Activer HTTPS dans `.htaccess` (décommenter lignes 67-71)
2. Modifier les URLs dans `submit-devis.php`
3. Configurer les emails
4. Tester tous les formulaires

## 📧 Support

Pour toute question ou problème:
- Email: nextdriveimport@gmail.com
- Site: https://nextdriveimport.fr

## 📝 Notes Importantes

1. **Fichier clients.json**
   - Doit être créé avant la première utilisation
   - Protégé par .htaccess
   - Accessible uniquement via PHP

2. **Identifiants Admin**
   - Par défaut: root/root
   - **À CHANGER EN PRODUCTION**

3. **Emails**
   - Configurés pour envoyer à `nextdriveimport@gmail.com`
   - Vérifier la configuration SMTP du serveur

4. **Images**
   - Toutes les images sont maintenant locales
   - Format WebP pour optimisation
   - Fallback en JPG pour compatibilité

## 🔄 Mises à Jour

### Version 2.1.0 (Novembre 2024)
- ✅ Ajout interface client complète
- ✅ Amélioration dashboard admin
- ✅ Remplacement images véhicules
- ✅ Sécurisation renforcée
- ✅ Optimisations performances
- ✅ Correction liens cassés

---

**Développé avec ❤️ pour NEXT DRIVE IMPORT**
