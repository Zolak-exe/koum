# Guide de Migration vers SQL (Neon / PostgreSQL)

Ce guide détaille les étapes pour migrer l'application **NEXT DRIVE IMPORT** d'un stockage basé sur des fichiers JSON vers une base de données SQL (PostgreSQL via Neon).

## 1. Structure de la Base de Données

Voici le schéma SQL nécessaire pour reproduire la structure de vos données actuelles.

### Table `users` (Comptes)
```sql
CREATE TABLE users (
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
```

### Table `devis` (Demandes de devis)
```sql
CREATE TABLE devis (
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
```

### Table `messages` (Chat)
```sql
CREATE TABLE messages (
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

## 2. Connexion à la Base de Données

Créez un fichier `api/db.php` pour gérer la connexion.

```php
<?php
function getDB() {
    $host = 'votre-host-neon.aws.neon.tech';
    $db   = 'neondb';
    $user = 'votre_user';
    $pass = 'votre_password';
    $port = "5432";

    $dsn = "pgsql:host=$host;port=$port;dbname=$db;";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        throw new \PDOException($e->getMessage(), (int)$e->getCode());
    }
}
?>
```

## 3. Script de Migration des Données

J'ai créé pour vous un script `tools/migrate_to_sql.php` (voir fichier joint dans le projet) qui lira vos fichiers JSON actuels et insérera les données dans votre nouvelle base de données.

## 4. Adaptation du Code PHP

Il faudra modifier vos fichiers dans `api/` pour remplacer les lectures/écritures JSON par des requêtes SQL.

### Exemple : `api/account-manager.php`

**Avant (JSON) :**
```php
function readAccounts() {
    $content = file_get_contents(ACCOUNTS_FILE);
    return json_decode($content, true) ?: [];
}
```

**Après (SQL) :**
```php
require_once 'db.php';

function readAccounts() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT * FROM users");
    return $stmt->fetchAll();
}

function createAccount($data) {
    $pdo = getDB();
    $sql = "INSERT INTO users (id, nom, email, password, role, created_at) VALUES (?, ?, ?, ?, ?, NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['id'], $data['nom'], $data['email'], $data['password'], $data['role']]);
}
```

## 5. Étapes pour passer en production

1.  Créez votre projet sur **Neon** (ou autre hébergeur PostgreSQL).
2.  Exécutez les requêtes SQL de création de tables (Section 1).
3.  Configurez `api/db.php` avec vos identifiants.
4.  Lancez le script de migration : `php tools/migrate_to_sql.php`.
5.  Remplacez progressivement les appels JSON par des appels SQL dans vos fichiers API.
