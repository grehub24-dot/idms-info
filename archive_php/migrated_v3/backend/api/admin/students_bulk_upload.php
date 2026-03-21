<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$admin = require_admin();
$actorAdminId = (int)$admin['admin_id'];

if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
    json_response(['error' => 'CSV file is required (field name: file)'], 400);
}

$tmpName = $_FILES['file']['tmp_name'] ?? '';
if (!is_string($tmpName) || $tmpName === '' || !file_exists($tmpName)) {
    json_response(['error' => 'Upload failed'], 400);
}

$handle = fopen($tmpName, 'r');
if ($handle === false) {
    json_response(['error' => 'Failed to read uploaded file'], 400);
}

$header = fgetcsv($handle);
if ($header === false) {
    fclose($handle);
    json_response(['error' => 'CSV is empty'], 400);
}

$map = [];
foreach ($header as $i => $col) {
    $key = strtolower(trim((string)$col));
    $map[$key] = $i;
}

$required = ['full_name', 'index_number', 'department', 'level', 'password'];
foreach ($required as $r) {
    if (!array_key_exists($r, $map)) {
        fclose($handle);
        json_response(['error' => "Missing required CSV column: {$r}"], 400);
    }
}

$optional = ['email', 'phone'];

$created = 0;
$errors = [];
$rowNum = 1;

try {
    $pdo = db();
    $pdo->beginTransaction();

    while (($row = fgetcsv($handle)) !== false) {
        $rowNum++;

        $fullName = trim((string)($row[$map['full_name']] ?? ''));
        $indexNumber = trim((string)($row[$map['index_number']] ?? ''));
        $department = trim((string)($row[$map['department']] ?? ''));
        $level = trim((string)($row[$map['level']] ?? ''));
        $password = (string)($row[$map['password']] ?? '');

        $email = null;
        $phone = null;

        if (array_key_exists('email', $map)) {
            $email = trim((string)($row[$map['email']] ?? ''));
        }
        if (array_key_exists('phone', $map)) {
            $phone = trim((string)($row[$map['phone']] ?? ''));
        }

        if ($fullName === '' || $indexNumber === '' || $department === '' || $level === '' || $password === '') {
            $errors[] = ['row' => $rowNum, 'error' => 'Missing required fields'];
            continue;
        }

        if ($email !== null && $email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = ['row' => $rowNum, 'error' => 'Invalid email'];
            continue;
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare(
                'INSERT INTO students (full_name, index_number, department, level, email, phone, password)\n'
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $fullName,
                $indexNumber,
                $department,
                $level,
                ($email === '') ? null : $email,
                ($phone === '') ? null : $phone,
                $hash,
            ]);

            $studentId = (int)$pdo->lastInsertId();
            audit_log($pdo, $actorAdminId, 'create', 'students', (string)$studentId, null, [
                'student_id' => $studentId,
                'full_name' => $fullName,
                'index_number' => $indexNumber,
                'department' => $department,
                'level' => $level,
                'email' => ($email === '') ? null : $email,
                'phone' => ($phone === '') ? null : $phone,
            ]);

            $created++;
        } catch (Throwable $e) {
            $errors[] = ['row' => $rowNum, 'error' => 'Insert failed (duplicate index/email or DB error)'];
            continue;
        }
    }

    fclose($handle);

    $pdo->commit();

    json_response([
        'ok' => true,
        'created' => $created,
        'failed' => count($errors),
        'errors' => $errors,
    ]);
} catch (Throwable $e) {
    fclose($handle);
    if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    json_response(['error' => 'Bulk upload failed'], 500);
}
