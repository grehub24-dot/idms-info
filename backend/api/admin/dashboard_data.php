<?php

declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../db.php';

// Ensure Admin Access
$admin_data = require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

try {
    // Fetch Current Settings for Dynamic Display
    $settings = [];
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    $current_year = $settings['current_academic_year'] ?? '2025/2026';
    $required_dues = isset($settings['annual_dues_amount']) ? (float)$settings['annual_dues_amount'] : 100.00;

    // Fetch Stats
    // 1. Total Students
    $stmt = $pdo->query("SELECT COUNT(*) FROM students");
    $total_students = (int)$stmt->fetchColumn();

    // 2. Total Revenue
    $stmt = $pdo->query("SELECT SUM(amount) FROM payments");
    $total_revenue = (float)($stmt->fetchColumn() ?: 0);

    // 3. Payments Today
    $today = date('Y-m-d');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE payment_date = :today");
    $stmt->execute(['today' => $today]);
    $payments_today = (int)$stmt->fetchColumn();

    // 4. Compliance Rate
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM (
            SELECT student_id, SUM(amount) AS total
            FROM payments
            WHERE academic_year = :year
            GROUP BY student_id
            HAVING total >= :required
        ) t
    ");
    $stmt->execute(['year' => $current_year, 'required' => $required_dues]);
    $students_paid = (int)$stmt->fetchColumn();
    $compliance_rate = $total_students > 0 ? round(($students_paid / $total_students) * 100, 1) : 0;
    $outstanding_students = max(0, $total_students - $students_paid);
    
    // 5. Recent Payments
    $stmt = $pdo->prepare("
        SELECT p.*, s.full_name, s.index_number,
               (SELECT SUM(amount) FROM payments WHERE student_id = s.id AND academic_year = :year) as total_paid
        FROM payments p 
        JOIN students s ON p.student_id = s.id 
        ORDER BY p.created_at DESC 
        LIMIT 5
    ");
    $stmt->execute(['year' => $current_year]);
    $recent_payments = $stmt->fetchAll();

    // Calculate balance for each recent payment
    foreach ($recent_payments as &$payment) {
        $payment['balance'] = max(0, $required_dues - (float)$payment['total_paid']);
    }

    // 6. Monthly Revenue for Chart
    $stmt = $pdo->query("
        SELECT 
            DATE_FORMAT(payment_date, '%b %Y') as month_label,
            SUM(amount) as monthly_total,
            DATE_FORMAT(payment_date, '%Y-%m') as sort_order
        FROM payments 
        GROUP BY sort_order, month_label
        ORDER BY sort_order ASC
        LIMIT 12
    ");
    $monthly_revenue_data = $stmt->fetchAll();

    $chart_labels = [];
    $chart_data = [];
    foreach ($monthly_revenue_data as $row) {
        $chart_labels[] = $row['month_label'];
        $chart_data[] = (float)$row['monthly_total'];
    }

    // Fallback if no data
    if (empty($chart_labels)) {
        $chart_labels = [date('M Y')];
        $chart_data = [0];
    }

    json_response([
        'ok' => true,
        'stats' => [
            'total_students' => $total_students,
            'total_revenue' => $total_revenue,
            'payments_today' => $payments_today,
            'compliance_rate' => $compliance_rate,
            'outstanding_students' => $outstanding_students,
            'current_year' => $current_year
        ],
        'recent_payments' => $recent_payments,
        'chart' => [
            'labels' => $chart_labels,
            'data' => $chart_data
        ]
    ]);

} catch (PDOException $e) {
    json_response(['ok' => false, 'error' => 'Database error: ' . $e->getMessage()], 500);
}