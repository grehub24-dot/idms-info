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

$required_fields = ['email', 'password', 'full_name'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || empty(trim($data[$field]))) {
        json_response(['error' => ucfirst($field) . ' is required'], 400);
    }
}

$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$password = $data['password'];
$full_name = trim($data['full_name']);
$role = $data['role'] ?? 'student';

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['error' => 'Invalid email format'], 400);
}

// Validate password strength
if (strlen($password) < 8) {
    json_response(['error' => 'Password must be at least 8 characters long'], 400);
}

try {
    $pdo = db();
    
    // Check if email already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_response(['error' => 'Email already registered'], 409);
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $pdo->prepare('
        INSERT INTO users (email, password, full_name, role, created_at) 
        VALUES (?, ?, ?, ?, NOW())
    ');
    $stmt->execute([$email, $hashed_password, $full_name, $role]);
    
    $user_id = $pdo->lastInsertId();
    
    // Generate JWT
    $secret = env('JWT_SECRET', 'your-secret-key-change-this');
    $token_payload = [
        'user_id' => $user_id,
        'email' => $email,
        'role' => $role,
        'full_name' => $full_name
    ];
    
    $token = generate_jwt($token_payload, $secret, 86400); // 24 hours
    
    json_response([
        'success' => true,
        'token' => $token,
        'user' => [
            'id' => $user_id,
            'email' => $email,
            'full_name' => $full_name,
            'role' => $role
        ]
    ], 201);
    
} catch (Throwable $e) {
    error_log('Registration error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}
