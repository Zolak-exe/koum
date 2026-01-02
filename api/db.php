<?php
/**
 * Configuration de la Base de Données (PostgreSQL / Neon)
 * 
 * Remplissez les variables ci-dessous avec les informations fournies par Neon.
 */

function getDB() {
    // -------------------------------------------------------------------------
    // CONFIGURATION À REMPLIR
    // -------------------------------------------------------------------------
    $host = 'ep-raspy-pine-abbdh2ut-pooler.eu-west-2.aws.neon.tech';
    $db   = 'neondb';
    $user = 'neondb_owner';
    $pass = 'npg_SxUnXsjIKq80';
    $port = "5432";
    // -------------------------------------------------------------------------

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
