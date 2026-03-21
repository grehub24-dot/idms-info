<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/session.php';

$actor = require_student();
$student_id = $actor['student_id'];
$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Fetch Broadcast Messages
        $stmt = $pdo->query("SELECT id, title, content, is_broadcast, created_at FROM messages WHERE is_broadcast = 1 ORDER BY created_at DESC LIMIT 20");
        $broadcasts = $stmt->fetchAll();

        // Fetch Direct Messages
        $stmt = $pdo->prepare("SELECT id, title, content, is_broadcast, created_at FROM messages WHERE receiver_id = ? AND is_broadcast = 0 ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $direct_messages = $stmt->fetchAll();

        // Fetch read messages to mark them
        $stmt = $pdo->prepare("SELECT message_id FROM message_reads WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $read_ids = $stmt->fetchAll(PDO::FETCH_COLUMN);

        json_response([
            'ok' => true,
            'broadcasts' => $broadcasts,
            'direct_messages' => $direct_messages,
            'read_ids' => $read_ids
        ]);
    } catch (PDOException $e) {
        json_response(['ok' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'send_to_admin') {
        $subject = isset($input['subject']) ? trim((string)$input['subject']) : '';
        $content = isset($input['content']) ? trim((string)$input['content']) : '';

        if (empty($subject) || empty($content)) {
            json_response(['ok' => false, 'error' => 'Subject and content are required'], 400);
        }

        try {
            // Find an admin user
            $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            $admin_id = $stmt->fetchColumn();

            if (!$admin_id) {
                json_response(['ok' => false, 'error' => 'No administrator found'], 404);
            }

            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, title, content, is_broadcast) VALUES (?, ?, ?, ?, 0)");
            $stmt->execute([$user_id, $admin_id, $subject, $content]);

            json_response(['ok' => true, 'message' => 'Message sent to administrator successfully!']);
        } catch (PDOException $e) {
            json_response(['ok' => false, 'error' => 'Failed to send message: ' . $e->getMessage()], 500);
        }
    } elseif ($action === 'mark_read') {
        $message_id = $input['message_id'] ?? null;
        if (!$message_id) {
            json_response(['ok' => false, 'error' => 'Message ID required'], 400);
        }

        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO message_reads (message_id, user_id) VALUES (?, ?)");
            $stmt->execute([$message_id, $user_id]);
            json_response(['ok' => true]);
        } catch (PDOException $e) {
            json_response(['ok' => false, 'error' => 'Failed to mark as read'], 500);
        }
    } else {
        json_response(['ok' => false, 'error' => 'Invalid action'], 400);
    }
} else {
    json_response(['ok' => false, 'error' => 'Method not allowed'], 405);
}
