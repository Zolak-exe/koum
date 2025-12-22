<?php
/**
 * Script d'Initialisation - NEXT DRIVE IMPORT
 * Crée les fichiers JSON et un compte admin par défaut
 */

define('ACCOUNTS_FILE', __DIR__ . '/../data/accounts.json');
define('DEVIS_FILE', __DIR__ . '/../data/devis.json');

echo "🚀 Initialisation de NEXT DRIVE IMPORT\n";
echo "=====================================\n\n";

// Créer le fichier accounts.json si inexistant
if (!file_exists(ACCOUNTS_FILE)) {
    // Compte admin par défaut
    $adminAccount = [
        'id' => 'acc_admin_001',
        'nom' => 'Administrateur',
        'email' => 'admin@nextdriveimport.fr',
        'telephone' => '0600000000',
        'password' => password_hash('Admin@2024', PASSWORD_DEFAULT),
        'role' => 'admin',
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'active' => true
    ];

    $accounts = [$adminAccount];
    file_put_contents(ACCOUNTS_FILE, json_encode($accounts, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    @chmod(ACCOUNTS_FILE, 0644);

    echo "✅ Fichier accounts.json créé\n";
    echo "📧 Compte admin créé:\n";
    echo "   Email: admin@nextdriveimport.fr\n";
    echo "   Mot de passe: Admin@2024\n\n";
} else {
    echo "ℹ️  accounts.json existe déjà\n\n";
}

// Créer le fichier devis.json si inexistant
if (!file_exists(DEVIS_FILE)) {
    file_put_contents(DEVIS_FILE, json_encode([], JSON_PRETTY_PRINT));
    @chmod(DEVIS_FILE, 0644);
    echo "✅ Fichier devis.json créé\n\n";
} else {
    echo "ℹ️  devis.json existe déjà\n\n";
}

// Vérifier les permissions
$accountsPerms = substr(sprintf('%o', fileperms(ACCOUNTS_FILE)), -4);
$devisPerms = substr(sprintf('%o', fileperms(DEVIS_FILE)), -4);

echo "📁 Permissions des fichiers:\n";
echo "   accounts.json: $accountsPerms\n";
echo "   devis.json: $devisPerms\n\n";

echo "🎉 Initialisation terminée avec succès!\n";
echo "=====================================\n\n";

echo "📌 Prochaines étapes:\n";
echo "1. Connectez-vous avec le compte admin\n";
echo "2. Changez le mot de passe admin par défaut\n";
echo "3. Créez d'autres comptes si nécessaire\n\n";

echo "🔐 IMPORTANT: Changez le mot de passe admin dès que possible!\n";
