<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/api/config.php';
require_once __DIR__ . '/../../../backend/api/db.php';
require_once __DIR__ . '/../../../backend/api/middleware/cors.php';
require_once __DIR__ . '/../../../backend/api/middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

try {
    $user = require_auth();
    
    // Get fresh user data from database
    $pdo = db();
    $stmt = $pdo->prepare('
        SELECT id, email, role, status, created_at, updated_at 
        FROM users 
        WHERE id = ?
    ');
    $stmt->execute([$user['user_id']]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        json_response(['error' => 'User not found'], 404);
    }
    
    json_response($user_data);
    
} catch (Throwable $e) {
    error_log('Get user error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}
