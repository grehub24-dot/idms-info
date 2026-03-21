<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/api/config.php';
require_once __DIR__ . '/../../../backend/api/db.php';
require_once __DIR__ . '/../../../backend/api/middleware/cors.php';
require_once __DIR__ . '/../../../backend/api/middleware/auth.php';
require_once __DIR__ . '/../../../backend/api/middleware/validation.php';
require_once __DIR__ . '/../../../backend/api/middleware/rate-limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

rate_limit_strict(10, 60); // 10 requests per minute for login

$data = read_json_body();

$errors = validate_required($data, ['email', 'password']);
if (!empty($errors)) {
    validation_response($errors);
}

$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$password = $data['password'];

if (!validate_email($email)) {
    validation_response(['email' => 'Invalid email format']);
}

try {
    $pdo = db();
    
    // Check if user exists
    $stmt = $pdo->prepare('SELECT id, email, password, role, status, is_password_reset FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        json_response(['error' => 'Invalid credentials'], 401);
    }
    
    // Check if user is active
    if ($user['status'] !== 'active') {
        json_response(['error' => 'Account is not active'], 401);
    }
    
    // Verify password
    if (!password_verify($password, $user['password'])) {
        json_response(['error' => 'Invalid credentials'], 401);
    }
    
    // Check if user needs to set permanent password
    if ($user['is_password_reset'] == 0) {
        json_response([
            'error' => 'Password reset required',
            'requires_password_reset' => true,
            'message' => 'You must set a permanent password before logging in. Use the password reset endpoint.'
        ], 401);
    }
    
    // Generate JWT
    $secret = env('JWT_SECRET', 'your-secret-key-change-this');
    $token_payload = [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'role' => $user['role']
    ];
    
    $token = generate_jwt($token_payload, $secret, 86400); // 24 hours
    
    json_response([
        'token' => $token,
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'status' => $user['status']
        ]
    ]);
    
} catch (Throwable $e) {
    error_log('Login error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}
