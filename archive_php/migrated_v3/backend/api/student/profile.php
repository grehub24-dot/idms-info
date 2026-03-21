<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../db.php';

$student_data = require_student();
$pdo = db();
$student_id = $student_data['student_id'];
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->prepare("SELECT s.*, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
        $stmt->execute([$student_id]);
        $student = $stmt->fetch();

        if (!$student) {
            json_response(['ok' => false, 'error' => 'Student not found'], 404);
        }

        json_response(['ok' => true, 'student' => $student]);
    } catch (PDOException $e) {
        json_response(['ok' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // We'll use a separate multipart/form-data handler if needed, 
    // but for now, let's support updating phone and email.
    
    $email = isset($_POST['email']) ? trim((string)$_POST['email']) : null;
    $phone = isset($_POST['phone_number']) ? trim((string)$_POST['phone_number']) : null;
    
    if (!$email || !$phone) {
        json_response(['ok' => false, 'error' => 'Email and phone number are required'], 400);
    }

    $pdo->beginTransaction();
    try {
        // Handle Profile Picture
        $profile_picture = null;
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../../images/profiles/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $index_number = $_SESSION['index_number'] ?? 'unknown';
            $file_name = $index_number . '_' . time() . '.' . $file_extension;
            $target_file = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                $profile_picture = 'images/profiles/' . $file_name;
            }
        }

        // Update user email
        $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->execute([$email, $user_id]);

        // Update student phone and picture
        if ($profile_picture) {
            $stmt = $pdo->prepare("UPDATE students SET phone_number = ?, profile_picture = ? WHERE id = ?");
            $stmt->execute([$phone, $profile_picture, $student_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE students SET phone_number = ? WHERE id = ?");
            $stmt->execute([$phone, $student_id]);
        }

        $pdo->commit();
        json_response(['ok' => true, 'message' => 'Profile updated successfully']);
    } catch (PDOException $e) {
        $pdo->rollBack();
        json_response(['ok' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
} else {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}
