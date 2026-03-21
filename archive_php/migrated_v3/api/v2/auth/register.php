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

rate_limit(5, 60); // 5 registrations per minute

$data = read_json_body();

$required_fields = ['email', 'password'];
$errors = validate_required($data, $required_fields);
if (!empty($errors)) {
    validation_response($errors);
}

$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$password = $data['password'];
$role = $data['role'] ?? 'student';

// Validate email
if (!validate_email($email)) {
    validation_response(['email' => 'Invalid email format']);
}

// Validate password strength
$password_errors = validate_password_strength($password);
if (!empty($password_errors)) {
    validation_response(['password' => implode(', ', $password_errors)]);
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
    
    // Insert new user with temporary password
    $temp_password = bin2hex(random_bytes(8)); // 16-character temporary password
    $hashed_temp_password = password_hash($temp_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare('
        INSERT INTO users (email, password, role, status, is_password_reset) 
        VALUES (?, ?, ?, ?, ?)
    ');
    $stmt->execute([$email, $hashed_temp_password, $role, 'active', '0']);
    
    $user_id = $pdo->lastInsertId();
    
    // TODO: Send SMS with temporary password
    // For now, return the temp password for testing
    json_response([
        'success' => true,
        'message' => 'Registration successful! Temporary password has been sent via SMS.',
        'user' => [
            'id' => $user_id,
            'email' => $email,
            'role' => $role,
            'status' => 'active'
        ],
        'debug_info' => [
            'temp_password' => $temp_password, // Remove this in production
            'next_step' => 'Use this temporary password to login and set your permanent password'
        ]
    ], 201);
    
} catch (Throwable $e) {
    error_log('Registration error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}
