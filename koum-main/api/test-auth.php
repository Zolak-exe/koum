<?php
// Test simple de l'authentification
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Test Auth System</h1>";

// Test 1: Vérifier si le fichier clients.json est accessible
echo "<h2>Test 1: Fichier clients.json</h2>";
$clientsFile = '../data/clients.json';

if (file_exists($clientsFile)) {
    echo "✅ Le fichier existe<br>";
    echo "Permissions: " . substr(sprintf('%o', fileperms($clientsFile)), -4) . "<br>";

    $content = file_get_contents($clientsFile);
    echo "Contenu actuel:<br><pre>" . htmlspecialchars($content) . "</pre>";
} else {
    echo "❌ Le fichier n'existe pas<br>";
    echo "Tentative de création...<br>";

    if (file_put_contents($clientsFile, '[]') !== false) {
        echo "✅ Fichier créé avec succès<br>";
    } else {
        echo "❌ Impossible de créer le fichier<br>";
    }
}

// Test 2: Sessions PHP
echo "<h2>Test 2: Sessions PHP</h2>";
session_start();
echo "Session ID: " . session_id() . "<br>";
echo "Session active: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ Oui" : "❌ Non") . "<br>";

// Test 3: Test d'inscription
echo "<h2>Test 3: Test inscription</h2>";
$testData = json_encode([
    'action' => 'register',
    'nom' => 'Test User',
    'email' => 'test@example.com',
    'telephone' => '0612345678'
]);

echo "Données test: <pre>" . htmlspecialchars($testData) . "</pre>";

// Test 4: Permissions d'écriture
echo "<h2>Test 4: Permissions d'écriture</h2>";
$testFile = 'test-write.txt';
if (file_put_contents($testFile, 'test') !== false) {
    echo "✅ Écriture possible<br>";
    unlink($testFile);
} else {
    echo "❌ Écriture impossible<br>";
}

echo "<hr>";
echo "<a href='index.html'>Retour à l'accueil</a>";
