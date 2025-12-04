<?php
// init.php - INITIALISATION DU SITE
header('Content-Type: text/html; charset=utf-8');

echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Initialisation - NEXT DRIVE IMPORT</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #0a0a0a;
            color: #fff;
            padding: 40px;
            max-width: 800px;
            margin: 0 auto;
        }
        h1 { color: #FF6B35; }
        .success { background: #10b981; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .error { background: #ef4444; padding: 15px; border-radius: 8px; margin: 10px 0; }
        .info { background: #3b82f6; padding: 15px; border-radius: 8px; margin: 10px 0; }
        code { background: #1a1a1a; padding: 2px 6px; border-radius: 4px; }
        pre { background: #1a1a1a; padding: 15px; border-radius: 8px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🚀 Initialisation NEXT DRIVE IMPORT</h1>
    <p>Ce script initialise les fichiers nécessaires au bon fonctionnement du site.</p>
    <hr>
';

$results = [];

// 1. Créer clients.json
$clientsFile = __DIR__ . '/../data/clients.json';
if (!file_exists($clientsFile)) {
    if (file_put_contents($clientsFile, '[]')) {
        $results[] = ['success' => true, 'message' => '✅ Fichier clients.json créé'];
    } else {
        $results[] = ['success' => false, 'message' => '❌ Impossible de créer clients.json'];
    }
} else {
    $results[] = ['success' => true, 'message' => 'ℹ️ clients.json existe déjà'];
}

// 2. Vérifier les permissions
if (is_writable(__DIR__)) {
    $results[] = ['success' => true, 'message' => '✅ Dossier accessible en écriture'];
} else {
    $results[] = ['success' => false, 'message' => '❌ Dossier non accessible en écriture (chmod 755 requis)'];
}

// 3. Tester la création de session
session_start();
$_SESSION['test'] = 'ok';
if (isset($_SESSION['test'])) {
    $results[] = ['success' => true, 'message' => '✅ Sessions PHP fonctionnelles'];
    unset($_SESSION['test']);
} else {
    $results[] = ['success' => false, 'message' => '❌ Sessions PHP non fonctionnelles'];
}

// 4. Vérifier JSON
$testData = ['test' => 'ok', 'timestamp' => date('Y-m-d H:i:s')];
$jsonTest = json_encode($testData);
if (json_last_error() === JSON_ERROR_NONE) {
    $results[] = ['success' => true, 'message' => '✅ JSON fonctionnel'];
} else {
    $results[] = ['success' => false, 'message' => '❌ Erreur JSON: ' . json_last_error_msg()];
}

// 5. Vérifier mail()
if (function_exists('mail')) {
    $results[] = ['success' => true, 'message' => '⚠️ Fonction mail() disponible (peut ne pas fonctionner sur Infinity Free)'];
} else {
    $results[] = ['success' => false, 'message' => '❌ Fonction mail() non disponible'];
}

// 6. Créer un client de test
$testClient = [
    'id' => 'client_test_' . uniqid(),
    'nom' => 'Test User',
    'email' => 'test@example.com',
    'telephone' => '0612345678',
    'created_at' => date('Y-m-d H:i:s'),
    'statut' => 'test',
    'vehicule' => [
        'marque' => 'BMW',
        'modele' => 'M3',
        'budget' => 50000
    ]
];

$clients = json_decode(file_get_contents($clientsFile), true);
if (!is_array($clients)) $clients = [];
$clients[] = $testClient;

if (file_put_contents($clientsFile, json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
    $results[] = ['success' => true, 'message' => '✅ Client de test ajouté'];
} else {
    $results[] = ['success' => false, 'message' => '❌ Impossible d\'ajouter le client de test'];
}

// Afficher les résultats
foreach ($results as $result) {
    $class = $result['success'] ? 'success' : 'error';
    echo "<div class='$class'>{$result['message']}</div>";
}

echo '
    <hr>
    <h2>📝 Informations Système</h2>
    <pre>';
echo 'PHP Version: ' . PHP_VERSION . "\n";
echo 'Dossier: ' . __DIR__ . "\n";
echo 'Permissions: ' . substr(sprintf('%o', fileperms(__DIR__)), -4) . "\n";
echo 'Session ID: ' . session_id() . "\n";
echo 'Timezone: ' . date_default_timezone_get() . "\n";
echo '</pre>

    <h2>🔐 Identifiants Admin Par Défaut</h2>
    <div class="info">
        <strong>Username:</strong> <code>admin</code><br>
        <strong>Password:</strong> <code>NextDrive2024!</code><br>
        <br>
        ⚠️ <strong>CHANGEZ CES IDENTIFIANTS</strong> dans <code>login_check.php</code> lignes 9-10
    </div>

    <h2>✅ Prochaines Étapes</h2>
    <ol>
        <li>Changez les identifiants admin dans <code>login_check.php</code></li>
        <li>Testez la page d\'accueil: <a href="index.html" style="color: #FF6B35;">index.html</a></li>
        <li>Testez l\'admin: <a href="login.html" style="color: #FF6B35;">login.html</a></li>
        <li>Testez l\'espace client: <a href="client.html" style="color: #FF6B35;">client.html</a></li>
        <li>Supprimez ce fichier après l\'initialisation</li>
    </ol>

    <hr>
    <p><a href="index.html" style="color: #FF6B35; text-decoration: none;">← Retour au site</a></p>
</body>
</html>';
