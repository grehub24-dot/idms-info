<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/api/config.php';
require_once __DIR__ . '/../../backend/api/middleware/cors.php';

try {
    require_once __DIR__ . '/../../backend/api/db.php';
    $pdo = db();
    
    // Check if user_profiles table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'user_profiles'");
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        // Create user_profiles table
        $sql = "
        CREATE TABLE user_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT UNIQUE NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            phone_number VARCHAR(20),
            bio TEXT,
            address TEXT,
            profile_picture VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )";
        
        $pdo->exec($sql);
        
        json_response([
            'success' => true,
            'message' => 'User profiles table created successfully',
            'table_structure' => [
                'id' => 'Primary key',
                'user_id' => 'Foreign key to users table',
                'full_name' => 'Student full name',
                'phone_number' => 'Contact phone',
                'bio' => 'Student biography',
                'address' => 'Student address',
                'profile_picture' => 'Path to profile image',
                'created_at' => 'Profile creation time',
                'updated_at' => 'Last update time'
            ]
        ]);
    } else {
        json_response([
            'success' => true,
            'message' => 'User profiles table already exists'
        ]);
    }
    
} catch (Throwable $e) {
    error_log('Profile table setup error: ' . $e->getMessage());
    json_response([
        'success' => false,
        'error' => 'Failed to setup profile table: ' . $e->getMessage()
    ], 500);
}
