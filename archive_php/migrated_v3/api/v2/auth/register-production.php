<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/api/config.php';
require_once __DIR__ . '/../../../backend/api/db.php';
require_once __DIR__ . '/../../../backend/api/middleware/cors.php';
require_once __DIR__ . '/../../../backend/api/middleware/validation.php';
require_once __DIR__ . '/../../../backend/api/middleware/rate-limit.php';
require_once __DIR__ . '/../services/sms-service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

rate_limit(5, 300); // 5 registrations per 5 minutes

$data = read_json_body();

// Validate required fields
$errors = validate_required($data, ['email', 'phone_number']);
if (!empty($errors)) {
    validation_response($errors);
}

$email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
$phone_number = sanitize_string($data['phone_number']);
$role = $data['role'] ?? 'student';
$full_name = $data['full_name'] ?? '';

// Validate email
if (!validate_email($email)) {
    validation_response(['email' => 'Invalid email format']);
}

// Validate phone number
if (!preg_match('/^\+[1-9]\d{1,14}$/', $phone_number)) {
    validation_response(['phone_number' => 'Invalid phone number format. Use international format: +1234567890']);
}

try {
    $pdo = db();
    
    // Check if email already exists
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        json_response(['error' => 'Email already registered'], 409);
    }
    
    // Check if phone number already exists
    $stmt = $pdo->prepare('SELECT id FROM user_profiles WHERE phone_number = ?');
    $stmt->execute([$phone_number]);
    if ($stmt->fetch()) {
        json_response(['error' => 'Phone number already registered'], 409);
    }
    
    // Generate temporary password
    $temp_password = bin2hex(random_bytes(8)); // 16-character temporary password
    $hashed_temp_password = password_hash($temp_password, PASSWORD_DEFAULT);
    
    // Start transaction
    $pdo->beginTransaction();
    
    try {
        // Insert user with temporary password
        $stmt = $pdo->prepare('
            INSERT INTO users (email, password, role, status, is_password_reset) 
            VALUES (?, ?, ?, ?, ?)
        ');
        $stmt->execute([$email, $hashed_temp_password, $role, 'active', '0']);
        
        $user_id = $pdo->lastInsertId();
        
        // Create user profile record
        $stmt = $pdo->prepare('
            INSERT INTO user_profiles (user_id, full_name, phone_number, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
        ');
        $stmt->execute([$user_id, $full_name, $phone_number]);
        
        // Send SMS with temporary password
        $smsService = new SMSService();
        $smsResult = $smsService->sendTemporaryPassword($phone_number, $temp_password, $full_name);
        
        // Log registration attempt
        error_log("New user registration: {$email} (ID: {$user_id}), SMS sent: " . ($smsResult['success'] ? 'Yes' : 'No'));
        
        // Commit transaction
        $pdo->commit();
        
        // Return success response (without temp password in production)
        json_response([
            'success' => true,
            'message' => 'Registration successful! Temporary password has been sent to your phone.',
            'user' => [
                'id' => $user_id,
                'email' => $email,
                'role' => $role,
                'status' => 'active',
                'phone_number' => $phone_number
            ],
            'sms_status' => $smsResult['success'] ? 'sent' : 'failed',
            'next_steps' => [
                'Check your phone for the temporary password',
                'Use the temporary password to login',
                'Set your permanent password during first login',
                'Complete your profile information'
            ]
        ], 201);
        
    } catch (Throwable $e) {
        // Rollback on error
        $pdo->rollback();
        throw $e;
    }
    
} catch (Throwable $e) {
    error_log('Registration error: ' . $e->getMessage());
    json_response(['error' => 'Registration failed. Please try again later.'], 500);
}
