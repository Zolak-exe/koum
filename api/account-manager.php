<?php
/**
 * Gestionnaire de Comptes - NEXT DRIVE IMPORT
 * Gestion des comptes utilisateurs avec rôles (admin/client)
 * VERSION SQL (MIGRATED)
 */

session_start();
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/security.php';

setSecureCORS();
enforceCSRF();

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
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
    $pdo = getDB();

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
            // Username ignored in SQL schema
            $email = trim($data['email'] ?? '');
            $telephone = trim($data['telephone'] ?? '');
            $password = $data['password'] ?? ''; // Optionnel pour clients
            $role = 'client'; // Par défaut

            if (empty($nom) || empty($email) || empty($telephone)) {
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

            // Vérifier si l'email existe déjà
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                http_response_code(409);
                echo json_encode([
                    'success' => false,
                    'message' => 'Un compte existe déjà avec cet email'
                ]);
                exit;
            }

            // Créer le nouveau compte
            $id = 'acc_' . uniqid();
            $hashedPassword = !empty($password) ? password_hash($password, PASSWORD_DEFAULT) : null;

            $sql = "INSERT INTO users (id, nom, email, telephone, password, role, created_at, updated_at, active) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), true)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$id, $nom, $email, $telephone, $hashedPassword, $role]);

            // Créer la session
            $_SESSION['user_id'] = $id;
            $_SESSION['user_email'] = $email;
            $_SESSION['user_name'] = $nom;
            $_SESSION['user_role'] = $role;
            $_SESSION['logged_in'] = true;

            echo json_encode([
                'success' => true,
                'message' => 'Compte créé avec succès',
                'account' => [
                    'id' => $id,
                    'nom' => $nom,
                    'email' => $email,
                    'role' => $role
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

            // Chercher l'utilisateur par email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$identifier]);
            $foundAccount = $stmt->fetch();

            if (!$foundAccount || !password_verify($password, $foundAccount['password'])) {
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
            $_SESSION['user_telephone'] = $foundAccount['telephone'];
            $_SESSION['user_role'] = $foundAccount['role'];
            $_SESSION['logged_in'] = true;

            $redirect = 'client.html';
            if ($foundAccount['role'] === 'admin') {
                $redirect = 'admin.html';
            } elseif ($foundAccount['role'] === 'vendeur') {
                $redirect = 'vendeur.html';
            }

            echo json_encode([
                'success' => true,
                'message' => 'Connexion réussie',
                'account' => [
                    'id' => $foundAccount['id'],
                    'nom' => $foundAccount['nom'],
                    'email' => $foundAccount['email'],
                    'telephone' => $foundAccount['telephone'],
                    'role' => $foundAccount['role']
                ],
                'redirect' => $redirect
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
                        'telephone' => $_SESSION['user_telephone'] ?? '', // Added telephone
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

            $stmt = $pdo->query("SELECT id, nom, email, telephone, role, created_at, updated_at, active FROM users ORDER BY created_at DESC");
            $accounts = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'accounts' => $accounts
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

            $stmt = $pdo->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newRole, $accountId]);

            if ($stmt->rowCount() > 0) {
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

            // Toggle status logic in SQL is tricky without reading first or using a CASE statement
            // Let's read first to be safe and simple
            $stmt = $pdo->prepare("SELECT active FROM users WHERE id = ?");
            $stmt->execute([$accountId]);
            $current = $stmt->fetchColumn();

            if ($current === false) {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Compte non trouvé'
                ]);
                exit;
            }

            $newStatus = !$current;
            $stmt = $pdo->prepare("UPDATE users SET active = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus ? 1 : 0, $accountId]);

            echo json_encode([
                'success' => true,
                'message' => 'Statut mis à jour'
            ]);
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
    error_log($e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur interne'
    ]);
}

