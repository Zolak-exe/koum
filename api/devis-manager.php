<?php
/**
 * Gestionnaire de Devis - NEXT DRIVE IMPORT
 * Gestion des demandes de devis séparée des comptes
 * VERSION SQL (MIGRATED)
 */

session_start();
require_once __DIR__ . '/db.php';

header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Récupérer l'action demandée
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$action = $data['action'] ?? '';

try {
    $pdo = getDB();

    switch ($action) {
        case 'create':
            // Créer un nouveau devis
            $userId = $_SESSION['user_id'] ?? null;

            if (empty($userId)) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Connexion requise'
                ]);
                exit;
            }

            // Validation des champs
            $required = ['marque', 'modele', 'budget'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'message' => "Le champ '$field' est requis"
                    ]);
                    exit;
                }
            }

            // Créer le devis
            $id = 'devis_' . uniqid();
            $marque = trim($data['marque']);
            $modele = trim($data['modele']);
            $budget = floatval($data['budget']);
            $annee_min = !empty($data['annee_minimum']) ? intval($data['annee_minimum']) : null;
            $km_max = !empty($data['kilometrage_max']) ? intval($data['kilometrage_max']) : null;
            $options = trim($data['options'] ?? '');
            $commentaires = trim($data['commentaires'] ?? '');
            
            $sql = "INSERT INTO devis (id, user_id, user_name, user_email, marque, modele, budget, annee_minimum, kilometrage_max, options, commentaires, statut, created_at, updated_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'nouveau', NOW(), NOW())";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $id,
                $userId,
                $_SESSION['user_name'] ?? '',
                $_SESSION['user_email'] ?? '',
                $marque,
                $modele,
                $budget,
                $annee_min,
                $km_max,
                $options,
                $commentaires
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Demande de devis enregistrée',
                'devis' => [
                    'id' => $id,
                    'marque' => $marque,
                    'modele' => $modele,
                    'statut' => 'nouveau'
                ]
            ]);
            break;

        case 'get_my_devis':
            // Récupérer les devis de l'utilisateur connecté
            $userId = $_SESSION['user_id'] ?? null;

            if (empty($userId)) {
                http_response_code(401);
                echo json_encode([
                    'success' => false,
                    'message' => 'Connexion requise'
                ]);
                exit;
            }

            $stmt = $pdo->prepare("SELECT * FROM devis WHERE user_id = ? ORDER BY created_at DESC");
            $stmt->execute([$userId]);
            $myDevis = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'devis' => $myDevis
            ]);
            break;

        case 'get_all_devis':
            // Récupérer tous les devis (admin uniquement)
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé - Admin uniquement'
                ]);
                exit;
            }

            $stmt = $pdo->query("SELECT * FROM devis ORDER BY created_at DESC");
            $allDevis = $stmt->fetchAll();

            echo json_encode([
                'success' => true,
                'devis' => $allDevis
            ]);
            break;

        case 'claim':
            // Claim un devis (vendeur uniquement)
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'vendeur') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé - Vendeur uniquement'
                ]);
                exit;
            }

            $devisId = $data['devis_id'] ?? '';
            $userId = $_SESSION['user_id'] ?? '';

            if (empty($devisId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID devis requis'
                ]);
                exit;
            }

            // Vérifier si déjà claim
            $checkStmt = $pdo->prepare("SELECT claimed_by FROM devis WHERE id = ?");
            $checkStmt->execute([$devisId]);
            $devis = $checkStmt->fetch();

            if (!$devis) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Devis non trouvé']);
                exit;
            }

            if (!empty($devis['claimed_by'])) {
                http_response_code(409);
                echo json_encode(['success' => false, 'message' => 'Ce devis est déjà pris en charge']);
                exit;
            }

            // Claim le devis
            $stmt = $pdo->prepare("UPDATE devis SET claimed_by = ?, claimed_at = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$userId, $devisId]);

            echo json_encode([
                'success' => true,
                'message' => 'Devis pris en charge avec succès'
            ]);
            break;

        case 'unclaim':
            // Libérer un devis (vendeur uniquement)
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'vendeur') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé - Vendeur uniquement'
                ]);
                exit;
            }

            $devisId = $data['devis_id'] ?? '';
            $userId = $_SESSION['user_id'] ?? '';

            if (empty($devisId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID devis requis'
                ]);
                exit;
            }

            // Vérifier si c'est bien mon devis
            $checkStmt = $pdo->prepare("SELECT claimed_by FROM devis WHERE id = ?");
            $checkStmt->execute([$devisId]);
            $devis = $checkStmt->fetch();

            if (!$devis) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Devis non trouvé']);
                exit;
            }

            if ($devis['claimed_by'] !== $userId) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Vous ne pouvez pas libérer un devis qui ne vous appartient pas']);
                exit;
            }

            // Unclaim le devis
            $stmt = $pdo->prepare("UPDATE devis SET claimed_by = NULL, claimed_at = NULL, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$devisId]);

            echo json_encode([
                'success' => true,
                'message' => 'Devis libéré avec succès'
            ]);
            break;

        case 'update_status':
            // Mettre à jour le statut d'un devis (admin ou vendeur)
            $isVendeur = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'vendeur';
            $isAdmin = isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';

            if (!$isAdmin && !$isVendeur) {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé'
                ]);
                exit;
            }

            $devisId = $data['devis_id'] ?? '';
            $newStatus = $data['statut'] ?? '';
            $userId = $_SESSION['user_id'] ?? '';

            // Updated valid statuses to match frontend values
            $validStatuses = [
                'nouveau', 'en_cours', 'devis_envoye', 'termine', 'annule',
                'En attente', 'En cours', 'Complété', 'Annulé' // Legacy support
            ];
            
            if (empty($devisId) || !in_array($newStatus, $validStatuses)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Données invalides: statut non reconnu (' . $newStatus . ')'
                ]);
                exit;
            }

            // Si vendeur, vérifier qu'il a le droit (claim ou non claim ?)
            // En général, un vendeur ne devrait modifier que SES devis claimés
            if ($isVendeur) {
                $checkStmt = $pdo->prepare("SELECT claimed_by FROM devis WHERE id = ?");
                $checkStmt->execute([$devisId]);
                $devis = $checkStmt->fetch();

                if (!$devis || $devis['claimed_by'] !== $userId) {
                    http_response_code(403);
                    echo json_encode(['success' => false, 'message' => 'Vous devez prendre en charge ce devis avant de le modifier']);
                    exit;
                }
            }

            $stmt = $pdo->prepare("UPDATE devis SET statut = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $devisId]);

            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Statut mis à jour'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Devis non trouvé'
                ]);
            }
            break;

        case 'add_response':
            // Ajouter une réponse à un devis (admin uniquement)
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé - Admin uniquement'
                ]);
                exit;
            }

            $devisId = $data['devis_id'] ?? '';
            $response = trim($data['response'] ?? '');

            if (empty($devisId) || empty($response)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Données invalides'
                ]);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE devis SET response = ?, response_date = NOW(), updated_at = NOW() WHERE id = ?");
            $stmt->execute([$response, $devisId]);

            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Réponse ajoutée'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Devis non trouvé'
                ]);
            }
            break;

        case 'update_notes':
            // Mettre à jour les notes admin (admin uniquement)
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé - Admin uniquement'
                ]);
                exit;
            }

            $devisId = $data['devis_id'] ?? '';
            $notes = trim($data['notes'] ?? '');

            if (empty($devisId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID devis requis'
                ]);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE devis SET admin_notes = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$notes, $devisId]);

            echo json_encode([
                'success' => true,
                'message' => 'Notes mises à jour'
            ]);
            break;

        case 'delete':
            // Supprimer un devis (admin uniquement)
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé - Admin uniquement'
                ]);
                exit;
            }

            $devisId = $data['devis_id'] ?? '';

            if (empty($devisId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'ID devis requis'
                ]);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM devis WHERE id = ?");
            $stmt->execute([$devisId]);

            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Devis supprimé'
                ]);
            } else {
                http_response_code(404);
                echo json_encode([
                    'success' => false,
                    'message' => 'Devis non trouvé'
                ]);
            }
            break;

        case 'get_stats':
            // Obtenir les statistiques des devis (admin uniquement)
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé - Admin uniquement'
                ]);
                exit;
            }

            $stats = [
                'total' => 0,
                'en_attente' => 0,
                'en_cours' => 0,
                'complete' => 0,
                'annule' => 0,
                'derniers_7_jours' => 0
            ];

            // Total
            $stats['total'] = $pdo->query("SELECT COUNT(*) FROM devis")->fetchColumn();

            // Par statut
            $stmt = $pdo->query("SELECT statut, COUNT(*) as count FROM devis GROUP BY statut");
            while ($row = $stmt->fetch()) {
                switch ($row['statut']) {
                    case 'nouveau': 
                    case 'En attente': // Legacy
                        $stats['en_attente'] += $row['count']; 
                        break;
                    case 'en_cours': 
                    case 'En cours': // Legacy
                        $stats['en_cours'] += $row['count']; 
                        break;
                    case 'termine': 
                    case 'Complété': // Legacy
                        $stats['complete'] += $row['count']; 
                        break;
                    case 'annule': 
                    case 'Annulé': // Legacy
                        $stats['annule'] += $row['count']; 
                        break;
                }
            }

            // Derniers 7 jours
            $stats['derniers_7_jours'] = $pdo->query("SELECT COUNT(*) FROM devis WHERE created_at >= NOW() - INTERVAL '7 days'")->fetchColumn();

            echo json_encode([
                'success' => true,
                'stats' => $stats
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
