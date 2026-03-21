<?php

declare(strict_types=1);

require_once __DIR__ . '/../../backend/api/config.php';
require_once __DIR__ . '/../../backend/api/middleware/cors.php';

try {
    // Test database connection
    require_once __DIR__ . '/../../backend/api/db.php';
    $pdo = db();
    
    // Check if users table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'users'");
    $stmt->execute();
    $table_exists = $stmt->fetch();
    
    if (!$table_exists) {
        // Create users table
        $sql = "
        CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(255) NOT NULL,
            role ENUM('student', 'admin', 'super_admin') DEFAULT 'student',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        
        $pdo->exec($sql);
        
        // Insert a test user
        $hashed_password = password_hash('TestPassword123!', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('
            INSERT INTO users (email, password, full_name, role) 
            VALUES (?, ?, ?, ?)
        ');
        $stmt->execute(['test@example.com', $hashed_password, 'Test User', 'student']);
        
        json_response([
            'success' => true,
            'message' => 'Database setup completed successfully',
            'actions' => ['Created users table', 'Inserted test user'],
            'test_user' => [
                'email' => 'test@example.com',
                'password' => 'TestPassword123!'
            ]
        ]);
    } else {
        // Check if test user exists
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute(['test@example.com']);
        $user = $stmt->fetch();
        
        if (!$user) {
            // Insert test user
            $hashed_password = password_hash('TestPassword123!', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('
                INSERT INTO users (email, password, full_name, role) 
                VALUES (?, ?, ?, ?)
            ');
            $stmt->execute(['test@example.com', $hashed_password, 'Test User', 'student']);
        }
        
        json_response([
            'success' => true,
            'message' => 'Database already configured',
            'test_user' => [
                'email' => 'test@example.com',
                'password' => 'TestPassword123!'
            ]
        ]);
    }
    
} catch (Throwable $e) {
    error_log('Database setup error: ' . $e->getMessage());
    json_response([
        'success' => false,
        'error' => 'Database setup failed: ' . $e->getMessage(),
        'details' => [
            'db_host' => env('DB_HOST', 'not_set'),
            'db_name' => env('DB_NAME', 'not_set'),
            'db_user' => env('DB_USER', 'not_set')
        ]
    ], 500);
}
