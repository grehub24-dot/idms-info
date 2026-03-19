<?php
require_once '../includes/db.php';

if (!isLoggedIn() || !isStudent()) {
    redirect('../login.php');
}

enforcePasswordReset();

$student_id = $_SESSION['student_id'];

// Fetch Student Data
$stmt = $pdo->prepare("SELECT * FROM students WHERE id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Fetch Payments
$stmt = $pdo->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC");
$stmt->execute([$student_id]);
$payments = $stmt->fetchAll();

// Calculate Total Paid
$total_paid = 0;
foreach ($payments as $p) {
    $total_paid += $p['amount'];
}

// Fetch system settings for dues
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings WHERE setting_key IN ('current_academic_year', 'annual_dues_amount')");
$settings = [];
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Outstanding balance for current academic year dues
$current_year = $settings['current_academic_year'] ?? '2025/2026';
$required_dues = (float)($settings['annual_dues_amount'] ?? 100.00);

$stmt = $pdo->prepare("SELECT COALESCE(SUM(amount),0) FROM payments WHERE student_id = ? AND academic_year = ?");
$stmt->execute([$student_id, $current_year]);
$paid_this_year = (float)$stmt->fetchColumn();
$outstanding = max(0, $required_dues - $paid_this_year);
$status_color = $outstanding <= 0 ? 'green' : 'red';
$status_text = $outstanding <= 0 ? 'Fully Paid' : 'Unpaid';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Student Dashboard - INFOTESS</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h3>My Portal</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages 
                    <?php
                    $stmt = $pdo->prepare("
                        SELECT COUNT(*) FROM messages m 
                        WHERE (m.is_broadcast = 1 OR m.receiver_id = ?) 
                        AND NOT EXISTS (
                            SELECT 1 FROM message_reads mr 
                            WHERE mr.message_id = m.id AND mr.user_id = ?
                        )
                    ");
                    $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                    $msg_count = $stmt->fetchColumn();
                    if ($msg_count > 0):
                    ?>
                        <span class="badge" style="background:#dc3545; color:white; padding:2px 6px; border-radius:50%; font-size:0.7rem;"><?php echo $msg_count; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="history.php"><i class="fas fa-history"></i> Payment History</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <img src="../<?php echo !empty($student['profile_picture']) ? htmlspecialchars($student['profile_picture']) : 'images/user-placeholder.png'; ?>" alt="Profile" style="width: 60px; height: 60px; border-radius: 50%; object-fit: cover; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                    <div>
                        <h2>Welcome, <?php echo htmlspecialchars($student['full_name']); ?></h2>
                        <div style="color: #666;"><?php echo htmlspecialchars($student['index_number']); ?></div>
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="stat-cards">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-wallet"></i>
                    </div>
                    <div class="stat-details">
                        <h3>GHS <?php echo number_format($total_paid, 2); ?></h3>
                        <p>Total Contribution</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-file-invoice"></i>
                    </div>
                    <div class="stat-details">
                        <h3><?php echo count($payments); ?></h3>
                        <p>Receipts Generated</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                    <div class="stat-details">
                        <h3>GHS <?php echo number_format($outstanding, 2); ?></h3>
                        <p>Outstanding (<?php echo htmlspecialchars($current_year); ?>)</p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-check-circle" style="color: <?php echo $status_color; ?>;"></i>
                    </div>
                    <div class="stat-details">
                        <h3 style="color: <?php echo $status_color; ?>;"><?php echo $status_text; ?></h3>
                        <p>Status (<?php echo htmlspecialchars($current_year); ?>)</p>
                    </div>
                </div>
            </div>

            <!-- Notifications Section -->
            <div class="section" style="margin-bottom: 30px;">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <h3>Recent Notifications</h3>
                    <a href="messages.php" style="font-size: 0.9rem; color: var(--primary-color);">View all messages</a>
                </div>
                <?php
                // Fetch last 3 broadcast or direct messages
                $stmt = $pdo->prepare("
                    SELECT * FROM messages 
                    WHERE is_broadcast = 1 OR receiver_id = ? 
                    ORDER BY created_at DESC LIMIT 3
                ");
                $stmt->execute([$_SESSION['user_id']]);
                $recent_msgs = $stmt->fetchAll();

                if (empty($recent_msgs)):
                ?>
                    <div class="card" style="padding: 15px; color: #666;">No new notifications.</div>
                <?php else: ?>
                    <?php foreach ($recent_msgs as $msg): ?>
                        <div class="card" style="padding: 15px; margin-bottom: 10px; border-left: 4px solid <?php echo $msg['is_broadcast'] ? '#003366' : 'var(--primary-color)'; ?>;">
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <strong><?php echo htmlspecialchars($msg['title']); ?></strong>
                                <small style="color: #888;"><?php echo date('M d, H:i', strtotime($msg['created_at'])); ?></small>
                            </div>
                            <p style="margin-top: 5px; font-size: 0.95rem; color: #444;">
                                <?php echo htmlspecialchars(substr($msg['content'], 0, 120)) . (strlen($msg['content']) > 120 ? '...' : ''); ?>
                            </p>
                            <a href="messages.php" style="font-size: 0.85rem; color: var(--secondary-color); font-weight: bold; margin-top: 5px; display: inline-block;">Read Full Message &rarr;</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Recent Payments -->
            <div class="section">
                <h3>My Payment History</h3>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Receipt #</th>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Semester</th>
                                <th>Status</th>
                                <th>Download</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($payment['receipt_number']); ?></td>
                                <td><?php echo htmlspecialchars($payment['payment_date']); ?></td>
                                <td>GHS <?php echo number_format($payment['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($payment['academic_year'] . ' - Sem ' . $payment['semester']); ?></td>
                                <td><span style="color:green; font-weight:bold;">Paid</span></td>
                                <td>
                                    <!-- In real app, this links to the PDF file -->
                                    <a href="../receipts/receipt_<?php echo $payment['receipt_number']; ?>.html" target="_blank" class="btn-login" style="padding: 5px 10px;">View Receipt</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (count($payments) === 0): ?>
                            <tr>
                                <td colspan="6" style="text-align:center;">No payment records found.</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
