<?php
require_once __DIR__ . '/security.php';

setSecureCORS();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$response = [
    'logged_in' => false,
    'session_data' => null,
    'csrf_token' => generateCSRFToken()
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