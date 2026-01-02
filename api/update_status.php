<?php
/**
 * NEXT DRIVE IMPORT - Update Status v2.1.0
 * Mise à jour du statut des demandes
 * VERSION SQL (MIGRATED)
 */

session_start();
require_once __DIR__ . '/db.php';

// ========== VÉRIFICATION AUTHENTIFICATION ==========
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// ========== HEADERS ==========
header('Content-Type: application/json; charset=UTF-8');

// Accepter uniquement POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Méthode non autorisée']);
    exit;
}

// ========== RÉCUPÉRATION DES DONNÉES ==========
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data || empty($data['id']) || empty($data['statut'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Données invalides']);
    exit;
}

$client_id = $data['id'];
$new_status = $data['statut'];

// Validation du statut
// Note: The DB might have different constraints or case sensitivity.
// The previous file had: 'nouveau', 'en_cours', 'devis_envoye', 'termine', 'annule'
// devis-manager.php uses: 'En attente', 'En cours', 'Complété', 'Annulé'
// I should probably map them or allow both sets if possible.
// But since I migrated data, I should check what's in the DB.
// The migration script mapped 'nouveau' -> 'En attente', etc.
// So I should map the input status to the DB status.

$statusMap = [
    'nouveau' => 'En attente',
    'en_cours' => 'En cours',
    'devis_envoye' => 'En cours', // Mapping approximation
    'termine' => 'Complété',
    'annule' => 'Annulé'
];

$dbStatus = $statusMap[$new_status] ?? $new_status;

// Also allow direct DB status values
$valid_db_statuses = ['En attente', 'En cours', 'Complété', 'Annulé'];
if (!in_array($dbStatus, $valid_db_statuses)) {
    // If not in map and not in valid DB statuses, maybe it's invalid.
    // But let's be lenient or strict?
    // The previous code was strict on the lowercase ones.
    // I'll stick to the map if it matches, otherwise check if it's a valid DB status.
    if (!in_array($new_status, $valid_db_statuses)) {
         http_response_code(400);
         echo json_encode(['error' => 'Statut invalide']);
         exit;
    }
    $dbStatus = $new_status;
}

try {
    $pdo = getDB();
    
    $stmt = $pdo->prepare("UPDATE devis SET statut = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$dbStatus, $client_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Statut mis à jour'
        ]);
    } else {
        // Check if ID exists
        $check = $pdo->prepare("SELECT id FROM devis WHERE id = ?");
        $check->execute([$client_id]);
        if ($check->fetch()) {
             // Exists but no change (same status)
             echo json_encode([
                'success' => true,
                'message' => 'Statut mis à jour (inchangé)'
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['error' => 'Client introuvable']);
        }
    }

} catch (Exception $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo json_encode(['error' => 'Erreur serveur interne']);
}
?>
```

---

## 🧪 **TEST COMPLET : Vérifier l'admin**

### **Étape 1 : Vérifier la structure des fichiers**
```
/htdocs/
├── admin.html ✅
├── admin-script.js ✅
├── get-clients.php ✅ (modifié)
├── update-status.php ✅ (modifié)
├── clients.json ✅ (doit contenir au moins [])
```

### **Étape 2 : Vérifier les permissions**
```
clients.json → 666 ou 644
get-clients.php → 644
update-status.php → 644
