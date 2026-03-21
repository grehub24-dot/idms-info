<?php

declare(strict_types=1);

require_once __DIR__ . '/../../config.php';
require_once __DIR__ . '/../../db.php';
require_once __DIR__ . '/../../middleware/cors.php';
require_once __DIR__ . '/../../middleware/auth.php';
require_once __DIR__ . '/../../middleware/validation.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $user = require_auth();
        
        $pdo = db();
        $stmt = $pdo->prepare('
            SELECT id, email, full_name, role, created_at, updated_at 
            FROM users 
            WHERE id = ?
        ');
        $stmt->execute([$user['user_id']]);
        $profile = $stmt->fetch();
        
        if (!$profile) {
            json_response(['error' => 'User not found'], 404);
        }
        
        json_response($profile);
        
    } catch (Throwable $e) {
        error_log('Get profile error: ' . $e->getMessage());
        json_response(['error' => 'Internal server error'], 500);
    }
    
} elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    try {
        $user = require_auth();
        $data = read_json_body();
        
        $errors = [];
        
        if (isset($data['full_name'])) {
            $full_name = sanitize_string($data['full_name']);
            if (empty($full_name)) {
                $errors['full_name'] = 'Full name cannot be empty';
            }
        }
        
        if (isset($data['email'])) {
            $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
            if (!validate_email($email)) {
                $errors['email'] = 'Invalid email format';
            }
        }
        
        if (!empty($errors)) {
            validation_response($errors);
        }
        
        $pdo = db();
        
        // Check if email is being changed and if it's already taken
        if (isset($email) && $email !== $user['email']) {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $user['user_id']]);
            if ($stmt->fetch()) {
                json_response(['error' => 'Email already taken'], 409);
            }
        }
        
        // Build update query
        $update_fields = [];
        $update_values = [];
        
        if (isset($full_name)) {
            $update_fields[] = 'full_name = ?';
            $update_values[] = $full_name;
        }
        
        if (isset($email)) {
            $update_fields[] = 'email = ?';
            $update_values[] = $email;
        }
        
        if (empty($update_fields)) {
            json_response(['error' => 'No valid fields to update'], 400);
        }
        
        $update_fields[] = 'updated_at = NOW()';
        $update_values[] = $user['user_id'];
        
        $sql = 'UPDATE users SET ' . implode(', ', $update_fields) . ' WHERE id = ?';
        $stmt = $pdo->prepare($sql);
        $stmt->execute($update_values);
        
        json_response(['message' => 'Profile updated successfully']);
        
    } catch (Throwable $e) {
        error_log('Update profile error: ' . $e->getMessage());
        json_response(['error' => 'Internal server error'], 500);
    }
    
} else {
    json_response(['error' => 'Method not allowed'], 405);
}
