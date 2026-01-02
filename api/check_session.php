<?php
// check_session.php - VERSION 100% FONCTIONNELLE
session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$response = [
    'logged_in' => false,
    'session_data' => null
];

// Check for new session structure (account-manager.php)
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $response['logged_in'] = true;
    $response['user_id'] = $_SESSION['user_id'] ?? null;
    $response['role'] = $_SESSION['user_role'] ?? 'client';
    $response['session_data'] = [
        'username' => $_SESSION['user_name'] ?? 'Utilisateur',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'client'
    ];
} 
// Fallback for legacy admin session
elseif (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $response['logged_in'] = true;
    $response['user_id'] = $_SESSION['user_id'] ?? 'admin'; // Fallback ID
    $response['role'] = 'admin';
    $response['session_data'] = [
        'username' => $_SESSION['admin_username'] ?? 'admin',
        'login_time' => $_SESSION['admin_login_time'] ?? time(),
        'ip' => $_SESSION['admin_ip'] ?? $_SERVER['REMOTE_ADDR']
    ];
}

echo json_encode($response);
