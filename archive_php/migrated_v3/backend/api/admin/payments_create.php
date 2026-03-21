<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../../includes/ReceiptGenerator.php';
require_once __DIR__ . '/../../includes/SMSHelper.php';
require_once __DIR__ . '/../../includes/Mailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

require_admin();

$body = read_json_body();

$indexNumber = isset($body['index_number']) ? trim((string)$body['index_number']) : '';
$amount = isset($body['amount']) ? (float)$body['amount'] : 0.0;
$academicYear = isset($body['academic_year']) ? trim((string)$body['academic_year']) : '';
$semester = isset($body['semester']) ? trim((string)$body['semester']) : '';
$paymentDate = isset($body['payment_date']) ? trim((string)$body['payment_date']) : '';
$paymentMethod = isset($body['payment_method']) ? trim((string)$body['payment_method']) : '';

if ($indexNumber === '' || $amount <= 0 || $academicYear === '' || $semester === '' || $paymentDate === '' || $paymentMethod === '') {
    json_response(['error' => 'index_number, amount, academic_year, semester, payment_date, payment_method are required'], 400);
}

$receiptNumber = 'SDMS-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(4)));

try {
    $pdo = db();

    $stmt = $pdo->prepare('SELECT s.id as student_id, s.full_name, s.index_number, s.phone_number, s.department, s.level, u.email FROM students s LEFT JOIN users u ON s.user_id = u.id WHERE s.index_number = ? LIMIT 1');
    $stmt->execute([$indexNumber]);
    $student = $stmt->fetch();

    if (!$student) {
        json_response(['error' => 'Student not found'], 404);
    }

    $studentId = (int)$student['student_id'];

    $stmt = $pdo->prepare(
        "INSERT INTO payments (student_id, amount, academic_year, semester, payment_date, payment_method, receipt_number) "
        . "VALUES (?, ?, ?, ?, ?, ?, ?)"
    );

    $stmt->execute([
        $studentId,
        $amount,
        $academicYear,
        $semester,
        $paymentDate,
        $paymentMethod,
        $receiptNumber,
    ]);

    $paymentId = (int)$pdo->lastInsertId();

    // Fetch Settings for Dues
    $stmt_settings = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'annual_dues_amount'");
    $settings_dues = $stmt_settings->fetchColumn();
    $requiredDues = $settings_dues !== false ? (float)$settings_dues : 100.00;

    // Fetch student total paid for the year to calculate balance
    $stmt_paid = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE student_id = ? AND academic_year = ?");
    $stmt_paid->execute([$studentId, $academicYear]);
    $totalPaid = (float)$stmt_paid->fetchColumn();
    $currentBalance = max(0, $requiredDues - $totalPaid);

    // Generate Receipt
    $generator = new ReceiptGenerator();
    $receipt_path = $generator->generate(
        $paymentId, 
        $receiptNumber, 
        $student, 
        $amount, 
        $paymentDate, 
        $student['level'], 
        '', 
        $student['department'], 
        $currentBalance,
        $academicYear,
        $semester,
        $paymentMethod
    );
    
    // Send SMS
    if (!empty($student['phone_number'])) {
        $sms = new SMSHelper();
        $sms_message = "Hello " . $student['full_name'] . ", your payment of GHS " . number_format($amount, 2) . " for " . $academicYear . " " . $semester . " has been received. Receipt #: " . $receiptNumber . ". Thank you.";
        $sms->send($student['phone_number'], $sms_message);
    }

    // Send Email
    if (!empty($student['email'])) {
        $mailer = new Mailer();
        $receipt_html = file_get_contents(__DIR__ . '/../../receipts/' . $receipt_path);
        $mailer->sendHTML($student['email'], "Payment Receipt - " . $receiptNumber, $receipt_html);
    }

    json_response(['ok' => true, 'payment_id' => $paymentId, 'receipt_number' => $receiptNumber]);
} catch (Throwable $e) {
    json_response(['error' => 'Failed to create payment: ' . $e->getMessage()], 500);
}
