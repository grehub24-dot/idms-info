<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/api/config.php';
require_once __DIR__ . '/../../backend/api/middleware/cors.php';

try {
    require_once __DIR__ . '/../../backend/api/db.php';
    $pdo = db();
    
    // Show all tables
    $stmt = $pdo->query('SHOW TABLES');
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $result = [
        'success' => true,
        'database' => env('DB_NAME', 'unknown'),
        'tables' => $tables,
        'env_vars' => [
            'DB_HOST' => env('DB_HOST', 'not_set'),
            'DB_NAME' => env('DB_NAME', 'not_set'),
            'DB_USER' => env('DB_USER', 'not_set'),
            'DB_PASS' => env('DB_PASS', 'not_set') ? '***set***' : 'not_set'
        ]
    ];
    
    // Check if users table exists and show its structure
    if (in_array('users', $tables)) {
        $stmt = $pdo->query('DESCRIBE users');
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result['users_table'] = $columns;
        
        // Show sample data
        $stmt = $pdo->query('SELECT * FROM users LIMIT 3');
        $sample_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $result['sample_users'] = $sample_data;
    }
    
    json_response($result);
    
} catch (Throwable $e) {
    error_log('DB check error: ' . $e->getMessage());
    json_response([
        'success' => false,
        'error' => 'Database check failed: ' . $e->getMessage(),
        'env_vars' => [
            'DB_HOST' => env('DB_HOST', 'not_set'),
            'DB_NAME' => env('DB_NAME', 'not_set'),
            'DB_USER' => env('DB_USER', 'not_set'),
            'DB_PASS' => env('DB_PASS', 'not_set') ? '***set***' : 'not_set'
        ]
    ], 500);
}
