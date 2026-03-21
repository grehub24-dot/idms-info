<?php
require_once '../../includes/db.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $message_id = isset($data['message_id']) ? intval($data['message_id']) : 0;
    $user_id = $_SESSION['user_id'];

    if ($message_id > 0) {
        try {
            // Check if already read to prevent unnecessary inserts
            $checkStmt = $pdo->prepare("SELECT id FROM message_reads WHERE message_id = ? AND user_id = ?");
            $checkStmt->execute([$message_id, $user_id]);
            
            if ($checkStmt->rowCount() == 0) {
                $stmt = $pdo->prepare("INSERT INTO message_reads (message_id, user_id) VALUES (?, ?)");
                $stmt->execute([$message_id, $user_id]);
                echo json_encode(['success' => true, 'action' => 'marked_read']);
            } else {
                echo json_encode(['success' => true, 'action' => 'already_read']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid message ID']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>