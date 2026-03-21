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

rate_limit(10, 60); // 10 password resets per minute

$data = read_json_body();

// Check if this is initial password setup or password reset
if (isset($data['temp_password']) && isset($data['new_password'])) {
    // Initial password setup with temporary password
    $errors = validate_required($data, ['email', 'temp_password', 'new_password']);
    if (!empty($errors)) {
        validation_response($errors);
    }
    
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $temp_password = $data['temp_password'];
    $new_password = $data['new_password'];
    
    // Validate email
    if (!validate_email($email)) {
        validation_response(['email' => 'Invalid email format']);
    }
    
    // Validate new password strength
    $password_errors = validate_password_strength($new_password);
    if (!empty($password_errors)) {
        validation_response(['new_password' => implode(', ', $password_errors)]);
    }
    
    try {
        $pdo = db();
        
        // Get user with temporary password
        $stmt = $pdo->prepare('SELECT id, email, password, role, status, is_password_reset FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            json_response(['error' => 'User not found'], 404);
        }
        
        if ($user['status'] !== 'active') {
            json_response(['error' => 'Account is not active'], 401);
        }
        
        // Verify temporary password
        if (!password_verify($temp_password, $user['password'])) {
            json_response(['error' => 'Invalid temporary password'], 401);
        }
        
        // Check if password has already been reset
        if ($user['is_password_reset'] == 1) {
            json_response(['error' => 'Password has already been set. Please use login instead.'], 400);
        }
        
        // Hash new password
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password and mark as reset
        $stmt = $pdo->prepare('
            UPDATE users 
            SET password = ?, is_password_reset = 1, updated_at = NOW() 
            WHERE id = ?
        ');
        $stmt->execute([$new_hashed_password, $user['id']]);
        
        json_response([
            'success' => true,
            'message' => 'Password set successfully. You can now login with your new password.'
        ]);
        
    } catch (Throwable $e) {
        error_log('Password setup error: ' . $e->getMessage());
        json_response(['error' => 'Internal server error'], 500);
    }
    
} elseif (isset($data['current_password']) && isset($data['new_password'])) {
    // Regular password change (for logged-in users)
    $user = require_auth();
    
    $errors = validate_required($data, ['current_password', 'new_password']);
    if (!empty($errors)) {
        validation_response($errors);
    }
    
    $current_password = $data['current_password'];
    $new_password = $data['new_password'];
    
    // Validate new password strength
    $password_errors = validate_password_strength($new_password);
    if (!empty($password_errors)) {
        validation_response(['new_password' => implode(', ', $password_errors)]);
    }
    
    try {
        $pdo = db();
        
        // Get current user password
        $stmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->execute([$user['user_id']]);
        $user_data = $stmt->fetch();
        
        if (!$user_data) {
            json_response(['error' => 'User not found'], 404);
        }
        
        // Verify current password
        if (!password_verify($current_password, $user_data['password'])) {
            json_response(['error' => 'Current password is incorrect'], 401);
        }
        
        // Hash new password
        $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $pdo->prepare('UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$new_hashed_password, $user['user_id']]);
        
        json_response([
            'success' => true,
            'message' => 'Password changed successfully'
        ]);
        
    } catch (Throwable $e) {
        error_log('Password change error: ' . $e->getMessage());
        json_response(['error' => 'Internal server error'], 500);
    }
    
} else {
    json_response(['error' => 'Invalid request. Provide either temp_password + new_password for initial setup, or current_password + new_password for password change.'], 400);
}
