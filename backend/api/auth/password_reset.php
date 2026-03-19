<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/../../includes/SMSHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

start_session();

if (!isset($_SESSION['user_id'])) {
    json_response(['error' => 'Unauthorized'], 401);
}

$input = json_decode(file_get_contents('php://input'), true);
$new_password = $input['new_password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

if (empty($new_password) || empty($confirm_password)) {
    json_response(['error' => 'All fields are required.'], 400);
}

if ($new_password !== $confirm_password) {
    json_response(['error' => 'Passwords do not match.'], 400);
}

if (strlen($new_password) < 6) {
    json_response(['error' => 'Password must be at least 6 characters long.'], 400);
}

$user_id = $_SESSION['user_id'];
$hash = password_hash($new_password, PASSWORD_DEFAULT);

try {
    // Update password and set is_password_reset to 1
    $stmt = $pdo->prepare("UPDATE users SET password = :password, is_password_reset = 1 WHERE id = :id");
    $stmt->execute([
        'password' => $hash,
        'id' => $user_id
    ]);

    // Fetch student phone number for SMS
    $stmt_s = $pdo->prepare("SELECT phone_number, full_name FROM students WHERE user_id = :uid");
    $stmt_s->execute(['uid' => $user_id]);
    $student = $stmt_s->fetch();

    if ($student && $student['phone_number']) {
        try {
            $sms = new SMSHelper();
            $message = "Hello " . $student['full_name'] . ", your INFOTESS SDMS password has been successfully reset. You can now use your new password to login. Thank you.";
            $sms->send($student['phone_number'], $message);
        } catch (Exception $e) {
            // Log SMS error but don't fail the password reset
            error_log("SMS Error: " . $e->getMessage());
        }
    }
    
    $_SESSION['is_password_reset'] = 1;

    json_response(['ok' => true, 'message' => 'Password reset successfully!']);
} catch (PDOException $e) {
    json_response(['error' => 'An error occurred. Please try again later.'], 500);
}
