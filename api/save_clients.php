<?php
// save_clients.php
header('Content-Type: application/json');

// Vérifier la méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérifier la session (sécurité)
session_start();
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

// Récupérer les données
$input = file_get_contents('php://input');
$clients = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON invalide']);
    exit;
}

// Sauvegarder dans le fichier
try {
    $result = file_put_contents('../data/clients.json', json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    if ($result === false) {
        throw new Exception('Impossible d\'écrire dans le fichier');
    }
    
    echo json_encode(['success' => true, 'message' => 'Données sauvegardées']);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur sauvegarde: ' . $e->getMessage()]);
}
?>