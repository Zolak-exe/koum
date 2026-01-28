<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';

setSecureCORS();
enforceCSRF();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Vérifier l'authentification
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);
$action = $data['action'] ?? $_GET['action'] ?? '';

try {
    $pdo = getDB();
    $currentUserId = $_SESSION['user_id'];
    $currentUserRole = $_SESSION['user_role'] ?? 'client';

    switch ($action) {
        case 'send_message':
            $devisId = $data['devis_id'] ?? '';
            $message = trim($data['message'] ?? '');

            if (empty($devisId) || empty($message)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Message vide ou ID manquant']);
                exit;
            }

            // Vérifier les droits
            if (!canAccessChat($pdo, $devisId, $currentUserId, $currentUserRole)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Accès refusé']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO messages (devis_id, sender_id, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$devisId, $currentUserId, $message]);

            echo json_encode(['success' => true, 'message' => 'Message envoyé']);
            break;

        case 'get_messages':
            $devisId = $_GET['devis_id'] ?? '';

            if (empty($devisId)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'ID devis requis']);
                exit;
            }

            // Vérifier les droits
            if (!canAccessChat($pdo, $devisId, $currentUserId, $currentUserRole)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Accès refusé']);
                exit;
            }

            // Récupérer les messages avec le nom de l'expéditeur
            $sql = "
                SELECT m.*, u.nom as sender_name, u.role as sender_role
                FROM messages m
                LEFT JOIN users u ON m.sender_id = u.id
                WHERE m.devis_id = ?
                ORDER BY m.created_at ASC
            ";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$devisId]);
            $messages = $stmt->fetchAll();

            echo json_encode(['success' => true, 'messages' => $messages]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Action inconnue']);
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
}

function canAccessChat($pdo, $devisId, $userId, $userRole)
{
    if ($userRole === 'admin')
        return true;

    $stmt = $pdo->prepare("SELECT user_id, user_email, claimed_by FROM devis WHERE id = ?");
    $stmt->execute([$devisId]);
    $devis = $stmt->fetch();

    if (!$devis)
        return false;

    if ($userRole === 'client') {
        // Check ID match
        if ($devis['user_id'] === $userId)
            return true;

        // Fallback: check email if available in session
        $sessionEmail = $_SESSION['user_email'] ?? '';
        if (!empty($sessionEmail) && $devis['user_email'] === $sessionEmail)
            return true;

        return false;
    }

    if ($userRole === 'vendeur') {
        return $devis['claimed_by'] === $userId;
    }

    return false;
}
?>