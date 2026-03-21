<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/api/config.php';
require_once __DIR__ . '/../../../backend/api/db.php';
require_once __DIR__ . '/../../../backend/api/middleware/cors.php';
require_once __DIR__ . '/../../../backend/api/middleware/validation.php';
require_once __DIR__ . '/../../../backend/api/middleware/rate-limit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

rate_limit(3, 300); // 3 requests per 5 minutes

$data = read_json_body();

$errors = validate_required($data, ['email']);
if (!empty($errors)) {
    validation_response($errors);
}

$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);

if (!validate_email($email)) {
    validation_response(['email' => 'Invalid email format']);
}

try {
    $pdo = db();
    
    // Check if user exists
    $stmt = $pdo->prepare('SELECT id, email, role, status FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Don't reveal if user exists or not for security
        json_response([
            'success' => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.'
        ]);
    }
    
    if ($user['status'] !== 'active') {
        json_response([
            'success' => true,
            'message' => 'If an account with that email exists, a password reset link has been sent.'
        ]);
    }
    
    // Generate temporary password
    $temp_password = bin2hex(random_bytes(8)); // 16-character temporary password
    $temp_hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
    
    // Update user with temporary password and mark as needing reset
    $stmt = $pdo->prepare('
        UPDATE users 
        SET password = ?, is_password_reset = 0, updated_at = NOW() 
        WHERE id = ?
    ');
    $stmt->execute([$temp_hashed_password, $user['id']]);
    
    // TODO: Send email with temporary password
    // For now, return the temp password for testing
    json_response([
        'success' => true,
        'message' => 'Temporary password has been generated. In production, this would be sent to your email.',
        'debug_info' => [
            'temp_password' => $temp_password, // Remove this in production
            'email' => $email,
            'next_step' => 'Use the reset-password endpoint with this temporary password to set your new password'
        ]
    ]);
    
} catch (Throwable $e) {
    error_log('Forgot password error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}
