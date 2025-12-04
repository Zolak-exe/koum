<?php
// submit-devis.php - VERSION 100% FONCTIONNELLE
session_start();
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
define('ADMIN_EMAIL', 'nextdriveimport@gmail.com');
define('SITE_URL', 'https://nextdriveimport.fr');

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
$rate_limit_file = __DIR__ . '/rate_limit_submit_' . md5($ip) . '.txt';
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

// Vérifier si l'utilisateur a un compte
$has_account = isset($data['has_account']) ? (bool)$data['has_account'] : false;
$accountsFile = __DIR__ . '/../data/accounts.json';
$user_id = null;

if (file_exists($accountsFile)) {
    $accounts = json_decode(file_get_contents($accountsFile), true);
    if (is_array($accounts)) {
        foreach ($accounts as $account) {
            if (strtolower($account['email']) === strtolower($email)) {
                $has_account = true;
                $user_id = $account['id'];
                break;
            }
        }
    }
}

// Préparer les données client
$clientData = [
    'id' => uniqid('devis_', true),
    'user_id' => $user_id,
    'timestamp' => date('Y-m-d H:i:s'),
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
    'source' => 'website',
    'statut' => 'nouveau',
    'has_account' => $has_account,
    'nom' => $nom,
    'email' => $email,
    'telephone' => $telephone_clean,
    'rgpd_consent' => true,
    'vehicule' => [
        'marque' => $marque,
        'modele' => $modele,
        'budget' => $budget,
        'annee_minimum' => isset($data['vehicule']['annee_minimum']) ? (int)$data['vehicule']['annee_minimum'] : null,
        'kilometrage_max' => isset($data['vehicule']['kilometrage_max']) ? (int)$data['vehicule']['kilometrage_max'] : null,
        'options' => isset($data['vehicule']['options']) ? htmlspecialchars($data['vehicule']['options'], ENT_QUOTES, 'UTF-8') : '',
        'commentaires' => isset($data['vehicule']['commentaires']) ? htmlspecialchars($data['vehicule']['commentaires'], ENT_QUOTES, 'UTF-8') : ''
    ],
    'metadata' => [
        'ip_address' => $ip,
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
        'referer' => $_SERVER['HTTP_REFERER'] ?? null
    ]
];

// Sauvegarder dans clients.json
$clientsFile = __DIR__ . '/../data/clients.json';

if (!file_exists($clientsFile)) {
    file_put_contents($clientsFile, '[]');
}

$clients = json_decode(file_get_contents($clientsFile), true);
if (!is_array($clients)) {
    $clients = [];
}

$clients[] = $clientData;
file_put_contents($clientsFile, json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

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

Année minimum: " . ($clientData['vehicule']['annee_minimum'] ?? 'Non spécifié') . "
Kilométrage maximum: " . ($clientData['vehicule']['kilometrage_max'] ?? 'Non spécifié') . " km

Options: " . ($clientData['vehicule']['options'] ?: 'Aucune') . "

Commentaires: " . ($clientData['vehicule']['commentaires'] ?: 'Aucun') . "

---
ID Demande: " . $clientData['id'] . "
Date: " . $clientData['created_at'] . "
IP: " . $ip . "

Lien admin: " . SITE_URL . "/admin.html
";

$mailSent = false;
if (function_exists('mail')) {
    $headers = "From: noreply@nextdriveimport.fr\r\n";
    $headers .= "Reply-To: $email\r\n";
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
error_log("Nouvelle demande: $nom ($email) - $marque $modele - " . $clientData['id'], 3, __DIR__ . '/admin_actions.log');

echo json_encode([
    'success' => true,
    'message' => 'Demande envoyée avec succès',
    'client_id' => $clientData['id'],
    'has_account' => $has_account,
    'email_sent' => $mailSent
]);
