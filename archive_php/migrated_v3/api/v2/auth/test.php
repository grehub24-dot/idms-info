<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/api/config.php';
require_once __DIR__ . '/../../../backend/api/middleware/cors.php';

json_response([
    'success' => true,
    'message' => 'Auth endpoint is working',
    'timestamp' => date('c'),
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'unknown'
]);
