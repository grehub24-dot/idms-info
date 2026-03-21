<?php
session_start();
header('Content-Type: application/json');
require_once '../../includes/db.php';

// If already logged in, you could return success, but we'll focus on the POST login flow
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method Not Allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$identifier = trim($data['identifier'] ?? '');
$password = $data['password'] ?? '';

if (empty($identifier) || empty($password)) {
    echo json_encode(['success' => false, 'error' => 'Please enter both identifier and password.']);
    exit;
}

try {
    // Check if it's an email (Admin) or Index Number (Student)
    if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
        // Admin/Executive Login
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $identifier]);
        $user = $stmt->fetch();
    } else {
        // Student Login
        $stmt = $pdo->prepare("
            SELECT u.*, s.index_number 
            FROM users u 
            JOIN students s ON u.id = s.user_id 
            WHERE s.index_number = :index_number
        ");
        $stmt->execute(['index_number' => $identifier]);
        $user = $stmt->fetch();
    }

    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] !== 'active') {
            echo json_encode(['success' => false, 'error' => 'Your account is inactive or banned. Please contact support.']);
            exit;
        }

        // Login Success - In a full Vercel setup, we'd use JWT or DB sessions.
        // For this PoC, we still use the standard PHP session so it doesn't break existing pages
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['actor_type'] = $user['role']; // Add actor_type for session.php compatibility
        
        $response = [
            'success' => true,
            'role' => $user['role'],
            'redirect' => ''
        ];

        if ($user['role'] === 'student') {
            $stmt_s = $pdo->prepare("SELECT * FROM students WHERE user_id = :uid");
            $stmt_s->execute(['uid' => $user['id']]);
            $student = $stmt_s->fetch();
            
            $_SESSION['student_id'] = $student['id'];
            $_SESSION['admin_id'] = null; // Ensure admin_id is null for student
            $_SESSION['index_number'] = $student['index_number'];
            $_SESSION['name'] = $student['full_name'];
            
            $is_reset = isset($user['is_password_reset']) ? $user['is_password_reset'] : 0;
            $_SESSION['is_password_reset'] = $is_reset;
            
            if ($is_reset == 0) {
                $response['redirect'] = 'student/password-reset.php';
            } else {
                $response['redirect'] = 'student/dashboard.php';
            }
        } else {
            $_SESSION['admin_id'] = $user['id']; // Use user id as admin id
            $_SESSION['student_id'] = null; // Ensure student_id is null for admin
            $_SESSION['name'] = "Admin";
            $response['redirect'] = 'admin/dashboard.php';
        }

        echo json_encode($response);
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid credentials. Please try again.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Server error occurred.']);
}
