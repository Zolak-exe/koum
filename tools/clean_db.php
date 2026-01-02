<?php
require_once __DIR__ . '/../api/db.php';

try {
    $pdo = getDB();
    echo "Connexion DB OK.\n";

    // 1. Vider la table devis
    $pdo->exec("DELETE FROM devis");
    echo "Table 'devis' vidée.\n";

    // 2. Vider la table messages
    $pdo->exec("DELETE FROM messages");
    echo "Table 'messages' vidée.\n";

    // 3. Supprimer les utilisateurs sauf l'admin
    // On garde l'admin existant
    $adminEmail = 'admin@nextdriveimport.fr';
    $stmt = $pdo->prepare("DELETE FROM users WHERE email != ?");
    $stmt->execute([$adminEmail]);
    echo "Utilisateurs supprimés (sauf admin).\n";

    // 4. Créer/Vérifier le vendeur
    $vendeurEmail = 'vendeur@nextdriveimport.fr';
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$vendeurEmail]);
    $vendeurId = $stmt->fetchColumn();

    if (!$vendeurId) {
        $vendeurId = uniqid('acc_', true);
        $password = password_hash('password', PASSWORD_DEFAULT); // Mot de passe par défaut
        $nom = 'Vendeur';
        $telephone = '0600000001';
        $role = 'vendeur'; // On utilise le rôle vendeur comme demandé

        $stmt = $pdo->prepare("INSERT INTO users (id, nom, email, telephone, password, role, created_at, updated_at, active) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW(), true)");
        $stmt->execute([$vendeurId, $nom, $vendeurEmail, $telephone, $password, $role]);
        echo "Utilisateur 'Vendeur' créé (Email: $vendeurEmail, Pass: password).\n";
    } else {
        echo "Utilisateur 'Vendeur' existe déjà.\n";
    }

    echo "Nettoyage terminé avec succès.\n";

} catch (Exception $e) {
    die("Erreur : " . $e->getMessage() . "\n");
}
