<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../db.php';

$student_data = require_student();
$student_id = $student_data['student_id'];

try {
    // Fetch Student Info
    $stmt = $pdo->prepare("SELECT id, full_name, index_number, profile_picture FROM students WHERE id = ?");
    $stmt->execute([$student_id]);
    $student = $stmt->fetch();

    if (!$student) {
        json_response(['ok' => false, 'error' => 'Student not found'], 404);
    }

    // Fetch Payments
    $stmt = $pdo->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC");
    $stmt->execute([$student_id]);
    $payments = $stmt->fetchAll();

    // Calculate Total Paid
    $total_paid = 0;
    foreach ($payments as $p) {
        $total_paid += (float)$p['amount'];
    }

    // Fetch system settings for dues
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('current_academic_year', 'annual_dues_amount')");
    $settings = [];
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    $current_year = $settings['current_academic_year'] ?? '2025/2026';
    $required_dues = (float)($settings['annual_dues_amount'] ?? 100.00);

    // Paid this year
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE student_id = ? AND academic_year = ?");
    $stmt->execute([$student_id, $current_year]);
    $paid_this_year = (float)$stmt->fetchColumn();
    $outstanding = max(0, $required_dues - $paid_this_year);
    $status_text = $outstanding <= 0 ? 'Fully Paid' : 'Unpaid';
    $status_color = $outstanding <= 0 ? 'green' : 'red';

    // Fetch Notifications (last 3)
    // Assuming messages table exists as seen in dashboard.php
    // We need to handle the user_id for message_reads
    // In session.php, student session only has student_id. 
    // We might need user_id if students are also in users table.
    // Let's check schema or dashboard.php again.
    // Dashboard.php uses $_SESSION['user_id'].
    
    $user_id = $_SESSION['user_id'] ?? null;
    $recent_msgs = [];
    $unread_count = 0;

    if ($user_id) {
        // Fetch last 3 broadcast or direct messages
        $stmt = $pdo->prepare("
            SELECT id, title, content, is_broadcast, created_at FROM messages 
            WHERE is_broadcast = 1 OR receiver_id = ? 
            ORDER BY created_at DESC LIMIT 3
        ");
        $stmt->execute([$user_id]);
        $recent_msgs = $stmt->fetchAll();

        // Count unread messages
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM messages m 
            WHERE (m.is_broadcast = 1 OR m.receiver_id = ?) 
            AND NOT EXISTS (
                SELECT 1 FROM message_reads mr 
                WHERE mr.message_id = m.id AND mr.user_id = ?
            )
        ");
        $stmt->execute([$user_id, $user_id]);
        $unread_count = (int)$stmt->fetchColumn();
    }

    json_response([
        'ok' => true,
        'student' => $student,
        'stats' => [
            'total_paid' => $total_paid,
            'receipt_count' => count($payments),
            'outstanding' => $outstanding,
            'current_year' => $current_year,
            'status_text' => $status_text,
            'status_color' => $status_color
        ],
        'recent_payments' => array_slice($payments, 0, 5),
        'recent_messages' => $recent_msgs,
        'unread_count' => $unread_count
    ]);

} catch (PDOException $e) {
    json_response(['ok' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
}
