<?php

declare(strict_types=1);

require_once __DIR__ . '/../../../backend/api/config.php';
require_once __DIR__ . '/../../../backend/api/db.php';
require_once __DIR__ . '/../../../backend/api/middleware/cors.php';
require_once __DIR__ . '/../../../backend/api/middleware/auth.php';
require_once __DIR__ . '/../../../backend/api/middleware/validation.php';
require_once __DIR__ . '/../../../backend/api/middleware/rate-limit.php';
require_once __DIR__ . '/../services/image-service.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

rate_limit(5, 60); // 5 profile updates per minute

$user = require_auth();

$data = read_json_body();

// Validate required fields for profile completion
$errors = validate_required($data, ['full_name', 'phone_number']);
if (!empty($errors)) {
    validation_response($errors);
}

$full_name = sanitize_string($data['full_name']);
$phone_number = sanitize_string($data['phone_number']);
$bio = $data['bio'] ?? '';
$address = $data['address'] ?? '';

// Validate phone number
if (!preg_match('/^\+[1-9]\d{1,14}$/', $phone_number)) {
    validation_response(['phone_number' => 'Invalid phone number format. Use international format: +1234567890']);
}

// Validate email if provided
if (isset($data['email'])) {
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    if (!validate_email($email)) {
        validation_response(['email' => 'Invalid email format']);
    }
}

$imageService = new ImageService();
$profile_picture_path = null;

// Handle profile picture upload
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $imageResult = $imageService->processProfilePicture($_FILES['profile_picture'], $user['user_id']);
    
    if (!$imageResult['success']) {
        json_response(['error' => $imageResult['error']], 400);
    }
    
    $profile_picture_path = $imageResult['path'];
}

try {
    $pdo = db();
    
    // Check if phone number is already taken by another user
    $stmt = $pdo->prepare('SELECT user_id FROM user_profiles WHERE phone_number = ? AND user_id != ?');
    $stmt->execute([$phone_number, $user['user_id']]);
    if ($stmt->fetch()) {
        json_response(['error' => 'Phone number already registered by another user'], 409);
    }
    
    // Check if user has profile record
    $stmt = $pdo->prepare('SELECT id FROM user_profiles WHERE user_id = ?');
    $stmt->execute([$user['user_id']]);
    $profile_exists = $stmt->fetch();
    
    if ($profile_exists) {
        // Update existing profile
        $sql = 'UPDATE user_profiles SET full_name = ?, phone_number = ?, bio = ?, address = ?, updated_at = NOW()';
        $params = [$full_name, $phone_number, $bio, $address];
        
        if ($profile_picture_path) {
            $sql .= ', profile_picture = ?';
            $params[] = $profile_picture_path;
        }
        
        $sql .= ' WHERE user_id = ?';
        $params[] = $user['user_id'];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    } else {
        // Create new profile record
        $sql = 'INSERT INTO user_profiles (user_id, full_name, phone_number, bio, address, profile_picture, created_at, updated_at)';
        $sql .= ' VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())';
        $params = [$user['user_id'], $full_name, $phone_number, $bio, $address, $profile_picture_path];
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
    }
    
    // Update email in users table if provided
    if (isset($email) && $email !== $user['email']) {
        // Check if email is already taken
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id != ?');
        $stmt->execute([$email, $user['user_id']]);
        if ($stmt->fetch()) {
            json_response(['error' => 'Email already taken'], 409);
        }
        
        $stmt = $pdo->prepare('UPDATE users SET email = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$email, $user['user_id']]);
    }
    
    // Get updated user data with profile
    $stmt = $pdo->prepare('
        SELECT u.id, u.email, u.role, u.status, u.created_at, u.updated_at,
               p.full_name, p.phone_number, p.bio, p.address, p.profile_picture
        FROM users u
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE u.id = ?
    ');
    $stmt->execute([$user['user_id']]);
    $updated_user = $stmt->fetch();
    
    // Log profile completion
    error_log("Profile completed for user {$user['user_id']}: {$full_name}");
    
    json_response([
        'success' => true,
        'message' => 'Profile completed successfully! Redirecting to dashboard.',
        'user' => $updated_user,
        'redirect_to' => 'dashboard',
        'profile_completion' => [
            'full_name' => true,
            'phone_number' => true,
            'profile_picture' => $profile_picture_path ? true : false,
            'is_complete' => true
        ]
    ]);
    
} catch (Throwable $e) {
    error_log('Profile completion error: ' . $e->getMessage());
    json_response(['error' => 'Failed to complete profile. Please try again.'], 500);
}
