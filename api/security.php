<?php
require_once __DIR__ . '/env.php';

if (session_status() === PHP_SESSION_NONE) {
    // Déterminer si on doit forcer le HTTPS (Activé en prod ou si détecté via proxy Render/Heroku)
    $is_secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || env('APP_ENV') === 'production';

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $is_secure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

/**
 * Generate a CSRF token and store it in the session
 */
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate the CSRF token from the request
 */
function validateCSRFToken($token)
{
    if (!isset($_SESSION['csrf_token'])) {
        file_put_contents(__DIR__ . '/backend_errors.log', "[" . date('Y-m-d H:i:s') . "] CSRF FAIL: No token in session\n", FILE_APPEND);
        return false;
    }
    if (empty($token)) {
        file_put_contents(__DIR__ . '/backend_errors.log', "[" . date('Y-m-d H:i:s') . "] CSRF FAIL: Empty token in request\n", FILE_APPEND);
        return false;
    }
    $match = hash_equals($_SESSION['csrf_token'], $token);
    if (!$match) {
        file_put_contents(__DIR__ . '/backend_errors.log', "[" . date('Y-m-d H:i:s') . "] CSRF FAIL: Token mismatch. Received: " . substr($token, 0, 8) . "... Expected: " . substr($_SESSION['csrf_token'], 0, 8) . "...\n", FILE_APPEND);
    }
    return $match;
}

/**
 * Get the CSRF token from headers or POST data
 */
function getCSRFTokenFromRequest()
{
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($token)) {
        $input = json_decode(file_get_contents('php://input'), true);
        $token = $input['csrf_token'] ?? '';
    }
    return $token;
}

/**
 * Enforce CSRF protection for non-GET requests
 */
function enforceCSRF()
{
    if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'OPTIONS') {
        $token = getCSRFTokenFromRequest();

        // Debug Log
        $debugInfo = "[" . date('Y-m-d H:i:s') . "] CSRF CHECK: " . $_SERVER['REQUEST_URI'] . "\n";
        $debugInfo .= " - Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
        $debugInfo .= " - Session ID: " . session_id() . "\n";
        $debugInfo .= " - Token in Request: " . (empty($token) ? 'EMPTY' : substr($token, 0, 8) . '...') . "\n";
        $debugInfo .= " - Token in Session: " . (!isset($_SESSION['csrf_token']) ? 'MISSING' : substr($_SESSION['csrf_token'], 0, 8) . '...') . "\n";
        file_put_contents(__DIR__ . '/backend_errors.log', $debugInfo, FILE_APPEND);

        if (!validateCSRFToken($token)) {
            http_response_code(403);
            die(json_encode([
                'success' => false,
                'message' => 'Erreur de sécurité : Jeton CSRF invalide ou manquant.'
            ]));
        }
    }
}

/**
 * Set secure CORS headers
 */
function setSecureCORS()
{
    $allowed_origins = [
        'https://nextdriveimport.onrender.com',
        'https://nextdriveimport.fr',
        'http://localhost:8000',
        'http://127.0.0.1:8000'
    ];

    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($origin, $allowed_origins)) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        // Fallback or deny
        header("Access-Control-Allow-Origin: " . $allowed_origins[0]);
    }

    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-CSRF-Token');
    header('Access-Control-Allow-Credentials: true');
}