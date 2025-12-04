<?php

/**
 * API Chat Instantané - NEXT DRIVE IMPORT
 * Gestion des messages de chat entre admin et clients
 */

session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Fichier de données
define('CHAT_FILE', __DIR__ . '/../data/chat-messages.json');

// Fonction pour lire les messages
function readMessages()
{
    if (!file_exists(CHAT_FILE)) {
        file_put_contents(CHAT_FILE, json_encode([]));
        @chmod(CHAT_FILE, 0644);
    }
    $content = file_get_contents(CHAT_FILE);
    return json_decode($content, true) ?: [];
}

// Fonction pour sauvegarder les messages
function saveMessages($messages)
{
    $json = json_encode($messages, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents(CHAT_FILE, $json, LOCK_EX);
    @chmod(CHAT_FILE, 0644);
}

// Récupérer l'action demandée
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$action = $data['action'] ?? '';

try {
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

            $messages = readMessages();

            $newMessage = [
                'id' => 'msg_' . uniqid(),
                'user_id' => $userId,
                'user_email' => $userEmail,
                'user_name' => $userName,
                'message' => htmlspecialchars($message, ENT_QUOTES, 'UTF-8'),
                'is_admin' => $isAdmin,
                'timestamp' => date('Y-m-d H:i:s'),
                'read' => false
            ];

            $messages[] = $newMessage;
            saveMessages($messages);

            echo json_encode([
                'success' => true,
                'message' => 'Message envoyé',
                'data' => $newMessage
            ]);
            break;

        case 'get_messages':
            // Récupérer les messages
            $userId = $data['user_id'] ?? null;
            $userEmail = $data['user_email'] ?? null;
            $isAdmin = $data['is_admin'] ?? false;

            $messages = readMessages();

            if ($isAdmin) {
                // Admin voit tous les messages
                echo json_encode([
                    'success' => true,
                    'messages' => $messages
                ]);
            } else {
                // Client voit uniquement ses messages
                $userMessages = array_filter($messages, function ($msg) use ($userId, $userEmail) {
                    return ($msg['user_id'] === $userId || $msg['user_email'] === $userEmail);
                });

                echo json_encode([
                    'success' => true,
                    'messages' => array_values($userMessages)
                ]);
            }
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

            $messages = readMessages();

            foreach ($messages as &$msg) {
                if (in_array($msg['id'], $messageIds)) {
                    $msg['read'] = true;
                }
            }

            saveMessages($messages);

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

            $messages = readMessages();

            if ($isAdmin) {
                // Compter les messages non lus des clients
                $unreadCount = count(array_filter($messages, function ($msg) {
                    return !$msg['is_admin'] && !$msg['read'];
                }));
            } else {
                // Compter les messages non lus de l'admin pour ce client
                $unreadCount = count(array_filter($messages, function ($msg) use ($userId, $userEmail) {
                    return $msg['is_admin'] &&
                        ($msg['user_id'] === $userId || $msg['user_email'] === $userEmail) &&
                        !$msg['read'];
                }));
            }

            echo json_encode([
                'success' => true,
                'unread_count' => $unreadCount
            ]);
            break;

        case 'get_conversations':
            // Récupérer toutes les conversations (Admin uniquement)
            $messages = readMessages();

            // Grouper par utilisateur
            $conversations = [];
            foreach ($messages as $msg) {
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
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
