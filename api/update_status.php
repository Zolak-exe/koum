<?php
/**
 * NEXT DRIVE IMPORT - Update Status v2.1.0
 * Mise à jour du statut des demandes
 */

session_start();

// ========== VÉRIFICATION AUTHENTIFICATION ==========
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit;
}

// ========== CONFIGURATION ==========
define('DATA_DIR', __DIR__);  // ← RACINE
define('CLIENTS_FILE', DATA_DIR . '/clients.json');

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
$valid_statuses = ['nouveau', 'en_cours', 'devis_envoye', 'termine', 'annule'];
if (!in_array($new_status, $valid_statuses)) {
    http_response_code(400);
    echo json_encode(['error' => 'Statut invalide']);
    exit;
}

// ========== CHARGEMENT DES CLIENTS ==========
if (!file_exists(CLIENTS_FILE)) {
    http_response_code(404);
    echo json_encode(['error' => 'Fichier clients introuvable']);
    exit;
}

$json_content = file_get_contents(CLIENTS_FILE);
$clients = json_decode($json_content, true);

if ($clients === null) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de lecture']);
    exit;
}

// ========== MISE À JOUR DU STATUT ==========
$found = false;
foreach ($clients as &$client) {
    if ($client['id'] === $client_id) {
        $client['statut'] = $new_status;
        $client['updated_at'] = date('Y-m-d H:i:s');
        $found = true;
        break;
    }
}

if (!$found) {
    http_response_code(404);
    echo json_encode(['error' => 'Client introuvable']);
    exit;
}

// ========== SAUVEGARDE ==========
$json_data = json_encode($clients, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

if (file_put_contents(CLIENTS_FILE, $json_data, LOCK_EX) === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la sauvegarde']);
    exit;
}

// ========== RÉPONSE SUCCÈS ==========
echo json_encode([
    'success' => true,
    'message' => 'Statut mis à jour'
]);
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
