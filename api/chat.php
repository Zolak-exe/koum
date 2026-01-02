<?php

/**
 * API Chat Instantané - NEXT DRIVE IMPORT
 * Gestion des messages de chat entre admin et clients
 * VERSION SQL (MIGRATED)
 */

session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Récupérer l'action demandée
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$action = $data['action'] ?? '';

try {
    $pdo = getDB();

    switch ($action) {
        case 'send_message':
            // Envoyer un nouveau message
            $userId = $data['user_id'] ?? null;
            $userEmail = $data['user_email'] ?? null;
            $userName = $data['user_name'] ?? null;
            $message = trim($data['message'] ?? '');
            $isAdmin = $data['is_admin'] ?? false;

            if (empty($message)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Message vide'
                ]);
                exit;
            }

            if (!$isAdmin && empty($userId) && empty($userEmail)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Identifiant utilisateur manquant'
                ]);
                exit;
            }

            $id = 'msg_' . uniqid();
            $timestamp = date('Y-m-d H:i:s');
            $cleanMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

            $sql = "INSERT INTO messages (id, user_id, user_email, user_name, message, is_admin, timestamp, read) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, false)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $id,
                $userId,
                $userEmail,
                $userName,
                $cleanMessage,
                $isAdmin ? 1 : 0,
                $timestamp
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Message envoyé',
                'data' => [
                    'id' => $id,
                    'user_id' => $userId,
                    'user_email' => $userEmail,
                    'user_name' => $userName,
                    'message' => $cleanMessage,
                    'is_admin' => $isAdmin,
                    'timestamp' => $timestamp,
                    'read' => false
                ]
            ]);
            break;

        case 'get_messages':
            // Récupérer les messages
            $userId = $data['user_id'] ?? null;
            $userEmail = $data['user_email'] ?? null;
            $isAdmin = $data['is_admin'] ?? false;

            if ($isAdmin) {
                // Admin voit tous les messages
                $stmt = $pdo->query("SELECT * FROM messages ORDER BY timestamp ASC");
                $messages = $stmt->fetchAll();
            } else {
                // Client voit uniquement ses messages
                $stmt = $pdo->prepare("SELECT * FROM messages WHERE user_id = ? OR user_email = ? ORDER BY timestamp ASC");
                $stmt->execute([$userId, $userEmail]);
                $messages = $stmt->fetchAll();
            }

            // Convert boolean fields
            foreach ($messages as &$msg) {
                $msg['is_admin'] = (bool)$msg['is_admin'];
                $msg['read'] = (bool)$msg['read'];
            }

            echo json_encode([
                'success' => true,
                'messages' => $messages
            ]);
            break;

        case 'mark_as_read':
            // Marquer les messages comme lus
            $messageIds = $data['message_ids'] ?? [];

            if (empty($messageIds)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'IDs de messages manquants'
                ]);
                exit;
            }

            // Create placeholders for IN clause
            $placeholders = implode(',', array_fill(0, count($messageIds), '?'));
            $stmt = $pdo->prepare("UPDATE messages SET read = true WHERE id IN ($placeholders)");
            $stmt->execute($messageIds);

            echo json_encode([
                'success' => true,
                'message' => 'Messages marqués comme lus'
            ]);
            break;

        case 'get_unread_count':
            // Compter les messages non lus
            $userId = $data['user_id'] ?? null;
            $userEmail = $data['user_email'] ?? null;
            $isAdmin = $data['is_admin'] ?? false;

            if ($isAdmin) {
                // Compter les messages non lus des clients (is_admin = false AND read = false)
                $stmt = $pdo->query("SELECT COUNT(*) FROM messages WHERE is_admin = false AND read = false");
                $unreadCount = $stmt->fetchColumn();
            } else {
                // Compter les messages non lus de l'admin pour ce client
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE is_admin = true AND (user_id = ? OR user_email = ?) AND read = false");
                $stmt->execute([$userId, $userEmail]);
                $unreadCount = $stmt->fetchColumn();
            }

            echo json_encode([
                'success' => true,
                'unread_count' => $unreadCount
            ]);
            break;

        case 'get_conversations':
            // Récupérer toutes les conversations (Admin uniquement)
            $stmt = $pdo->query("SELECT * FROM messages ORDER BY timestamp ASC");
            $messages = $stmt->fetchAll();

            // Grouper par utilisateur (PHP logic preserved)
            $conversations = [];
            foreach ($messages as $msg) {
                $msg['is_admin'] = (bool)$msg['is_admin'];
                $msg['read'] = (bool)$msg['read'];

                $key = $msg['user_email'] ?? $msg['user_id'] ?? 'unknown';
                if (!isset($conversations[$key])) {
                    $conversations[$key] = [
                        'user_id' => $msg['user_id'],
                        'user_email' => $msg['user_email'],
                        'user_name' => $msg['user_name'],
                        'messages' => [],
                        'unread_count' => 0,
                        'last_message' => null
                    ];
                }
                $conversations[$key]['messages'][] = $msg;
                if (!$msg['is_admin'] && !$msg['read']) {
                    $conversations[$key]['unread_count']++;
                }
                $conversations[$key]['last_message'] = $msg;
            }

            echo json_encode([
                'success' => true,
                'conversations' => array_values($conversations)
            ]);
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Action invalide'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur interne'
    ]);
}

