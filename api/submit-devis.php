<?php
// submit-devis.php - VERSION SQL (MIGRATED)
session_start();
require_once __DIR__ . '/db.php'; // Inclut env.php via db.php

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Configuration
define('ADMIN_EMAIL', env('ADMIN_EMAIL', 'nextdriveimport@gmail.com'));
define('SITE_URL', env('SITE_URL', 'https://nextdriveimport.fr'));

// Lire les données JSON
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Données invalides']);
    exit;
}

// Rate limiting
$ip = $_SERVER['REMOTE_ADDR'];
$rate_limit_file = __DIR__ . '/rate_limit_submit_' . hash('sha256', $ip) . '.txt';
$max_submissions = 50; // Augmenté pour les tests
$time_window = 3600; // 1 heure

if (file_exists($rate_limit_file)) {
    $rateData = json_decode(file_get_contents($rate_limit_file), true);
    if ($rateData['count'] >= $max_submissions && time() - $rateData['last_submit'] < $time_window) {
        http_response_code(429);
        echo json_encode(['success' => false, 'message' => 'Trop de demandes. Réessayez dans 1 heure.']);
        exit;
    }
}

// Validation des données
$nom = isset($data['nom']) ? htmlspecialchars(trim($data['nom']), ENT_QUOTES, 'UTF-8') : '';
$email = isset($data['email']) ? filter_var(trim($data['email']), FILTER_VALIDATE_EMAIL) : false;
$telephone = isset($data['telephone']) ? htmlspecialchars(trim($data['telephone']), ENT_QUOTES, 'UTF-8') : '';
$budget = isset($data['vehicule']['budget']) ? (int)$data['vehicule']['budget'] : 0;
$marque = isset($data['vehicule']['marque']) ? htmlspecialchars(trim($data['vehicule']['marque']), ENT_QUOTES, 'UTF-8') : '';
$modele = isset($data['vehicule']['modele']) ? htmlspecialchars(trim($data['vehicule']['modele']), ENT_QUOTES, 'UTF-8') : '';
$rgpd_consent = isset($data['rgpd_consent']) ? (bool)$data['rgpd_consent'] : false;

if (!$nom || !$email || !$telephone || !$budget || !$marque || !$modele || !$rgpd_consent) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Tous les champs obligatoires doivent être remplis',
        'missing' => [
            'nom' => empty($nom),
            'email' => empty($email),
            'telephone' => empty($telephone),
            'budget' => empty($budget),
            'marque' => empty($marque),
            'modele' => empty($modele),
            'rgpd' => !$rgpd_consent
        ]
    ]);
    exit;
}

// Validation téléphone français
$telephone_clean = preg_replace('/\s+/', '', $telephone);
if (!preg_match('/^(\+33|0)[1-9](\d{2}){4}$/', $telephone_clean)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Numéro de téléphone invalide (format: 06 12 34 56 78)']);
    exit;
}

try {
    $pdo = getDB();

    // Vérifier si l'utilisateur a un compte
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user_id = $stmt->fetchColumn();

    // Gestion de la création de compte optionnelle
    $create_account = isset($data['create_account']) ? (bool)$data['create_account'] : false;
    $password = isset($data['password']) ? $data['password'] : '';

    if ($create_account && !$user_id) {
        if (empty($password)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Mot de passe requis pour créer un compte']);
            exit;
        }

        // Créer le compte
        $user_id = uniqid('acc_', true);
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $role = 'client';

        $stmt = $pdo->prepare("INSERT INTO users (id, nom, email, telephone, password, role, created_at, updated_at, active) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), true)");
        $stmt->execute([$user_id, $nom, $email, $telephone_clean, $hashedPassword, $role]);
    }

    $has_account = (bool)$user_id;

    // Préparer les données devis
    $devis_id = 'devis_' . uniqid();
    $annee_min = isset($data['vehicule']['annee_minimum']) ? (int)$data['vehicule']['annee_minimum'] : null;
    $km_max = isset($data['vehicule']['kilometrage_max']) ? (int)$data['vehicule']['kilometrage_max'] : null;
    $options = isset($data['vehicule']['options']) ? htmlspecialchars($data['vehicule']['options'], ENT_QUOTES, 'UTF-8') : '';
    $commentaires = isset($data['vehicule']['commentaires']) ? htmlspecialchars($data['vehicule']['commentaires'], ENT_QUOTES, 'UTF-8') : '';

    $sql = "INSERT INTO devis (id, user_id, user_name, user_email, marque, modele, budget, annee_minimum, kilometrage_max, options, commentaires, statut, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'En attente', NOW(), NOW())";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $devis_id,
        $user_id ?: null, // NULL if no account
        $nom,
        $email,
        $marque,
        $modele,
        $budget,
        $annee_min,
        $km_max,
        $options,
        $commentaires
    ]);

    // Mettre à jour le rate limiting
    $rateData = file_exists($rate_limit_file) ? json_decode(file_get_contents($rate_limit_file), true) : ['count' => 0, 'last_submit' => 0];
    $rateData['count']++;
    $rateData['last_submit'] = time();
    file_put_contents($rate_limit_file, json_encode($rateData));

    // Tenter d'envoyer un email
    $subject = "Nouvelle demande de devis - $nom";
    $message = "
    Nouvelle demande de devis reçue:

    Client: $nom
    Email: $email
    Téléphone: $telephone

    Véhicule souhaité:
    - Marque: $marque
    - Modèle: $modele
    - Budget: " . number_format($budget, 0, ',', ' ') . " €

    Année minimum: " . ($annee_min ?? 'Non spécifié') . "
    Kilométrage maximum: " . ($km_max ?? 'Non spécifié') . " km

    Options: " . ($options ?: 'Aucune') . "

    Commentaires: " . ($commentaires ?: 'Aucun') . "

    ---
    ID Demande: " . $devis_id . "
    Date: " . date('Y-m-d H:i:s') . "
    IP: " . $ip . "

    Lien admin: " . SITE_URL . "/admin.html
    ";

    $mailSent = false;
    if (function_exists('mail')) {
        // Sanitize email to prevent header injection
        $cleanEmail = str_replace(["\r", "\n"], '', $email);
        
        $headers = "From: noreply@nextdriveimport.fr\r\n";
        $headers .= "Reply-To: $cleanEmail\r\n";
        $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

        $mailSent = @mail(ADMIN_EMAIL, $subject, $message, $headers);
    }

    // Sauvegarder pour traitement manuel si l'email échoue
    if (!$mailSent) {
        $emailFile = __DIR__ . '/pending_emails_' . date('Y-m-d') . '.txt';
        $emailContent = "\n" . str_repeat('=', 50) . "\n";
        $emailContent .= date('Y-m-d H:i:s') . " - NOUVELLE DEMANDE\n";
        $emailContent .= str_repeat('=', 50) . "\n";
        $emailContent .= $message . "\n";
        file_put_contents($emailFile, $emailContent, FILE_APPEND | LOCK_EX);
    }

    // Logger
    error_log("Nouvelle demande: $nom ($email) - $marque $modele - " . $devis_id, 3, __DIR__ . '/admin_actions.log');

    echo json_encode([
        'success' => true,
        'message' => 'Demande envoyée avec succès',
        'client_id' => $devis_id,
        'has_account' => $has_account,
        'email_sent' => $mailSent
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage()); // Log error instead of showing it
    echo json_encode(['success' => false, 'message' => 'Erreur serveur interne']);
}
