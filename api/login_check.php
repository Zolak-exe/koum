<?php
// login_check.php - VERSION 100% FONCTIONNELLE
session_start();
require_once __DIR__ . '/env.php';
require_once __DIR__ . '/security.php';

setSecureCORS();
enforceCSRF();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// CONFIGURATION
define('ADMIN_USERNAME', env('ADMIN_USERNAME', 'admin'));
define('ADMIN_PASSWORD', env('ADMIN_PASSWORD', 'change_me_in_env'));

// Lire les données JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

$username = isset($data['username']) ? trim($data['username']) : '';
$password = isset($data['password']) ? $data['password'] : '';

// Rate limiting
$ip = $_SERVER['REMOTE_ADDR'];
$rate_limit_file = __DIR__ . '/rate_limit_admin_' . hash('sha256', $ip) . '.txt';
$max_attempts = 5;
$lockout_time = 900; // 15 minutes

if (file_exists($rate_limit_file)) {
    $attempts = json_decode(file_get_contents($rate_limit_file), true);
    if ($attempts['count'] >= $max_attempts && time() - $attempts['last_attempt'] < $lockout_time) {
        http_response_code(429);
        $remaining = ceil(($lockout_time - (time() - $attempts['last_attempt'])) / 60);
        echo json_encode([
            'success' => false,
            'message' => "Trop de tentatives. Réessayez dans $remaining minutes.",
            'locked_until' => $attempts['last_attempt'] + $lockout_time
        ]);
        exit;
    }
}

// Validation
if (!$username || !$password) {
    log_attempt($rate_limit_file);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Identifiant et mot de passe requis']);
    exit;
}

// Vérification
if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
    // Succès
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username'] = $username;
    $_SESSION['admin_login_time'] = time();
    $_SESSION['admin_ip'] = $ip;

    // Supprimer le rate limit
    if (file_exists($rate_limit_file)) {
        unlink($rate_limit_file);
    }

    // Logger
    error_log("✅ Connexion admin réussie: $username - IP: $ip", 3, __DIR__ . '/admin_login.log');

    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie',
        'username' => $username
    ]);
} else {
    log_attempt($rate_limit_file);
    error_log("❌ Tentative échouée: $username - IP: $ip", 3, __DIR__ . '/admin_failed_attempts.log');

    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Identifiant ou mot de passe incorrect']);
}

function log_attempt($rate_limit_file)
{
    $attempts = file_exists($rate_limit_file) ? json_decode(file_get_contents($rate_limit_file), true) : ['count' => 0, 'last_attempt' => 0];
    $attempts['count']++;
    $attempts['last_attempt'] = time();
    file_put_contents($rate_limit_file, json_encode($attempts));
}
