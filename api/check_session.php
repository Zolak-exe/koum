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

if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    $response['logged_in'] = true;
    $response['session_data'] = [
        'username' => $_SESSION['admin_username'] ?? 'admin',
        'login_time' => $_SESSION['admin_login_time'] ?? time(),
        'ip' => $_SESSION['admin_ip'] ?? $_SERVER['REMOTE_ADDR']
    ];
}

echo json_encode($response);
