<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../middleware/cors.php';

try {
    $pdo = db();
    $pdo->query('SELECT 1');
    
    $health_data = [
        'status' => 'healthy',
        'service' => 'infotess-api-v2',
        'version' => '2.0.0',
        'timestamp' => date('c'),
        'database' => 'connected',
        'environment' => env('APP_ENV', 'development')
    ];
    
    json_response($health_data);
    
} catch (Throwable $e) {
    error_log('Health check error: ' . $e->getMessage());
    json_response([
        'status' => 'unhealthy',
        'service' => 'infotess-api-v2',
        'database' => 'error',
        'error' => 'Database connection failed',
        'timestamp' => date('c')
    ], 503);
}
