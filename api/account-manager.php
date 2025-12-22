<?php
/**
 * Gestionnaire de Comptes - NEXT DRIVE IMPORT
 * Gestion des comptes utilisateurs avec rôles (admin/client)
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

// Fichiers de données
define('ACCOUNTS_FILE', __DIR__ . '/../data/accounts.json');
define('DEVIS_FILE', __DIR__ . '/../data/devis.json');

// Fonction pour lire les comptes
function readAccounts()
{
    if (!file_exists(ACCOUNTS_FILE)) {
        file_put_contents(ACCOUNTS_FILE, json_encode([]));
        @chmod(ACCOUNTS_FILE, 0644);
    }
    $content = file_get_contents(ACCOUNTS_FILE);
    return json_decode($content, true) ?: [];
}

// Fonction pour sauvegarder les comptes
function saveAccounts($accounts)
{
    $json = json_encode($accounts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents(ACCOUNTS_FILE, $json, LOCK_EX);
    @chmod(ACCOUNTS_FILE, 0644);
}

// Fonction pour lire les devis
function readDevis()
{
    if (!file_exists(DEVIS_FILE)) {
        file_put_contents(DEVIS_FILE, json_encode([]));
        @chmod(DEVIS_FILE, 0644);
    }
    $content = file_get_contents(DEVIS_FILE);
    return json_decode($content, true) ?: [];
}

// Fonction pour sauvegarder les devis
function saveDevis($devis)
{
    $json = json_encode($devis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents(DEVIS_FILE, $json, LOCK_EX);
    @chmod(DEVIS_FILE, 0644);
}

// Récupérer l'action demandée
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data && json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Données JSON invalides'
    ]);
    exit;
}

$action = $data['action'] ?? '';

try {
    switch ($action) {
        case 'logout':
            // Déconnexion
            session_destroy();
            echo json_encode([
                'success' => true,
                'message' => 'Déconnexion réussie'
            ]);
            break;

        case 'register':
            // Inscription d'un nouveau client
            $nom = trim($data['nom'] ?? '');
            $username = trim($data['username'] ?? '');
            $email = trim($data['email'] ?? '');
            $telephone = trim($data['telephone'] ?? '');
            $password = $data['password'] ?? ''; // Optionnel pour clients
            $role = 'client'; // Par défaut

            if (empty($nom) || empty($username) || empty($email) || empty($telephone)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Tous les champs sont requis'
                ]);
                exit;
            }

            // Validation email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Email invalide'
                ]);
                exit;
            }

            $accounts = readAccounts();

            // Vérifier si l'email ou le username existe déjà
            foreach ($accounts as $account) {
                if ($account['email'] === $email) {
                    http_response_code(409);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Un compte existe déjà avec cet email'
                    ]);
                    exit;
                }
                if (isset($account['username']) && $account['username'] === $username) {
                    http_response_code(409);
                    echo json_encode([
                        'success' => false,
                        'message' => 'Ce username est déjà utilisé'
                    ]);
                    exit;
                }
            }

            // Créer le nouveau compte
            $newAccount = [
                'id' => 'acc_' . uniqid(),
                'nom' => $nom,
                'username' => $username,
                'email' => $email,
                'telephone' => $telephone,
                'password' => !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null,
                'role' => $role,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'active' => true
            ];

            $accounts[] = $newAccount;
            saveAccounts($accounts);

            // Créer la session
            $_SESSION['user_id'] = $newAccount['id'];
            $_SESSION['user_email'] = $newAccount['email'];
            $_SESSION['user_name'] = $newAccount['nom'];
            $_SESSION['user_role'] = $newAccount['role'];
            $_SESSION['logged_in'] = true;

            echo json_encode([
                'success' => true,
                'message' => 'Compte créé avec succès',
                'account' => [
                    'id' => $newAccount['id'],
                    'nom' => $newAccount['nom'],
                    'email' => $newAccount['email'],
                    'role' => $newAccount['role']
                ]
            ]);
            break;

        case 'login':
            // Connexion avec email/username + password
            $identifier = trim($data['identifier'] ?? $data['email'] ?? '');
            $password = $data['password'] ?? '';

            if (empty($identifier) || empty($password)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Identifiant et mot de passe requis'
                ]);
                exit;
            }

            $accounts = readAccounts();
            $foundAccount = null;

            foreach ($accounts as $account) {
                // Vérifier si l'identifiant correspond à l'email OU au username
                $matchesEmail = $account['email'] === $identifier;
                $matchesUsername = isset($account['username']) && $account['username'] === $identifier;

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
                echo json_encode([
                    'success' => false,
                    'message' => 'Email ou mot de passe incorrect'
                ]);
                exit;
            }

            if (!$foundAccount['active']) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Compte désactivé'
                ]);
                exit;
            }

            // Créer la session
            $_SESSION['user_id'] = $foundAccount['id'];
            $_SESSION['user_email'] = $foundAccount['email'];
            $_SESSION['user_name'] = $foundAccount['nom'];
            $_SESSION['user_role'] = $foundAccount['role'];
            $_SESSION['logged_in'] = true;

            echo json_encode([
                'success' => true,
                'message' => 'Connexion réussie',
                'account' => [
                    'id' => $foundAccount['id'],
                    'nom' => $foundAccount['nom'],
                    'email' => $foundAccount['email'],
                    'role' => $foundAccount['role']
                ],
                'redirect' => $foundAccount['role'] === 'admin' ? 'admin.html' : 'client.html'
            ]);
            break;

        case 'check_session':
            // Vérifier si l'utilisateur est connecté
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                echo json_encode([
                    'authenticated' => true,
                    'user' => [
                        'id' => $_SESSION['user_id'] ?? null,
                        'nom' => $_SESSION['user_name'] ?? '',
                        'email' => $_SESSION['user_email'] ?? '',
                        'role' => $_SESSION['user_role'] ?? 'client'
                    ]
                ]);
            } else {
                echo json_encode([
                    'authenticated' => false
                ]);
            }
            break;

        case 'get_all_accounts':
            // Récupérer tous les comptes (admin uniquement)
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé - Admin uniquement'
                ]);
                exit;
            }

            $accounts = readAccounts();

            // Retirer les mots de passe pour la sécurité
            $accountsClean = array_map(function ($acc) {
                unset($acc['password']);
                return $acc;
            }, $accounts);

            echo json_encode([
                'success' => true,
                'accounts' => $accountsClean
            ]);
            break;

        case 'update_account_role':
            // Modifier le rôle d'un compte (admin uniquement)
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé - Admin uniquement'
                ]);
                exit;
            }

            $accountId = $data['account_id'] ?? '';
            $newRole = $data['role'] ?? '';

            if (empty($accountId) || !in_array($newRole, ['admin', 'client'])) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Données invalides'
                ]);
                exit;
            }

            $accounts = readAccounts();
            $updated = false;

            foreach ($accounts as &$account) {
                if ($account['id'] === $accountId) {
                    $account['role'] = $newRole;
                    $account['updated_at'] = date('Y-m-d H:i:s');
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                saveAccounts($accounts);
                echo json_encode([
                    'success' => true,
                    'message' => 'Rôle mis à jour'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Compte non trouvé'
                ]);
            }
            break;

        case 'toggle_account_status':
            // Activer/désactiver un compte (admin uniquement)
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé - Admin uniquement'
                ]);
                exit;
            }

            $accountId = $data['account_id'] ?? '';

            if (empty($accountId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID compte requis'
                ]);
                exit;
            }

            $accounts = readAccounts();
            $updated = false;

            foreach ($accounts as &$account) {
                if ($account['id'] === $accountId) {
                    $account['active'] = !($account['active'] ?? true);
                    $account['updated_at'] = date('Y-m-d H:i:s');
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                saveAccounts($accounts);
                echo json_encode([
                    'success' => true,
                    'message' => 'Statut mis à jour'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Compte non trouvé'
                ]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => 'Action non reconnue'
            ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
