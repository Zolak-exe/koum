<?php
// get-clients.php - VERSION 100% FONCTIONNELLE
session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');

// Vérifier la session (admin ou client)
$isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
$isClient = isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

if (!$isAdmin && !$isClient) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé', 'message' => 'Veuillez vous connecter']);
    exit;
}

// Lire le fichier clients.json
$clientsFile = __DIR__ . '/../data/clients.json';

if (!file_exists($clientsFile)) {
    // Créer le fichier s'il n'existe pas
    file_put_contents($clientsFile, '[]');
    echo '[]';
    exit;
}

$content = file_get_contents($clientsFile);

// Valider le JSON
$clients = json_decode($content, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Fichier JSON corrompu',
        'message' => json_last_error_msg()
    ]);
    exit;
}

if (!is_array($clients)) {
    $clients = [];
}

// Si c'est un client, filtrer pour ne renvoyer que ses demandes
if (!$isAdmin && $isClient) {
    $userEmail = $_SESSION['user_email'] ?? '';
    $clients = array_values(array_filter($clients, function ($client) use ($userEmail) {
        return isset($client['email']) && strtolower($client['email']) === strtolower($userEmail);
    }));
}

// Retourner les clients
echo json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
