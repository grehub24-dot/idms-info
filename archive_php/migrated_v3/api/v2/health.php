<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/api/config.php';
require_once __DIR__ . '/../../backend/api/middleware/cors.php';

try {
    // Try to connect to database
    $pdo = null;
    $db_status = 'not_configured';
    
    try {
        require_once __DIR__ . '/../../backend/api/db.php';
        $pdo = db();
        $pdo->query('SELECT 1');
        $db_status = 'connected';
    } catch (Throwable $e) {
        $db_status = 'error: ' . $e->getMessage();
    }
    
    $health_data = [
        'status' => 'healthy',
        'service' => 'infotess-api-v2',
        'version' => '2.0.0',
        'timestamp' => date('c'),
        'database' => $db_status,
        'environment' => env('APP_ENV', 'development')
    ];
    
    json_response($health_data);
    
} catch (Throwable $e) {
    error_log('Health check error: ' . $e->getMessage());
    json_response([
        'status' => 'unhealthy',
        'service' => 'infotess-api-v2',
        'database' => 'error',
        'error' => 'Health check failed: ' . $e->getMessage(),
        'timestamp' => date('c')
    ], 503);
}
