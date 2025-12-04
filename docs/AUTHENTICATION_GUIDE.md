# Guide d'Authentification - NEXT DRIVE IMPORT

## 🔐 Vue d'ensemble

Le système d'authentification a été complètement refondu pour utiliser **email + mot de passe** pour tous les utilisateurs (clients et administrateurs).

## 📋 Changements Principaux

### 1. Système d'Authentification Unifié

- **Avant** : Clients utilisaient email + téléphone, Admin utilisait username + password
- **Maintenant** : Tous utilisent email + mot de passe via `api/account-manager.php`

### 2. Inscription avec Mot de Passe

Les nouveaux clients doivent maintenant créer un mot de passe lors de l'inscription :

**Exigences du mot de passe** :
- Minimum 8 caractères
- Au moins une majuscule
- Au moins une minuscule
- Au moins un chiffre
- Au moins un symbole (!@#$%^&*...)

### 3. Comptes Générés

**19 comptes clients** ont été créés à partir des demandes existantes dans `clients.json` :
- Chaque client a reçu un mot de passe temporaire fort (16 caractères)
- Tous les mots de passe sont stockés avec bcrypt (sécurisé)
- Flag `password_reset_required: true` pour forcer le changement au premier login

**1 compte administrateur** a été créé :
- Email : `admin@nextdriveimport.fr`
- Mot de passe : Voir `docs/admin-credentials.txt`
- Rôle : `admin`

## 📁 Fichiers de Données

### accounts.json
Contient tous les comptes utilisateurs (clients + admin) :
```json
{
  "id": "acc_...",
  "nom": "Nom Complet",
  "email": "email@exemple.com",
  "telephone": "0612345678",
  "password": "$2y$10$...",  // Hash bcrypt
  "role": "client" | "admin",
  "created_at": "2025-11-13 01:00:00",
  "updated_at": "2025-11-13 01:00:00",
  "active": true,
  "password_reset_required": true
}
```

### devis.json
Contient tous les devis liés aux comptes :
```json
{
  "id": "devis_...",
  "user_id": "acc_...",  // Lié au compte
  "user_name": "Nom Client",
  "user_email": "email@exemple.com",
  "marque": "BMW",
  "modele": "Série 5",
  "budget": 45000,
  "statut": "En attente" | "En cours" | "Complété" | "Annulé",
  "created_at": "2025-10-12 14:20:12",
  "updated_at": "2025-11-13 01:00:00"
}
```

## 🔑 Credentials

### Fichier : docs/client-credentials.csv

Contient les credentials de tous les clients (19 comptes) :
- Nom
- Email
- Téléphone
- Mot de passe temporaire
- ID Compte

**⚠️ IMPORTANT** :
- Ce fichier contient des mots de passe en clair
- NE PAS déployer sur le serveur de production
- Distribuer aux clients de manière sécurisée (email chiffré, etc.)
- Supprimer après distribution

### Fichier : docs/admin-credentials.txt

Contient les credentials administrateur :
- Email : `admin@nextdriveimport.fr`
- Mot de passe : Voir le fichier

**⚠️ IMPORTANT** :
- Changez ce mot de passe après la première connexion
- Ne partagez jamais ces identifiants
- Supprimez ce fichier après avoir noté les credentials

## 🚀 Utilisation

### Connexion Client

1. Aller sur `pages/login.html`
2. Entrer email + mot de passe
3. Redirection automatique vers `pages/client.html`

### Connexion Admin

1. Aller sur `pages/login.html`
2. Entrer email admin + mot de passe
3. Redirection automatique vers `pages/admin.html`

### Inscription Nouveau Client

1. Aller sur `pages/register.html`
2. Remplir le formulaire :
   - Nom complet
   - Email
   - Téléphone
   - Mot de passe (avec confirmation)
   - Accepter RGPD
3. Validation automatique du mot de passe
4. Redirection vers `pages/devis-form.html`

## 🔧 API Endpoints

### account-manager.php

**Actions disponibles** :

1. **register** - Inscription nouveau compte
   ```json
   {
     "action": "register",
     "nom": "Jean Dupont",
     "email": "jean@exemple.com",
     "telephone": "0612345678",
     "password": "MotDePasse123!"
   }
   ```

2. **login** - Connexion (client ou admin)
   ```json
   {
     "action": "login",
     "email": "jean@exemple.com",
     "password": "MotDePasse123!"
   }
   ```

3. **check_session** - Vérifier session active
   ```json
   {
     "action": "check_session"
   }
   ```

4. **logout** - Déconnexion
   ```json
   {
     "action": "logout"
   }
   ```

### devis-manager.php

**Actions disponibles** :

1. **create** - Créer un devis (client connecté)
2. **get_my_devis** - Récupérer mes devis (client)
3. **get_all_devis** - Récupérer tous les devis (admin)
4. **update_status** - Modifier statut (admin)
5. **add_response** - Ajouter réponse (admin)
6. **delete** - Supprimer devis (admin)
7. **get_stats** - Statistiques (admin)

## 🔒 Sécurité

### Mots de Passe

- Tous les mots de passe sont hashés avec **bcrypt** (PHP `password_hash()`)
- Coût bcrypt : 10 (par défaut)
- Impossible de récupérer le mot de passe en clair depuis la base

### Sessions

- Sessions PHP côté serveur
- SessionStorage côté client (isLoggedIn, userName, userEmail, clientId, userRole)
- Timeout automatique après inactivité

### Protection des Fichiers

Le fichier `.htaccess` protège :
- `/data/*.json` - Accès direct bloqué
- Seuls les scripts PHP autorisés peuvent lire les données

## 📊 Statuts des Devis

Les statuts utilisés dans `devis.json` :
- **En attente** - Nouveau devis, pas encore traité
- **En cours** - Devis en cours de traitement
- **Complété** - Devis terminé, véhicule livré
- **Annulé** - Devis annulé

**Note** : Le frontend (client-script.js) mappe ces statuts pour l'affichage :
- En attente → nouveau
- En cours → en_cours
- Complété → termine
- Annulé → annule

## 🎯 Prochaines Étapes

1. **Déployer le site** sur votre hébergement InfinityFree
2. **Tester la connexion admin** avec les credentials fournis
3. **Changer le mot de passe admin** immédiatement
4. **Distribuer les credentials clients** de manière sécurisée
5. **Supprimer les fichiers de credentials** du serveur après distribution
6. **Implémenter la fonctionnalité "Mot de passe oublié"** (optionnel)
7. **Ajouter une page "Changer mot de passe"** pour les clients (optionnel)

## ❓ Support

Pour toute question ou problème :
- Vérifiez que PHP 7.4+ est installé
- Vérifiez que l'extension bcrypt est disponible
- Vérifiez les permissions des fichiers `/data/*.json` (644)
- Vérifiez que `.htaccess` est actif sur votre hébergement

## 📝 Notes Techniques

### Compatibilité PHP

Le code est compatible avec :
- PHP 7.4+
- PHP 8.0+
- PHP 8.1+
- PHP 8.2+

### Hébergement InfinityFree

Points d'attention :
- Vérifiez que `.htaccess` est supporté
- Vérifiez les permissions d'écriture sur `/data`
- Testez que bcrypt fonctionne correctement
- Vérifiez les limites de taille des fichiers JSON

### Migration depuis l'Ancien Système

Si vous aviez déjà des clients avec l'ancien système (email + téléphone) :
- Tous les clients ont été migrés vers le nouveau système
- Chaque client a reçu un mot de passe temporaire
- Les clients doivent utiliser leur nouveau mot de passe pour se connecter
- L'ancien système (email + téléphone) ne fonctionne plus
