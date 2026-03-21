<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

try {
    $user = require_auth();
    
    // Get fresh user data from database
    $pdo = db();
    $stmt = $pdo->prepare('
        SELECT id, email, full_name, role, created_at, updated_at 
        FROM users 
        WHERE id = ?
    ');
    $stmt->execute([$user['user_id']]);
    $user_data = $stmt->fetch();
    
    if (!$user_data) {
        json_response(['error' => 'User not found'], 404);
    }
    
    json_response([
        'success' => true,
        'user' => $user_data
    ]);
    
} catch (Throwable $e) {
    error_log('Get user error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}
