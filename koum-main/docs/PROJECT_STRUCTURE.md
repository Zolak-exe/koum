# NEXT DRIVE IMPORT - Documentation Technique

## 📁 Structure du Projet

### **Pages Principales**
- `index.html` - Page d'accueil avec présentation des services
- `login.html` - Page de connexion administrateur
- `register.html` - Page d'inscription client
- `client.html` - Espace client (protégé, nécessite authentification)
- `admin.html` - Tableau de bord admin
- `devis-contact.html` - Formulaire de contact pour devis

### **Pages Légales**
- `mentions-legales.html` - Mentions légales
- `cgv.html` - Conditions Générales de Vente
- `cgu.html` - Conditions Générales d'Utilisation
- `pdc.html` - Politique de confidentialité
- `cookies.html` - Politique de cookies

### **Pages d'Erreur**
- `400.html`, `401.html`, `403.html`, `404.html`, `500.html`, `503.html`

### **Scripts JavaScript**
- `script.js` - Script principal (gestion formulaires, FAQ, modals)
- `auth-manager.js` - Gestionnaire d'authentification global
- `login-script.js` - Script de la page login admin
- `client-script.js` - Script de l'espace client
- `devis-script.js` - Script du formulaire de devis
- `admin-script.js` - Script du tableau de bord admin
- `tailwind-config.js` - Configuration et suppression warnings Tailwind

### **Fichiers PHP Backend**
- `auth.php` - API d'authentification client (register, login, check_session)
- `login_check.php` - Vérification login admin
- `logout.php` - Déconnexion
- `check_session.php` - Vérification session admin
- `submit-devis.php` - Soumission des demandes de devis
- `get-clients.php` - Récupération liste clients (admin)
- `save_clients.php` - Sauvegarde données clients
- `update_status.php` - Mise à jour statut demandes
- `init.php` - Initialisation base de données/fichiers

### **Fichiers de Données**
- `clients.json` - Stockage des clients et demandes (PROTÉGÉ)

### **Fichiers CSS**
- `style.css` - Styles principaux du site
- `login-style.css` - Styles page login
- `admin-style.css` - Styles tableau de bord admin

### **Configuration**
- `.htaccess` - Configuration serveur Apache (sécurité, cache, redirections)
- `robots.txt` - Instructions pour les moteurs de recherche
- `sitemap.xml` - Plan du site pour SEO

### **Documentation**
- `README.md` - Documentation principale (ce fichier)
- `TESTING_REPORT.md` - Rapport de tests

## 🔐 Système d'Authentification

### **Authentification Client**
- Basée sur `sessionStorage` pour persistence pendant la session
- Gère l'affichage dynamique des menus (Connexion/Inscription vs Espace Client/Déconnexion)
- Fichier principal: `auth-manager.js`

### **Authentification Admin**
- Sessions PHP traditionnelles
- Fichiers: `login_check.php`, `check_session.php`, `logout.php`

### **Flux d'Authentification Client**
1. Inscription via `register.html` → `auth.php` (action: register)
2. Données stockées dans `sessionStorage`: `isLoggedIn`, `userName`, `userEmail`, `clientId`
3. Vérification sur chaque page via `checkAuthStatus()`
4. Déconnexion: suppression `sessionStorage` + appel `logout.php`

## 🚀 Fonctionnalités

### **Page d'Accueil (index.html)**
- Hero section avec animations
- Exemples d'économies sur véhicules premium
- Modal détaillé pour chaque véhicule
- Processus en 5 étapes
- FAQ avec accordéon
- Section avis clients
- Formulaire de devis avec authentification
- Section garanties
- Footer complet avec liens légaux

### **Espace Client (client.html)**
- Tableau de bord personnel
- Historique des demandes de devis
- Suivi des statuts (En attente, En cours, Complété)
- Accès protégé (redirection vers login si non authentifié)

### **Tableau de Bord Admin (admin.html)**
- Liste complète des clients
- Gestion des demandes de devis
- Mise à jour des statuts
- Statistiques en temps réel

## 📦 Dépendances

### **CDN Utilisés**
- Tailwind CSS: `https://cdn.tailwindcss.com`
- Google Fonts: Orbitron + Inter

### **Technologies**
- HTML5
- CSS3 (Tailwind + Custom)
- JavaScript Vanilla (ES6+)
- PHP 7.4+
- JSON pour stockage de données

## 🔧 Installation

1. **Upload des fichiers** sur le serveur (InfinityFree ou similaire)
2. **Permissions**: 
   - `clients.json`: 0644 (lecture/écriture pour PHP)
   - Dossier racine: 0755
3. **Configuration PHP**: 
   - `display_errors = Off` en production
   - Sessions activées
4. **Test**: Accéder à `index.html`

## 🐛 Résolution des Problèmes Courants

### **Erreur "Permissions-Policy"**
✅ **Solution**: Désactivée via `.htaccess` (Header unset Permissions-Policy)

### **Erreur "cdn.tailwindcss.com should not be used in production"**
✅ **Solution**: Warning supprimé via `tailwind-config.js`

### **Erreur JSON Parsing dans checkClientSession**
✅ **Solution**: Vérification `content-type` avant parsing + gestion d'erreur améliorée

### **Session non persistante**
✅ **Solution**: Utilisation de `sessionStorage` pour persistence côté client

### **Menus ne s'affichent pas correctement selon l'état de connexion**
✅ **Solution**: Classes `.auth-only-logged-in` et `.auth-only-logged-out` gérées par `checkAuthStatus()`

## 📊 Données Stockées

### **sessionStorage (Client)**
```javascript
{
  isLoggedIn: 'true',
  userName: 'Jean Dupont',
  userEmail: 'jean@example.com',
  clientId: 'client_xxx'
}
```

### **clients.json (Serveur)**
```json
[
  {
    "id": "client_xxx",
    "nom": "Jean Dupont",
    "email": "jean@example.com",
    "telephone": "0612345678",
    "created_at": "2024-xx-xx",
    "demandes": [...]
  }
]
```

## 🔒 Sécurité

- ✅ Protection des fichiers sensibles via `.htaccess`
- ✅ Headers de sécurité (X-Frame-Options, X-XSS-Protection, etc.)
- ✅ Validation côté serveur et client
- ✅ Sessions PHP sécurisées
- ✅ Pas de mot de passe stocké (authentification par email+téléphone)
- ✅ Conformité RGPD

## 📱 Responsive Design

- ✅ Mobile-first approach
- ✅ Breakpoints: sm (640px), md (768px), lg (1024px), xl (1280px)
- ✅ Menu burger sur mobile
- ✅ Grilles adaptatives

## 🎨 Thème

**Couleurs:**
- Primary: `#FF6B35` (Orange)
- Secondary: `#F7931E` (Jaune)
- Dark: `#0a0a0a`
- Gray Custom: `#1a1a1a`

**Polices:**
- Titres: Orbitron (Bold/Black)
- Texte: Inter (Regular/Medium/Semibold)

## 📈 SEO

- ✅ Balises meta optimisées
- ✅ Open Graph pour réseaux sociaux
- ✅ Schema.org (Organization, FAQPage)
- ✅ Sitemap XML
- ✅ Robots.txt
- ✅ URLs canoniques

## 🚦 Statut du Projet

**Version:** 2.1.0  
**Dernière mise à jour:** Novembre 2024  
**Statut:** ✅ Production Ready

## 📞 Support

Email: nextdriveimport@gmail.com  
Instagram: @nextdriveimport

---

**© 2024 NEXT DRIVE IMPORT - Tous droits réservés**
