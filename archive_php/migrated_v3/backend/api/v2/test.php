<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/cors.php';

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'message' => 'API v2 routing is working!',
    'timestamp' => date('c'),
    'method' => $_SERVER['REQUEST_METHOD'],
    'path' => $_GET['path'] ?? 'none'
]);
