<?php

/**
 * Script de Génération de Données - NEXT DRIVE IMPORT
 * Génère les comptes et devis à partir de clients.json
 */

define('CLIENTS_FILE', __DIR__ . '/../data/clients.json');
define('ACCOUNTS_FILE', __DIR__ . '/../data/accounts.json');
define('DEVIS_FILE', __DIR__ . '/../data/devis.json');
define('CLIENT_CREDENTIALS_FILE', __DIR__ . '/../docs/client-credentials.csv');
define('ADMIN_CREDENTIALS_FILE', __DIR__ . '/../docs/admin-credentials.txt');

echo "🚀 Génération des données - NEXT DRIVE IMPORT\n";
echo "=============================================\n\n";

function generateStrongPassword($length = 16) {
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $symbols = '!@#$%^&*()_+-=[]{}|;:,.<>?';
    
    $password = '';
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $symbols[random_int(0, strlen($symbols) - 1)];
    
    $all = $uppercase . $lowercase . $numbers . $symbols;
    for ($i = 4; $i < $length; $i++) {
        $password .= $all[random_int(0, strlen($all) - 1)];
    }
    
    return str_shuffle($password);
}

function mapStatus($oldStatus) {
    $mapping = [
        'nouveau' => 'En attente',
        'en-cours' => 'En cours',
        'en_cours' => 'En cours',
        'termine' => 'Complété',
        'terminé' => 'Complété',
        'annule' => 'Annulé',
        'annulé' => 'Annulé'
    ];
    
    return $mapping[$oldStatus] ?? 'En attente';
}

if (!file_exists(__DIR__ . '/../docs')) {
    mkdir(__DIR__ . '/../docs', 0755, true);
}

if (!file_exists(CLIENTS_FILE)) {
    die("❌ Erreur: clients.json n'existe pas\n");
}

$clients = json_decode(file_get_contents(CLIENTS_FILE), true);
if (!is_array($clients)) {
    die("❌ Erreur: clients.json invalide\n");
}

echo "📊 Nombre de clients trouvés: " . count($clients) . "\n\n";

$accounts = [];
$devis = [];
$clientCredentials = [];

echo "🔐 Génération des comptes clients...\n";
foreach ($clients as $client) {
    $password = generateStrongPassword(16);
    
    $account = [
        'id' => 'acc_' . uniqid(),
        'nom' => $client['nom'] ?? 'Client',
        'email' => $client['email'] ?? '',
        'telephone' => $client['telephone'] ?? '',
        'password' => password_hash($password, PASSWORD_DEFAULT),
        'role' => 'client',
        'created_at' => $client['created_at'] ?? date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'active' => true,
        'password_reset_required' => true
    ];
    
    $accounts[] = $account;
    
    $clientCredentials[] = [
        'nom' => $account['nom'],
        'email' => $account['email'],
        'telephone' => $account['telephone'],
        'password' => $password,
        'account_id' => $account['id']
    ];
    
    if (isset($client['vehicule'])) {
        $devis = [
            'id' => 'devis_' . uniqid(),
            'user_id' => $account['id'],
            'user_name' => $account['nom'],
            'user_email' => $account['email'],
            'marque' => $client['vehicule']['marque'] ?? '',
            'modele' => $client['vehicule']['modele'] ?? '',
            'budget' => floatval($client['vehicule']['budget'] ?? 0),
            'annee_minimum' => isset($client['vehicule']['annee_minimum']) ? intval($client['vehicule']['annee_minimum']) : null,
            'kilometrage_max' => isset($client['vehicule']['kilometrage_max']) ? intval($client['vehicule']['kilometrage_max']) : null,
            'options' => $client['vehicule']['options'] ?? '',
            'commentaires' => $client['vehicule']['commentaires'] ?? '',
            'statut' => mapStatus($client['statut'] ?? 'nouveau'),
            'created_at' => $client['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at' => $client['updated_at'] ?? date('Y-m-d H:i:s'),
            'response' => null,
            'response_date' => null
        ];
        
        $devis[] = $devis;
    }
    
    echo "  ✓ {$account['nom']} ({$account['email']})\n";
}

echo "\n🔐 Génération du compte admin...\n";

$adminPassword = generateStrongPassword(20);
$adminAccount = [
    'id' => 'acc_admin_' . uniqid(),
    'nom' => 'Administrateur',
    'email' => 'admin@nextdriveimport.fr',
    'telephone' => '0600000000',
    'password' => password_hash($adminPassword, PASSWORD_DEFAULT),
    'role' => 'admin',
    'created_at' => date('Y-m-d H:i:s'),
    'updated_at' => date('Y-m-d H:i:s'),
    'active' => true,
    'password_reset_required' => false
];

$accounts[] = $adminAccount;
echo "  ✓ Admin ({$adminAccount['email']})\n\n";

echo "💾 Sauvegarde de accounts.json...\n";
file_put_contents(ACCOUNTS_FILE, json_encode($accounts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
@chmod(ACCOUNTS_FILE, 0644);
echo "  ✓ " . count($accounts) . " comptes sauvegardés\n\n";

echo "💾 Sauvegarde de devis.json...\n";
file_put_contents(DEVIS_FILE, json_encode($devis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
@chmod(DEVIS_FILE, 0644);
echo "  ✓ " . count($devis) . " devis sauvegardés\n\n";

echo "📄 Création de client-credentials.csv...\n";
$csvHandle = fopen(CLIENT_CREDENTIALS_FILE, 'w');
fputcsv($csvHandle, ['Nom', 'Email', 'Téléphone', 'Mot de passe temporaire', 'ID Compte']);
foreach ($clientCredentials as $cred) {
    fputcsv($csvHandle, [
        $cred['nom'],
        $cred['email'],
        $cred['telephone'],
        $cred['password'],
        $cred['account_id']
    ]);
}
fclose($csvHandle);
@chmod(CLIENT_CREDENTIALS_FILE, 0600);
echo "  ✓ Fichier créé: docs/client-credentials.csv\n\n";

echo "📄 Création de admin-credentials.txt...\n";
$adminCredentialsContent = "==============================================\n";
$adminCredentialsContent .= "CREDENTIALS ADMINISTRATEUR - NEXT DRIVE IMPORT\n";
$adminCredentialsContent .= "==============================================\n\n";
$adminCredentialsContent .= "⚠️  CONFIDENTIEL - À CONSERVER EN LIEU SÛR\n\n";
$adminCredentialsContent .= "Email: {$adminAccount['email']}\n";
$adminCredentialsContent .= "Mot de passe: {$adminPassword}\n\n";
$adminCredentialsContent .= "Page de connexion: pages/login.html\n\n";
$adminCredentialsContent .= "IMPORTANT:\n";
$adminCredentialsContent .= "- Changez ce mot de passe après la première connexion\n";
$adminCredentialsContent .= "- Ne partagez jamais ces identifiants\n";
$adminCredentialsContent .= "- Supprimez ce fichier après avoir noté les credentials\n\n";
$adminCredentialsContent .= "Généré le: " . date('Y-m-d H:i:s') . "\n";

file_put_contents(ADMIN_CREDENTIALS_FILE, $adminCredentialsContent);
@chmod(ADMIN_CREDENTIALS_FILE, 0600);
echo "  ✓ Fichier créé: docs/admin-credentials.txt\n\n";

echo "✅ GÉNÉRATION TERMINÉE AVEC SUCCÈS!\n";
echo "=====================================\n\n";
echo "📊 Résumé:\n";
echo "  • Comptes clients créés: " . (count($accounts) - 1) . "\n";
echo "  • Compte admin créé: 1\n";
echo "  • Devis créés: " . count($devis) . "\n\n";
echo "📁 Fichiers générés:\n";
echo "  • data/accounts.json (" . count($accounts) . " comptes)\n";
echo "  • data/devis.json (" . count($devis) . " devis)\n";
echo "  • docs/client-credentials.csv (credentials clients)\n";
echo "  • docs/admin-credentials.txt (credentials admin)\n\n";
echo "⚠️  IMPORTANT:\n";
echo "  • Les fichiers de credentials contiennent des mots de passe en clair\n";
echo "  • NE PAS déployer ces fichiers sur le serveur de production\n";
echo "  • Distribuer les credentials aux clients de manière sécurisée\n";
echo "  • Supprimer les fichiers de credentials après distribution\n\n";
echo "🔐 Credentials Admin:\n";
echo "  Email: {$adminAccount['email']}\n";
echo "  Mot de passe: {$adminPassword}\n\n";
echo "✨ Le site est maintenant prêt à être utilisé!\n";
