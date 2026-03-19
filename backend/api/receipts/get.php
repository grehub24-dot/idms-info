<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

$receiptNumber = isset($_GET['receipt_number']) ? trim((string)$_GET['receipt_number']) : '';
if ($receiptNumber === '') {
    json_response(['error' => 'receipt_number is required'], 400);
}

$actor = current_actor();
if ($actor === null) {
    json_response(['error' => 'Unauthorized'], 401);
}

try {
    $pdo = db();

    $stmt = $pdo->prepare(
        'SELECT p.payment_id, p.amount, p.academic_year, p.semester, p.payment_date, p.payment_method, p.receipt_number, p.created_at,\n'
        . '       s.student_id, s.full_name, s.index_number, s.department, s.level, s.email, s.phone\n'
        . 'FROM payments p\n'
        . 'JOIN students s ON s.student_id = p.student_id\n'
        . 'WHERE p.receipt_number = ?\n'
        . 'LIMIT 1'
    );
    $stmt->execute([$receiptNumber]);
    $row = $stmt->fetch();

    if (!$row) {
        json_response(['error' => 'Receipt not found'], 404);
    }

    if ($actor['type'] === 'student') {
        if ((int)$row['student_id'] !== (int)$actor['student_id']) {
            json_response(['error' => 'Forbidden'], 403);
        }
    }

    json_response(['ok' => true, 'receipt' => $row]);
} catch (Throwable $e) {
    json_response(['error' => 'Failed to fetch receipt'], 500);
}
