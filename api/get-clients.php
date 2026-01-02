<?php
// get-clients.php - VERSION SQL (MIGRATED)
session_start();
require_once __DIR__ . '/db.php';

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

try {
    $pdo = getDB();

    if ($isAdmin) {
        // Admin voit tout avec les infos utilisateur (téléphone)
        $stmt = $pdo->query("
            SELECT d.*, u.telephone 
            FROM devis d 
            LEFT JOIN users u ON d.user_id = u.id 
            ORDER BY d.created_at DESC
        ");
        $clients = $stmt->fetchAll();
    } else {
        // Client voit uniquement ses demandes
        $userId = $_SESSION['user_id'] ?? '';
        $userEmail = $_SESSION['user_email'] ?? '';
        
        $stmt = $pdo->prepare("SELECT * FROM devis WHERE user_id = ? OR user_email = ? ORDER BY created_at DESC");
        $stmt->execute([$userId, $userEmail]);
        $clients = $stmt->fetchAll();
    }

    // Retourner les clients
    echo json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode([
        'error' => 'Erreur serveur',
        'message' => 'Erreur interne: ' . $e->getMessage() // Exposed for debugging
    ]);
}
