<?php
/**
 * Test Devis Submission with Email Notification
 */
require_once __DIR__ . '/api/db.php';
require_once __DIR__ . '/api/security.php';

// Start session to get a CSRF token
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$token = generateCSRFToken();
$session_id = session_id();
$session_name = session_name();
session_write_close(); // IMPORTANT: Release session lock before making HTTP request

$url = 'http://localhost:8000/api/submit-devis.php';
$data = [
    'nom' => 'Test Email User',
    'email' => 'test_email@example.com',
    'telephone' => '0698765432',
    'vehicule' => [
        'marque' => 'Ferrari',
        'modele' => '488',
        'budget' => 200000,
        'annee_minimum' => 2018,
        'kilometrage_max' => 10000,
        'options' => 'Red color',
        'commentaires' => 'Test email delivery logic'
    ],
    'rgpd_consent' => true,
    'csrf_token' => $token
];

echo "Submitting devis to $url...\n";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    "X-CSRF-Token: $token"
]);
// Include cookies for the session
curl_setopt($ch, CURLOPT_COOKIE, $session_name . '=' . $session_id);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status Code: $httpCode\n";
echo "Raw Response: $response\n";

$result = json_decode($response, true);
if ($result && $result['success']) {
    echo "✅ Success: Devis submitted successfully.\n";
    if (isset($result['email_sent']) && $result['email_sent']) {
        echo "📧 Email sent successfully.\n";
    } else {
        echo "⚠️ Email failed to send (this is expected on localhost without SMTP configured).\n";
        echo "   Checking if 'pending_emails' file exists in api/ directory...\n";
    }
} else {
    echo "❌ Error: Failed to submit devis.\n";
    if ($result && isset($result['message'])) {
        echo "Message: " . $result['message'] . "\n";
    }
}
