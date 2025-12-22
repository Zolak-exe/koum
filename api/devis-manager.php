<?php
/**
 * Gestionnaire de Devis - NEXT DRIVE IMPORT
 * Gestion des demandes de devis séparée des comptes
 */

session_start();
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');

// Fichier de données
define('DEVIS_FILE', __DIR__ . '/../data/devis.json');

// Fonction pour lire les devis
function readDevis()
{
    if (!file_exists(DEVIS_FILE)) {
        file_put_contents(DEVIS_FILE, json_encode([]));
        @chmod(DEVIS_FILE, 0644);
    }
    $content = file_get_contents(DEVIS_FILE);
    return json_decode($content, true) ?: [];
}

// Fonction pour sauvegarder les devis
function saveDevis($devis)
{
    $json = json_encode($devis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    file_put_contents(DEVIS_FILE, $json, LOCK_EX);
    @chmod(DEVIS_FILE, 0644);
}

// Récupérer l'action demandée
$input = file_get_contents('php://input');
$data = json_decode($input, true);
$action = $data['action'] ?? '';

try {
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
            $newDevis = [
                'id' => 'devis_' . uniqid(),
                'user_id' => $userId,
                'user_name' => $_SESSION['user_name'] ?? '',
                'user_email' => $_SESSION['user_email'] ?? '',
                'marque' => trim($data['marque']),
                'modele' => trim($data['modele']),
                'budget' => floatval($data['budget']),
                'annee_minimum' => !empty($data['annee_minimum']) ? intval($data['annee_minimum']) : null,
                'kilometrage_max' => !empty($data['kilometrage_max']) ? intval($data['kilometrage_max']) : null,
                'options' => trim($data['options'] ?? ''),
                'commentaires' => trim($data['commentaires'] ?? ''),
                'statut' => 'En attente',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'response' => null,
                'response_date' => null
            ];

            $allDevis = readDevis();
            $allDevis[] = $newDevis;
            saveDevis($allDevis);

            echo json_encode([
                'success' => true,
                'message' => 'Demande de devis enregistrée',
                'devis' => $newDevis
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

            $allDevis = readDevis();
            $myDevis = array_filter($allDevis, function ($devis) use ($userId) {
                return $devis['user_id'] === $userId;
            });

            echo json_encode([
                'success' => true,
                'devis' => array_values($myDevis)
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

            $allDevis = readDevis();

            echo json_encode([
                'success' => true,
                'devis' => $allDevis
            ]);
            break;

        case 'update_status':
            // Mettre à jour le statut d'un devis (admin uniquement)
            if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
                http_response_code(403);
                echo json_encode([
                    'success' => false,
                    'message' => 'Accès refusé - Admin uniquement'
                ]);
                exit;
            }

            $devisId = $data['devis_id'] ?? '';
            $newStatus = $data['statut'] ?? '';

            $validStatuses = ['En attente', 'En cours', 'Complété', 'Annulé'];
            if (empty($devisId) || !in_array($newStatus, $validStatuses)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'message' => 'Données invalides'
                ]);
                exit;
            }

            $allDevis = readDevis();
            $updated = false;

            foreach ($allDevis as &$devis) {
                if ($devis['id'] === $devisId) {
                    $devis['statut'] = $newStatus;
                    $devis['updated_at'] = date('Y-m-d H:i:s');
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                saveDevis($allDevis);
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

            $allDevis = readDevis();
            $updated = false;

            foreach ($allDevis as &$devis) {
                if ($devis['id'] === $devisId) {
                    $devis['response'] = $response;
                    $devis['response_date'] = date('Y-m-d H:i:s');
                    $devis['updated_at'] = date('Y-m-d H:i:s');
                    $updated = true;
                    break;
                }
            }

            if ($updated) {
                saveDevis($allDevis);
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

            $allDevis = readDevis();
            $filteredDevis = array_filter($allDevis, function ($devis) use ($devisId) {
                return $devis['id'] !== $devisId;
            });

            if (count($filteredDevis) < count($allDevis)) {
                saveDevis(array_values($filteredDevis));
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

            $allDevis = readDevis();

            $stats = [
                'total' => count($allDevis),
                'en_attente' => 0,
                'en_cours' => 0,
                'complete' => 0,
                'annule' => 0,
                'derniers_7_jours' => 0
            ];

            $now = time();
            $sevenDaysAgo = $now - (7 * 24 * 60 * 60);

            foreach ($allDevis as $devis) {
                switch ($devis['statut']) {
                    case 'En attente':
                        $stats['en_attente']++;
                        break;
                    case 'En cours':
                        $stats['en_cours']++;
                        break;
                    case 'Complété':
                        $stats['complete']++;
                        break;
                    case 'Annulé':
                        $stats['annule']++;
                        break;
                }

                $createdTimestamp = strtotime($devis['created_at']);
                if ($createdTimestamp >= $sevenDaysAgo) {
                    $stats['derniers_7_jours']++;
                }
            }

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
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage()
    ]);
}
