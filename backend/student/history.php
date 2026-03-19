<?php
require_once '../includes/db.php';

if (!isLoggedIn() || !isStudent()) {
    redirect('../login.php');
}

enforcePasswordReset();

$student_id = $_SESSION['student_id'];

// Fetch Payments
$stmt = $pdo->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC");
$stmt->execute([$student_id]);
$payments = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payment History - INFOTESS</title>
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
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="messages.php"><i class="fas fa-envelope"></i> Messages 
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE is_broadcast = 1 OR receiver_id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    $msg_count = $stmt->fetchColumn();
                    if ($msg_count > 0):
                    ?>
                        <span class="badge" style="background:#dc3545; color:white; padding:2px 6px; border-radius:50%; font-size:0.7rem;"><?php echo $msg_count; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="history.php" class="active"><i class="fas fa-history"></i> Payment History</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h2>Payment History</h2>
            </div>

            <div class="card">
                <h3>My Transactions</h3>
                <table class="table" style="width: 100%;">
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
                                <!-- Mock Download Link -->
                                <a href="../receipts/receipt_<?php echo $payment['receipt_number']; ?>.html" target="_blank" class="btn-sm btn-secondary"><i class="fas fa-download"></i> Receipt</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php if (empty($payments)): ?>
                            <tr><td colspan="6">No payments found.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>