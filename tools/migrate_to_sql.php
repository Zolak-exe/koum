<?php
/**
 * Script de migration JSON vers SQL (PostgreSQL/Neon)
 * Usage: php tools/migrate_to_sql.php
 */

require_once __DIR__ . '/../api/db.php'; // Assurez-vous que ce fichier existe et est configuré

$accountsFile = __DIR__ . '/../data/accounts.json';
$devisFile = __DIR__ . '/../data/devis.json';
$messagesFile = __DIR__ . '/../data/chat-messages.json';

try {
    $pdo = getDB();
    echo "✅ Connexion à la base de données réussie.\n";
} catch (Exception $e) {
    die("❌ Erreur de connexion : " . $e->getMessage() . "\n");
}

// 1. Migration des Utilisateurs
if (file_exists($accountsFile)) {
    echo "\n🔄 Migration des utilisateurs...\n";
    $users = json_decode(file_get_contents($accountsFile), true);
    $stmt = $pdo->prepare("INSERT INTO users (id, nom, email, telephone, password, role, created_at, updated_at, active, password_reset_required) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING");
    
    foreach ($users as $user) {
        try {
            $stmt->execute([
                $user['id'],
                $user['nom'],
                $user['email'],
                $user['telephone'] ?? null,
                $user['password'],
                $user['role'],
                $user['created_at'],
                $user['updated_at'],
                $user['active'] ? 1 : 0,
                $user['password_reset_required'] ? 1 : 0
            ]);
            echo "  - Utilisateur importé : " . $user['email'] . "\n";
        } catch (PDOException $e) {
            echo "  ❌ Erreur import utilisateur " . $user['email'] . ": " . $e->getMessage() . "\n";
        }
    }
}

// 2. Migration des Devis
if (file_exists($devisFile)) {
    echo "\n🔄 Migration des devis...\n";
    $devisList = json_decode(file_get_contents($devisFile), true);
    $stmt = $pdo->prepare("INSERT INTO devis (id, user_id, user_name, user_email, marque, modele, budget, annee_minimum, kilometrage_max, options, commentaires, statut, created_at, updated_at, response, response_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING");

    foreach ($devisList as $devis) {
        // Skip invalid entries (like the "0" key we saw earlier if it exists)
        if (!isset($devis['id'])) continue;

        try {
            $stmt->execute([
                $devis['id'],
                $devis['user_id'],
                $devis['user_name'],
                $devis['user_email'],
                $devis['marque'],
                $devis['modele'],
                $devis['budget'],
                $devis['annee_minimum'] ?? null,
                $devis['kilometrage_max'] ?? null,
                $devis['options'] ?? '',
                $devis['commentaires'] ?? '',
                $devis['statut'],
                $devis['created_at'],
                $devis['updated_at'],
                $devis['response'] ?? null,
                $devis['response_date'] ?? null
            ]);
            echo "  - Devis importé : " . $devis['id'] . "\n";
        } catch (PDOException $e) {
            echo "  ❌ Erreur import devis " . $devis['id'] . ": " . $e->getMessage() . "\n";
        }
    }
}

// 3. Migration des Messages
if (file_exists($messagesFile)) {
    echo "\n🔄 Migration des messages...\n";
    $messages = json_decode(file_get_contents($messagesFile), true);
    $stmt = $pdo->prepare("INSERT INTO messages (id, user_id, user_email, user_name, message, is_admin, timestamp, read) VALUES (?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING");

    foreach ($messages as $msg) {
        try {
            $stmt->execute([
                $msg['id'],
                $msg['user_id'],
                $msg['user_email'],
                $msg['user_name'],
                $msg['message'],
                $msg['is_admin'] ? 1 : 0,
                $msg['timestamp'],
                $msg['read'] ? 1 : 0
            ]);
            echo "  - Message importé : " . $msg['id'] . "\n";
        } catch (PDOException $e) {
            echo "  ❌ Erreur import message " . $msg['id'] . ": " . $e->getMessage() . "\n";
        }
    }
}

echo "\n✅ Migration terminée !\n";
