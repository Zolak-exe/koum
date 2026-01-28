<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';

setSecureCORS();
enforceCSRF();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Vérifier la session (admin, vendeur ou client)
$isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
$isVendeur = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'vendeur';
$isClient = (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) || (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true);

if (!$isAdmin && !$isVendeur && !$isClient) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé', 'message' => 'Veuillez vous connecter']);
    exit;
}

try {
    $pdo = getDB();

    if ($isAdmin) {
        // Admin voit tout avec les infos utilisateur (téléphone) et qui a claim
        $stmt = $pdo->query("
            SELECT d.*, u.telephone, u_claim.nom as claimed_by_name
            FROM devis d 
            LEFT JOIN users u ON d.user_id = u.id 
            LEFT JOIN users u_claim ON d.claimed_by = u_claim.id
            ORDER BY d.created_at DESC
        ");
        $clients = $stmt->fetchAll();
    } elseif ($isVendeur) {
        // Vendeur voit :
        // 1. Les devis non claim (claimed_by IS NULL)
        // 2. Les devis qu'il a claim (claimed_by = user_id)
        $userId = $_SESSION['user_id'] ?? '';

        $stmt = $pdo->prepare("
            SELECT d.*, u.telephone 
            FROM devis d 
            LEFT JOIN users u ON d.user_id = u.id 
            WHERE d.claimed_by IS NULL OR d.claimed_by = ?
            ORDER BY d.created_at DESC
        ");
        $stmt->execute([$userId]);
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
