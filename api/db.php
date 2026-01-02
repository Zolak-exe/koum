<?php
/**
 * Configuration de la Base de Données (PostgreSQL / Neon)
 */

require_once __DIR__ . '/env.php';

function getDB() {
    $host = env('DB_HOST');
    $db   = env('DB_NAME');
    $user = env('DB_USER');
    $pass = env('DB_PASS');
    $port = env('DB_PORT', '5432');

    if (!$host || !$db || !$user || !$pass) {
        die("Erreur de configuration DB: Variables d'environnement manquantes.");
    }

    $dsn = "pgsql:host=$host;port=$port;dbname=$db;sslmode=require";
    
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        return new PDO($dsn, $user, $pass, $options);
    } catch (\PDOException $e) {
        // En production, ne pas afficher l'erreur brute pour sécurité
        error_log($e->getMessage());
        throw new \PDOException("Erreur de connexion à la base de données.", (int)$e->getCode());
    }
}
