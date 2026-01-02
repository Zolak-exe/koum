# 📘 Guide de Configuration Complet : Migration vers SQL

Ce guide vous accompagne pas à pas pour configurer votre application **NEXT DRIVE IMPORT** avec une base de données PostgreSQL (hébergée sur Neon).

---

## 1. Prérequis

Assurez-vous que votre serveur PHP dispose de l'extension `pdo_pgsql`.
Sur Debian/Ubuntu :
```bash
sudo apt-get install php-pgsql
sudo service apache2 restart
```

---

## 2. Configuration de la Base de Données (Neon)

1.  Créez un compte et un projet sur [Neon.tech](https://neon.tech).
2.  Dans votre tableau de bord Neon, récupérez la **chaîne de connexion** (Connection String). Elle ressemble à :
    `postgres://user:password@ep-xyz.aws.neon.tech/neondb`
3.  Allez dans l'éditeur SQL de Neon et exécutez les commandes suivantes pour créer vos tables :

```sql
-- Création des tables
CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(50) PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    telephone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'client',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    active BOOLEAN DEFAULT TRUE,
    password_reset_required BOOLEAN DEFAULT FALSE
);

CREATE TABLE IF NOT EXISTS devis (
    id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50) REFERENCES users(id),
    user_name VARCHAR(100),
    user_email VARCHAR(150),
    marque VARCHAR(50),
    modele VARCHAR(50),
    budget DECIMAL(12, 2),
    annee_minimum INT,
    kilometrage_max INT,
    options TEXT,
    commentaires TEXT,
    statut VARCHAR(50) DEFAULT 'En attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    response TEXT,
    response_date TIMESTAMP
);

CREATE TABLE IF NOT EXISTS messages (
    id VARCHAR(50) PRIMARY KEY,
    user_id VARCHAR(50) REFERENCES users(id),
    user_email VARCHAR(150),
    user_name VARCHAR(100),
    message TEXT NOT NULL,
    is_admin BOOLEAN DEFAULT FALSE,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read BOOLEAN DEFAULT FALSE
);
```

---

## 3. Configuration de l'Application

### A. Fichier de connexion (`api/db.php`)

J'ai créé ce fichier pour vous. Vous devez l'éditer avec vos informations Neon.

1.  Ouvrez `api/db.php`.
2.  Remplissez les variables :
    ```php
    $host = 'ep-votre-endpoint.aws.neon.tech';
    $db   = 'neondb';
    $user = 'votre_user';
    $pass = 'votre_password';
    ```

### B. Migration des Données (JSON -> SQL)

Une fois la base configurée, transférez vos données existantes :

1.  Ouvrez un terminal dans le dossier du projet.
2.  Lancez la commande :
    ```bash
    php tools/migrate_to_sql.php
    ```
3.  Vérifiez que le script affiche "✅ Migration terminée !".

---

## 4. Adaptation du Code (Transition Finale)

C'est l'étape la plus importante. Vous devez modifier vos fichiers PHP pour qu'ils utilisent `api/db.php` au lieu des fichiers JSON.

Voici la liste des fichiers à modifier et un exemple de la logique à appliquer.

### Fichiers concernés :
*   `api/account-manager.php` (Gestion utilisateurs)
*   `api/auth.php` (Connexion/Inscription)
*   `api/devis-manager.php` (Gestion devis)
*   `api/submit-devis.php` (Création devis)
*   `api/chat.php` (Messagerie)
*   `api/get-clients.php` & `api/save_clients.php`

### Exemple de modification : `api/account-manager.php`

**Avant (Lecture JSON) :**
```php
function readAccounts() {
    $content = file_get_contents(ACCOUNTS_FILE);
    return json_decode($content, true) ?: [];
}
```

**Après (Lecture SQL) :**
```php
require_once __DIR__ . '/db.php'; // Inclure la connexion

function readAccounts() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}
```

**Avant (Sauvegarde JSON) :**
```php
function saveAccounts($accounts) {
    file_put_contents(ACCOUNTS_FILE, json_encode($accounts));
}
```

**Après (Sauvegarde SQL) :**
*La fonction `saveAccounts` disparaît souvent au profit de fonctions `createAccount` ou `updateAccount` spécifiques.*

```php
function createAccount($user) {
    $pdo = getDB();
    $sql = "INSERT INTO users (id, nom, email, password, role) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user['id'], $user['nom'], $user['email'], $user['password'], $user['role']]);
}
```

---

## 5. Vérification

Une fois le code modifié :
1.  Supprimez (ou renommez) les fichiers `.json` dans le dossier `data/` pour être sûr que l'application ne les utilise plus.
2.  Testez l'inscription, la connexion et la création de devis.
