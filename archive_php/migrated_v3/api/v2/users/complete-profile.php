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

// Validate email if provided
if (isset($data['email'])) {
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    if (!validate_email($email)) {
        validation_response(['email' => 'Invalid email format']);
    }
}

// Handle profile picture upload
$profile_picture_path = null;
if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = __DIR__ . '/../../../../uploads/profile_pictures/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file = $_FILES['profile_picture'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    
    if (!in_array($file_extension, $allowed_extensions)) {
        json_response(['error' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.'], 400);
    }
    
    if ($file['size'] > 5 * 1024 * 1024) { // 5MB limit
        json_response(['error' => 'File too large. Maximum size is 5MB.'], 400);
    }
    
    $filename = 'profile_' . $user['user_id'] . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $filename;
    
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        json_response(['error' => 'Failed to upload profile picture'], 500);
    }
    
    $profile_picture_path = '/uploads/profile_pictures/' . $filename;
}

try {
    $pdo = db();
    
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
    
    // Get updated user data
    $stmt = $pdo->prepare('
        SELECT u.id, u.email, u.role, u.status, u.created_at, u.updated_at,
               p.full_name, p.phone_number, p.bio, p.address, p.profile_picture
        FROM users u
        LEFT JOIN user_profiles p ON u.id = p.user_id
        WHERE u.id = ?
    ');
    $stmt->execute([$user['user_id']]);
    $updated_user = $stmt->fetch();
    
    json_response([
        'success' => true,
        'message' => 'Profile completed successfully! Redirecting to dashboard.',
        'user' => $updated_user,
        'redirect_to' => 'dashboard'
    ]);
    
} catch (Throwable $e) {
    error_log('Profile completion error: ' . $e->getMessage());
    json_response(['error' => 'Internal server error'], 500);
}
