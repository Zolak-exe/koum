# NEXT DRIVE IMPORT - Package de Déploiement

## 🎯 Résumé des Modifications

Ce package contient toutes les corrections et améliorations demandées pour le site NEXT DRIVE IMPORT :

### ✅ Corrections Effectuées

1. **Bouton Login sur la Page d'Accueil** ✅
   - Le bouton login redirige maintenant vers `pages/login.html`
   - Tous les CTAs de connexion ont été vérifiés et corrigés

2. **Inscription avec Mot de Passe** ✅
   - Le formulaire d'inscription demande maintenant de créer un mot de passe
   - Validation complète : 8+ caractères, majuscule, minuscule, chiffre, symbole
   - Confirmation du mot de passe obligatoire

3. **Connexion avec Email + Mot de Passe** ✅
   - Tous les utilisateurs (clients et admin) se connectent avec email + mot de passe
   - L'ancien système (email + téléphone) a été supprimé
   - Authentification unifiée via `api/account-manager.php`

4. **Devis Supprimés et Recréés** ✅
   - Tous les anciens devis ont été supprimés
   - 19 nouveaux devis créés, liés aux comptes clients
   - Chaque devis est lié à un `user_id` dans `accounts.json`

5. **Credentials Clients Générés** ✅
   - 19 comptes clients créés avec mots de passe temporaires forts
   - Fichier CSV avec tous les credentials : `docs/client-credentials.csv`
   - Mots de passe de 16 caractères (majuscules, minuscules, chiffres, symboles)

6. **Credentials Admin Générés** ✅
   - 1 compte admin créé : `admin@nextdriveimport.fr`
   - Mot de passe fort de 20 caractères
   - Fichier avec credentials : `docs/admin-credentials.txt`

## 📦 Contenu du Package

```
koumaz-project/
├── index.html                          # Page d'accueil (login button corrigé)
├── pages/
│   ├── login.html                      # Page de connexion (email + password)
│   ├── register.html                   # Inscription (avec password)
│   ├── client.html                     # Interface client
│   ├── admin.html                      # Interface admin
│   └── ...
├── api/
│   ├── account-manager.php             # Gestion authentification (mis à jour)
│   ├── devis-manager.php               # Gestion devis
│   └── ...
├── assets/
│   ├── js/
│   │   ├── client-script.js            # Script client (mis à jour)
│   │   ├── auth-manager.js             # Gestion auth frontend
│   │   └── ...
│   └── css/
│       └── ...
├── data/
│   ├── accounts.json                   # 20 comptes (19 clients + 1 admin)
│   ├── devis.json                      # 19 devis liés aux comptes
│   └── clients.json                    # Anciens clients (conservé)
├── docs/
│   ├── client-credentials.csv          # ⚠️ Credentials clients (19)
│   ├── admin-credentials.txt           # ⚠️ Credentials admin
│   ├── AUTHENTICATION_GUIDE.md         # Guide d'authentification
│   └── DEPLOYMENT_README.md            # Ce fichier
└── generate_data.py                    # Script de génération (conservé)
```

## 🚀 Installation sur InfinityFree

### Étape 1 : Upload des Fichiers

1. Connectez-vous à votre compte InfinityFree
2. Ouvrez le File Manager
3. Uploadez tous les fichiers **SAUF** :
   - ❌ `docs/client-credentials.csv`
   - ❌ `docs/admin-credentials.txt`
   - ❌ `generate_data.py`

**⚠️ IMPORTANT** : Ne jamais uploader les fichiers de credentials sur le serveur !

### Étape 2 : Vérifier les Permissions

Vérifiez que les fichiers ont les bonnes permissions :
- `/data/*.json` → 644 (lecture/écriture pour le serveur)
- `.htaccess` → 644
- Tous les fichiers PHP → 644

### Étape 3 : Tester le Site

1. **Tester la page d'accueil** : `https://votre-site.infinityfreeapp.com/`
2. **Tester le bouton login** : Doit rediriger vers `pages/login.html`
3. **Tester la connexion admin** :
   - Email : `admin@nextdriveimport.fr`
   - Mot de passe : Voir `docs/admin-credentials.txt` (local)
4. **Tester l'inscription** : Créer un nouveau compte avec mot de passe

### Étape 4 : Changer le Mot de Passe Admin

**IMPORTANT** : Changez immédiatement le mot de passe admin après la première connexion !

Pour l'instant, vous devrez :
1. Vous connecter avec le mot de passe temporaire
2. Modifier manuellement `data/accounts.json` sur le serveur
3. Remplacer le hash du mot de passe admin par un nouveau hash bcrypt

**Note** : Une page "Changer mot de passe" peut être ajoutée ultérieurement.

### Étape 5 : Distribuer les Credentials Clients

1. **Télécharger** `docs/client-credentials.csv` (depuis votre ordinateur local)
2. **Envoyer les credentials** à chaque client de manière sécurisée :
   - Email chiffré
   - Message privé
   - Appel téléphonique
3. **Supprimer le fichier CSV** après distribution
4. **Demander aux clients** de changer leur mot de passe après la première connexion

## 🔐 Credentials

### Admin

**Email** : `admin@nextdriveimport.fr`  
**Mot de passe** : Voir `docs/admin-credentials.txt`

**⚠️ À FAIRE IMMÉDIATEMENT** :
- Notez ces credentials dans un gestionnaire de mots de passe sécurisé
- Changez le mot de passe après la première connexion
- Supprimez le fichier `admin-credentials.txt` de votre ordinateur

### Clients (19 comptes)

Voir le fichier `docs/client-credentials.csv` pour la liste complète.

**Format du CSV** :
```
Nom,Email,Téléphone,Mot de passe temporaire,ID Compte
Sophie Martin,sophie.martin@email.com,0612345678,Abc123!@#XyZ,...
...
```

**⚠️ À FAIRE** :
- Distribuer les credentials de manière sécurisée
- Demander aux clients de changer leur mot de passe
- Supprimer le fichier CSV après distribution

## 📊 Données Générées

### Comptes (accounts.json)

- **Total** : 20 comptes
- **Clients** : 19
- **Admin** : 1
- **Tous actifs** : `active: true`
- **Mots de passe** : Hashés avec bcrypt (sécurisé)

### Devis (devis.json)

- **Total** : 19 devis
- **Tous liés** : Chaque devis est lié à un `user_id`
- **Statuts** : En attente, En cours, Complété, Annulé
- **Données** : Marque, modèle, budget, options, commentaires

## 🔧 Configuration

### Fichiers à Configurer (Optionnel)

1. **api/devis-manager.php** (ligne ~9)
   - Modifier l'email de notification si nécessaire
   ```php
   $admin_email = 'admin@nextdriveimport.fr';
   ```

2. **.htaccess**
   - Déjà configuré pour bloquer l'accès direct aux fichiers JSON
   - Vérifier qu'il fonctionne sur InfinityFree

## 🧪 Tests à Effectuer

### Tests Essentiels

- [ ] Page d'accueil charge correctement
- [ ] Bouton login redirige vers `pages/login.html`
- [ ] Connexion admin fonctionne (email + password)
- [ ] Connexion client fonctionne (email + password)
- [ ] Inscription nouveau client fonctionne (avec password)
- [ ] Dashboard client affiche les devis
- [ ] Dashboard admin affiche tous les devis
- [ ] Modification de statut fonctionne (admin)
- [ ] Déconnexion fonctionne
- [ ] Accès direct à `/data/accounts.json` est bloqué

### Tests Optionnels

- [ ] Création d'un nouveau devis (client)
- [ ] Réponse à un devis (admin)
- [ ] Export Excel (admin)
- [ ] Recherche et filtres (admin)
- [ ] Statistiques (admin et client)

## ⚠️ Sécurité - Points Importants

### À FAIRE Immédiatement

1. ✅ Changer le mot de passe admin après première connexion
2. ✅ Supprimer `docs/client-credentials.csv` du serveur (ne jamais uploader)
3. ✅ Supprimer `docs/admin-credentials.txt` du serveur (ne jamais uploader)
4. ✅ Vérifier que `.htaccess` bloque l'accès à `/data/*.json`
5. ✅ Tester que bcrypt fonctionne sur InfinityFree

### À NE JAMAIS FAIRE

- ❌ Uploader les fichiers de credentials sur le serveur
- ❌ Partager les credentials par email non chiffré
- ❌ Stocker les mots de passe en clair dans les fichiers
- ❌ Désactiver `.htaccess` (protection des données)
- ❌ Modifier manuellement les hash de mots de passe

## 📚 Documentation Complète

Pour plus de détails, consultez :
- **AUTHENTICATION_GUIDE.md** - Guide complet de l'authentification
- **README.md** - Documentation générale du site

## 🆘 Dépannage

### Problème : Connexion ne fonctionne pas

**Solutions** :
1. Vérifier que PHP 7.4+ est installé
2. Vérifier que l'extension bcrypt est disponible
3. Vérifier les permissions de `/data/accounts.json` (644)
4. Vérifier que le fichier `accounts.json` existe et contient des données

### Problème : Accès direct aux JSON fonctionne

**Solutions** :
1. Vérifier que `.htaccess` est uploadé
2. Vérifier que mod_rewrite est activé sur InfinityFree
3. Vérifier la syntaxe du `.htaccess`

### Problème : Inscription ne fonctionne pas

**Solutions** :
1. Vérifier que `/data/accounts.json` est accessible en écriture
2. Vérifier les permissions (644)
3. Vérifier les logs PHP pour les erreurs

### Problème : Devis ne s'affichent pas

**Solutions** :
1. Vérifier que `/data/devis.json` existe
2. Vérifier que les `user_id` correspondent aux comptes
3. Vérifier la console JavaScript pour les erreurs

## 📞 Support

Si vous rencontrez des problèmes :
1. Vérifiez les logs d'erreur PHP sur InfinityFree
2. Vérifiez la console JavaScript du navigateur
3. Vérifiez que tous les fichiers sont bien uploadés
4. Vérifiez les permissions des fichiers

## ✨ Prochaines Améliorations (Optionnel)

1. **Page "Mot de passe oublié"**
   - Envoi d'un email avec lien de réinitialisation
   - Génération de token temporaire

2. **Page "Changer mot de passe"**
   - Permettre aux clients de changer leur mot de passe
   - Validation du mot de passe actuel

3. **Notifications Email**
   - Envoi automatique des credentials aux nouveaux clients
   - Notification de changement de statut de devis

4. **Logs d'Activité**
   - Historique des connexions
   - Historique des modifications de devis

5. **Export de Données**
   - Export CSV des clients
   - Export PDF des devis

## 📝 Notes de Version

**Version** : 2.0.0  
**Date** : 13 novembre 2025  
**Auteur** : Devin AI

**Changements majeurs** :
- Refonte complète du système d'authentification
- Migration vers email + mot de passe pour tous les utilisateurs
- Génération de 20 comptes avec credentials sécurisés
- Création de 19 devis liés aux comptes clients
- Documentation complète

---

**🎉 Le site est maintenant prêt à être déployé et utilisé !**
