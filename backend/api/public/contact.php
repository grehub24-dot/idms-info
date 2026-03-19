<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$name = trim($input['name'] ?? '');
$email = trim($input['email'] ?? '');
$subject = trim($input['subject'] ?? 'No Subject');
$message = trim($input['message'] ?? '');

if (empty($name) || empty($email) || empty($message)) {
    json_response(['error' => 'Please fill in all required fields'], 400);
}

try {
    $stmt = $pdo->prepare("INSERT INTO contact_submissions (name, email, subject, message) VALUES (?, ?, ?, ?)");
    $stmt->execute([$name, $email, $subject, $message]);
    json_response(['ok' => true, 'message' => "Thank you for contacting us, $name. Your message has been received."]);
} catch (PDOException $e) {
    json_response(['error' => "Sorry, there was an error sending your message. Please try again later."], 500);
}
