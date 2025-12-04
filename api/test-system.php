<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$results = [
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'files' => [],
    'directories' => []
];

// Check Data Files
$dataFiles = [
    'data/accounts.json',
    'data/devis.json',
    'data/clients.json',
    'data/chat-messages.json'
];

foreach ($dataFiles as $file) {
    $path = __DIR__ . '/../' . $file;
    $results['files'][$file] = [
        'exists' => file_exists($path),
        'writable' => file_exists($path) && is_writable($path),
        'size' => file_exists($path) ? filesize($path) : 0,
        'perms' => file_exists($path) ? substr(sprintf('%o', fileperms($path)), -4) : '0000'
    ];
}

// Check Directories
$dirs = [
    'data',
    'api',
    'assets',
    'pages',
    'images'
];

foreach ($dirs as $dir) {
    $path = __DIR__ . '/../' . $dir;
    $results['directories'][$dir] = [
        'exists' => is_dir($path),
        'writable' => is_dir($path) && is_writable($path)
    ];
}

echo json_encode(['success' => true, 'system' => $results]);
