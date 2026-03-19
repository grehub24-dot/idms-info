<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/session.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

require_admin();

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
$limit = max(1, min(200, $limit));

$indexNumber = isset($_GET['index_number']) ? trim((string)$_GET['index_number']) : '';

try {
    $pdo = db();

    if ($indexNumber !== '') {
        $stmt = $pdo->prepare(
            'SELECT p.payment_id, p.amount, p.academic_year, p.semester, p.payment_date, p.payment_method, p.receipt_number, p.created_at,\n'
            . '       s.student_id, s.full_name, s.index_number\n'
            . 'FROM payments p\n'
            . 'JOIN students s ON s.student_id = p.student_id\n'
            . 'WHERE s.index_number = ?\n'
            . 'ORDER BY p.payment_id DESC\n'
            . 'LIMIT ?'
        );
        $stmt->execute([$indexNumber, $limit]);
    } else {
        $stmt = $pdo->prepare(
            'SELECT p.payment_id, p.amount, p.academic_year, p.semester, p.payment_date, p.payment_method, p.receipt_number, p.created_at,\n'
            . '       s.student_id, s.full_name, s.index_number\n'
            . 'FROM payments p\n'
            . 'JOIN students s ON s.student_id = p.student_id\n'
            . 'ORDER BY p.payment_id DESC\n'
            . 'LIMIT ?'
        );
        $stmt->execute([$limit]);
    }

    $rows = $stmt->fetchAll();
    json_response(['ok' => true, 'payments' => $rows]);
} catch (Throwable $e) {
    json_response(['error' => 'Failed to fetch payments'], 500);
}
