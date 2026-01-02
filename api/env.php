<?php
/**
 * Chargeur de variables d'environnement (.env)
 * Compatible avec les environnements locaux et cloud (Heroku/Render)
 */

function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);
        
        // Ne pas écraser les variables système existantes (important pour Render/Heroku)
        if (getenv($name) === false) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
}

// Charger le .env s'il existe (développement local)
loadEnv(__DIR__ . '/../.env');

// Helper pour récupérer une variable avec valeur par défaut
function env($key, $default = null) {
    $value = getenv($key);
    if ($value === false) {
        return $default;
    }
    return $value;
}
