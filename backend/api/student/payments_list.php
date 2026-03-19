<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

$actor = require_student();
$studentId = (int)$actor['student_id'];

try {
    $stmt = $pdo->prepare(
        "SELECT id, amount, academic_year, semester, payment_date, payment_method, receipt_number, created_at 
        FROM payments 
        WHERE student_id = ? 
        ORDER BY payment_date DESC"
    );
    $stmt->execute([$studentId]);

    $rows = $stmt->fetchAll();
    json_response(['ok' => true, 'payments' => $rows]);
} catch (Throwable $e) {
    json_response(['ok' => false, 'error' => 'Failed to fetch payments: ' . $e->getMessage()], 500);
}
