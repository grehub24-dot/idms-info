<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/api/config.php';
require_once __DIR__ . '/../../../backend/api/db.php';
require_once __DIR__ . '/../../../backend/api/middleware/cors.php';

try {
    $pdo = db();
    
    // Get the latest test user
    $stmt = $pdo->prepare('SELECT id, email, password FROM users WHERE email = ? ORDER BY id DESC LIMIT 1');
    $stmt->execute(['test@example.com']);
    $user = $stmt->fetch();
    
    if (!$user) {
        json_response(['error' => 'User not found'], 404);
    }
    
    $test_password = 'TestPassword123!';
    $password_verify = password_verify($test_password, $user['password']);
    
    json_response([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email']
        ],
        'password_test' => [
            'test_password' => $test_password,
            'password_verify_result' => $password_verify,
            'stored_hash' => $user['password'],
            'hash_info' => password_get_info($user['password'])
        ],
        'suggestion' => $password_verify ? 'Password should work!' : 'Password verification failed - try a different password'
    ]);
    
} catch (Throwable $e) {
    error_log('Password debug error: ' . $e->getMessage());
    json_response(['error' => 'Debug failed: ' . $e->getMessage()], 500);
}
