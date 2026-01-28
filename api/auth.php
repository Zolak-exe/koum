<?php
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/db.php';

setSecureCORS();
enforceCSRF();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

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
    // Username ignored in SQL schema, using nom/email
    $email = isset($data['email']) ? filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL) : false;
    $telephone = isset($data['telephone']) ? htmlspecialchars(trim($data['telephone']), ENT_QUOTES, 'UTF-8') : '';
    $password = isset($data['password']) ? $data['password'] : '';

    if (!$nom || !$email || !$telephone) {
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

    try {
        $pdo = getDB();

        // Vérifier si l'email existe déjà
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            http_response_code(409);
            echo json_encode(['success' => false, 'message' => 'Email déjà utilisé']);
            exit;
        }

        // Créer un nouveau compte
        $id = uniqid('acc_', true);
        $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;
        $role = 'client';

        $sql = "INSERT INTO users (id, nom, email, telephone, password, role, created_at, updated_at, active) VALUES (?, ?, ?,
?, ?, ?, NOW(), NOW(), true)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id, $nom, $email, $telephone_clean, $hashedPassword, $role]);

        $newAccount = [
            'id' => $id,
            'nom' => $nom,
            'email' => $email,
            'telephone' => $telephone_clean,
            'role' => $role
        ];

        $_SESSION['client_logged_in'] = true;
        $_SESSION['client_data'] = $newAccount;
        $_SESSION['user_role'] = 'client';
        $_SESSION['logged_in'] = true;
        $_SESSION['user_id'] = $id;
        $_SESSION['user_name'] = $nom;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_telephone'] = $telephone_clean;

        echo json_encode([
            'success' => true,
            'message' => 'Inscription réussie',
            'client' => $newAccount
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        error_log($e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Erreur serveur interne']);
    }
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

    try {
        $pdo = getDB();

        // Chercher l'utilisateur par email ou nom (pseudo)
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? OR nom = ?");
        $stmt->execute([$identifier, $identifier]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            http_response_code(401);
            echo json_encode(['success' => false, 'message' => 'Identifiant ou mot de passe incorrect']);
            exit;
        }

        $_SESSION['client_logged_in'] = true;
        $_SESSION['client_data'] = $user;
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['nom'];
        $_SESSION['user_telephone'] = $user['telephone'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['user_email'] = $user['email'];

        if ($user['role'] === 'admin') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $user['nom']; // Using nom as username for session
        }

        echo json_encode([
            'success' => true,
            'message' => 'Connexion réussie',
            'client' => $user
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur serveur']);
    }
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
    // Clear all known session keys to ensure compatibility
    $_SESSION['client_logged_in'] = false;
    unset($_SESSION['client_data']);
    $_SESSION['logged_in'] = false;
    unset($_SESSION['user_email']);
    $_SESSION['admin_logged_in'] = false;
    unset($_SESSION['admin_username']);
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Déconnexion réussie']);
}