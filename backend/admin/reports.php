<?php
require_once '../includes/db.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Generate Report Logic
$report_type = $_GET['type'] ?? '';
$data = [];
$headers = [];

if ($report_type) {
    if ($report_type === 'payments_per_dept') {
        $stmt = $pdo->query("
            SELECT s.department, COUNT(p.id) as payment_count, SUM(p.amount) as total_amount 
            FROM payments p 
            JOIN students s ON p.student_id = s.id 
            GROUP BY s.department
        ");
        $data = $stmt->fetchAll();
        $headers = ['Department', 'Payment Count', 'Total Amount'];
    } elseif ($report_type === 'payments_per_year') {
        $stmt = $pdo->query("
            SELECT academic_year, COUNT(id) as payment_count, SUM(amount) as total_amount 
            FROM payments 
            GROUP BY academic_year
        ");
        $data = $stmt->fetchAll();
        $headers = ['Academic Year', 'Payment Count', 'Total Amount'];
    } elseif ($report_type === 'payments_per_semester') {
        $stmt = $pdo->query("
            SELECT semester, COUNT(id) as payment_count, SUM(amount) as total_amount 
            FROM payments 
            GROUP BY semester
        ");
        $data = $stmt->fetchAll();
        $headers = ['Semester', 'Payment Count', 'Total Amount'];
    }
}

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv' && $data) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="report_' . $report_type . '_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reports - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="students.php"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="payments.php"><i class="fas fa-money-bill-wave"></i> Payments</a></li>
                <li><a href="reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="verify.php"><i class="fas fa-qrcode"></i> Verify Receipt</a></li>
                <li><a href="users.php"><i class="fas fa-users-cog"></i> User Management</a></li>
                <li><a href="messaging.php"><i class="fas fa-envelope"></i> Messaging</a></li>
                <li><a href="inbox.php"><i class="fas fa-inbox"></i> Inbox</a></li>
                <li><a href="module_settings.php"><i class="fas fa-cogs"></i> Module Settings</a></li>
                <li><a href="settings.php"><i class="fas fa-tools"></i> System Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h2>System Reports</h2>
            </div>

            <div class="report-filters card">
                <h3>Generate Report</h3>
                <form method="GET" action="">
                    <select name="type" required style="padding: 10px; margin-right: 10px;">
                        <option value="">Select Report Type</option>
                        <option value="payments_per_dept" <?php echo $report_type == 'payments_per_dept' ? 'selected' : ''; ?>>Payments per Department</option>
                        <option value="payments_per_year" <?php echo $report_type == 'payments_per_year' ? 'selected' : ''; ?>>Payments per Academic Year</option>
                        <option value="payments_per_semester" <?php echo $report_type == 'payments_per_semester' ? 'selected' : ''; ?>>Payments per Semester</option>
                    </select>
                    <button type="submit" class="btn-primary">View Report</button>
                </form>
            </div>

            <?php if ($data): ?>
                <div class="card" style="margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h3>Report Results</h3>
                        <div>
                            <a href="?type=<?php echo $report_type; ?>&export=csv" class="btn-primary" style="background: #28a745;">Export CSV</a>
                            <button onclick="window.print()" class="btn-primary" style="background: #17a2b8;">Print PDF</button>
                        </div>
                    </div>
                    
                    <table class="table" style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f4f4f4; text-align: left;">
                                <?php foreach ($headers as $header): ?>
                                    <th style="padding: 10px; border-bottom: 2px solid #ddd;"><?php echo $header; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data as $row): ?>
                                <tr>
                                    <?php foreach ($row as $cell): ?>
                                        <td style="padding: 10px; border-bottom: 1px solid #ddd;"><?php echo htmlspecialchars($cell); ?></td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Chart -->
                <div class="card" style="margin-top: 20px;">
                    <canvas id="reportChart"></canvas>
                </div>
                
                <script>
                    const ctx = document.getElementById('reportChart').getContext('2d');
                    const data = <?php echo json_encode($data); ?>;
                    const labels = data.map(item => Object.values(item)[0]);
                    const values = data.map(item => Object.values(item)[2]); // Total Amount

                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: labels,
                            datasets: [{
                                label: 'Total Amount (GHS)',
                                data: values,
                                backgroundColor: 'rgba(0, 51, 102, 0.6)',
                                borderColor: 'rgba(0, 51, 102, 1)',
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                </script>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
