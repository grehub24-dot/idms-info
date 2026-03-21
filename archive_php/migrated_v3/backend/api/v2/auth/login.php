<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$data = read_json_body();

if (!isset($data['email']) || !isset($data['password'])) {
    json_response(['error' => 'Email and password are required'], 400);
}

$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$password = $data['password'];

try {
    $pdo = db();
    
    // Check if user exists
    $stmt = $pdo->prepare('SELECT id, email, password, full_name, role FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        json_response(['error' => 'Invalid credentials'], 401);
    }
    
    // Verify password (assuming password_hash was used)
    if (!password_verify($password, $user['password'])) {
        json_response(['error' => 'Invalid credentials'], 401);
    }
    
    // Generate JWT
    $secret = env('JWT_SECRET', 'your-secret-key-change-this');
    $token_payload = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role'],
        'full_name' => $user['full_name']
    ];
    
    $token = generate_jwt($token_payload, $secret, 86400); // 24 hours
    
    json_response([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'role' => $user['role']
        ]
    ]);
    
} catch (Throwable $e) {
    error_log('Login error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}
