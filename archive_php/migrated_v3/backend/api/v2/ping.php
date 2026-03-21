<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../middleware/cors.php';

json_response([
    'status' => 'ok',
    'message' => 'API v2 is working',
    'timestamp' => date('c'),
    'version' => '2.0.0'
]);
