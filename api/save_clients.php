<?php
// save_clients.php - DEPRECATED (MIGRATED TO SQL)
// This file is kept for compatibility but does nothing.
// Updates should be done via devis-manager.php

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

echo json_encode([
    'success' => true,
    'message' => 'Deprecated endpoint. Please use devis-manager.php for updates.'
]);
