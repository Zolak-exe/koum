<?php
// auth.php - VERSION 100% FONCTIONNELLE
session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Lire les données JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

$action = $data['action'] ?? '';

switch ($action) {
    case 'register':
        handleRegistration($data);
        break;
    case 'login':
        handleLogin($data);
        break;
    case 'check_session':
        checkSession();
        break;
    case 'logout':
        handleLogout();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action invalide']);
}

function handleRegistration($data)
{
    $nom = isset($data['nom']) ? htmlspecialchars(trim($data['nom']), ENT_QUOTES, 'UTF-8') : '';
    $username = isset($data['username']) ? htmlspecialchars(trim($data['username']), ENT_QUOTES, 'UTF-8') : '';
    $email = isset($data['email']) ? filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL) : false;
    $telephone = isset($data['telephone']) ? htmlspecialchars(trim($data['telephone']), ENT_QUOTES, 'UTF-8') : '';
    $password = isset($data['password']) ? $data['password'] : '';

    if (!$nom || !$username || !$email || !$telephone) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Tous les champs sont requis']);
        exit;
    }

    // Validation téléphone
    $telephone_clean = preg_replace('/\s+/', '', $telephone);
    if (!preg_match('/^(\+33|0)[1-9](\d{2}){4}$/', $telephone_clean)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Numéro de téléphone invalide (format: 06 12 34 56 78)']);
        exit;
    }

    // Vérifier si l'email ou username existe déjà
    $accountsFile = __DIR__ . '/../data/accounts.json';

    if (!file_exists($accountsFile)) {
        file_put_contents($accountsFile, '[]');
    }

    $accounts = json_decode(file_get_contents($accountsFile), true);
    if (!is_array($accounts)) {
        $accounts = [];
    }

    // Chercher le compte existant
    foreach ($accounts as $account) {
        if (strtolower($account['email']) === strtolower($email)) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email déjà utilisé']);
            exit;
        }
        if (isset($account['username']) && strtolower($account['username']) === strtolower($username)) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Username déjà utilisé']);
            exit;
        }
    }

    // Créer un nouveau compte
    $newAccount = [
        'id' => uniqid('acc_', true),
        'nom' => $nom,
        'username' => $username,
        'email' => $email,
        'telephone' => $telephone_clean,
        'password' => !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null,
        'role' => 'client',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'active' => true
    ];

    $accounts[] = $newAccount;
    file_put_contents($accountsFile, json_encode($accounts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

    $_SESSION['client_logged_in'] = true;
    $_SESSION['client_data'] = $newAccount;
    $_SESSION['user_role'] = 'client';

    echo json_encode([
        'success' => true,
        'message' => 'Inscription réussie',
        'client' => $newAccount
    ]);
}

function handleLogin($data)
{
    $identifier = isset($data['identifier']) ? trim($data['identifier']) : '';
    $password = isset($data['password']) ? $data['password'] : '';

    if (empty($identifier) || empty($password)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Identifiant et mot de passe requis']);
        exit;
    }

    // Chercher le client dans accounts.json
    $accountsFile = __DIR__ . '/../data/accounts.json';

    if (!file_exists($accountsFile)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Aucun compte trouvé']);
        exit;
    }

    $accounts = json_decode(file_get_contents($accountsFile), true);
    if (!is_array($accounts)) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Aucun compte trouvé']);
        exit;
    }

    $foundAccount = null;
    foreach ($accounts as $account) {
        // Vérifier si l'identifiant correspond à l'email OU au username
        $matchesEmail = strtolower($account['email']) === strtolower($identifier);
        $matchesUsername = isset($account['username']) && strtolower($account['username']) === strtolower($identifier);

        if ($matchesEmail || $matchesUsername) {
            // Vérifier le mot de passe
            if (!empty($account['password']) && password_verify($password, $account['password'])) {
                $foundAccount = $account;
                break;
            }
        }
    }

    if (!$foundAccount) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Identifiant ou mot de passe incorrect']);
        exit;
    }

    $_SESSION['client_logged_in'] = true;
    $_SESSION['client_data'] = $foundAccount;
    $_SESSION['user_role'] = $foundAccount['role'] ?? 'client';

    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie',
        'client' => $foundAccount
    ]);
}

function checkSession()
{
    if (isset($_SESSION['client_logged_in']) && $_SESSION['client_logged_in'] === true) {
        echo json_encode([
            'authenticated' => true,
            'client' => $_SESSION['client_data']
        ]);
    } else {
        echo json_encode(['authenticated' => false]);
    }
}

function handleLogout()
{
    $_SESSION['client_logged_in'] = false;
    unset($_SESSION['client_data']);
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Déconnexion réussie']);
}
