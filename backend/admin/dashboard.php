<?php
require_once '../includes/db.php';

// Ensure Admin Access
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

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
$total_students = $stmt->fetchColumn();

// 2. Total Revenue
$stmt = $pdo->query("SELECT SUM(amount) FROM payments");
$total_revenue = $stmt->fetchColumn() ?: 0;

// 3. Payments Today
$today = date('Y-m-d');
$stmt = $pdo->prepare("SELECT COUNT(*) FROM payments WHERE payment_date = :today");
$stmt->execute(['today' => $today]);
$payments_today = $stmt->fetchColumn();

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
$compliance_rate = $total_students > 0 ? round(($students_paid / (int)$total_students) * 100, 1) : 0;
$outstanding_students = max(0, (int)$total_students - $students_paid);
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - INFOTESS SDMS</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header" style="text-align: center; padding: 20px 10px;">
                <img src="../images/infotess.png" alt="INFOTESS Logo" style="width: 80px; height: 80px; margin-bottom: 10px; border-radius: 50%; background: #fff; padding: 5px;">
                <h3>INFOTESS Admin</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="payments.php"><i class="fas fa-money-bill-wave"></i> Payments</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="verify.php"><i class="fas fa-qrcode"></i> Verify Receipt</a></li>
                <li><a href="users.php"><i class="fas fa-users-cog"></i> User Management</a></li>
                <li><a href="messaging.php"><i class="fas fa-envelope"></i> Messaging</a></li>
                <li><a href="inbox.php"><i class="fas fa-inbox"></i> Inbox</a></li>
                <li><a href="module_settings.php"><i class="fas fa-cogs"></i> Module Settings</a></li>
                <li><a href="settings.php"><i class="fas fa-tools"></i> System Settings</a></li>
                <li><a href="../logout.html"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h2>Dashboard Overview</h2>
                <div class="user-info">
                    <span>Welcome, Admin</span>
                </div>
            </div>

            <!-- Stats -->
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($total_students); ?></h3>
                        <p>Total Students</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-details">
                        <h3>GHS <?php echo number_format($total_revenue, 2); ?></h3>
                        <p>Total Revenue</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-receipt"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $payments_today; ?></h3>
                        <p>Payments Today</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo $compliance_rate; ?>%</h3>
                        <p>Compliance (<?php echo htmlspecialchars($current_year); ?>)</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-clock"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo number_format($outstanding_students); ?></h3>
                        <p>Outstanding Students</p>
                    </div>
                </div>
            </div>

            <!-- Recent Payments Table -->
            <div class="section">
                <h3>Recent Payments</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Student</th>
                                <th>Amount (GHS)</th>
                                <th>Balance (GHS)</th>
                                <th>Date</th>
                                <th>Method</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_payments as $payment): 
                                $balance = max(0, $required_dues - (float)$payment['total_paid']);
                            ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['receipt_number']); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($payment['full_name']); ?><br>
                                    <small><?php echo htmlspecialchars($payment['index_number']); ?></small>
                                </td>
                                <td><?php echo number_format($payment['amount'], 2); ?></td>
                                <td>
                                    <span style="color: <?php echo $balance > 0 ? 'red' : 'green'; ?>; font-weight: bold;">
                                        <?php echo number_format($balance, 2); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                <td>
                                    <a href="../receipts/view.php?id=<?php echo $payment['id']; ?>" target="_blank" class="btn-login" style="padding: 5px 10px; font-size: 0.8rem;">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Charts Placeholder -->
            <div class="section">
                <h3>Revenue Analytics</h3>
                <canvas id="revenueChart" width="400" height="150"></canvas>
            </div>
        </main>
    </div>

    <script>
        // Real-time Revenue Analytics Chart
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const revenueChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Revenue (GHS)',
                    data: <?php echo json_encode($chart_data); ?>,
                    backgroundColor: 'rgba(0, 51, 102, 0.7)',
                    borderColor: 'rgba(0, 51, 102, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>

