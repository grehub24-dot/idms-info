<?php
require_once '../includes/db.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$receipt_number = $_GET['receipt'] ?? '';
$payment = null;
$history = [];

if ($receipt_number) {
    // Fetch specific payment
    $stmt = $pdo->prepare("SELECT p.*, s.full_name, s.index_number, s.department, s.level FROM payments p JOIN students s ON p.student_id = s.id WHERE p.receipt_number = ?");
    $stmt->execute([$receipt_number]);
    $payment = $stmt->fetch();

    if ($payment) {
        // Fetch full history for this student
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY payment_date DESC");
        $stmt->execute([$payment['student_id']]);
        $history = $stmt->fetchAll();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Verify Receipt - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
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
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="verify.php" class="active"><i class="fas fa-qrcode"></i> Verify Receipt</a></li>
                <li><a href="users.php"><i class="fas fa-users-cog"></i> User Management</a></li>
                <li><a href="messaging.php"><i class="fas fa-envelope"></i> Messaging</a></li>
                <li><a href="inbox.php"><i class="fas fa-inbox"></i> Inbox</a></li>
                <li><a href="module_settings.php"><i class="fas fa-cogs"></i> Module Settings</a></li>
                <li><a href="settings.php"><i class="fas fa-tools"></i> System Settings</a></li>
                <li><a href="../logout.html"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h2>Verify Receipt</h2>
            </div>

            <div class="card">
                <h3>Enter Receipt Number or Scan QR</h3>
                <form method="GET" action="" style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <input type="text" name="receipt" value="<?php echo htmlspecialchars($receipt_number); ?>" placeholder="SDMS-2026-XXXX" class="form-control" style="flex: 1;">
                    <button type="submit" class="btn-primary">Verify</button>
                </form>
                
                <div id="reader" style="width: 300px; display: none;"></div>
                <button onclick="startScanner()" class="btn-primary" style="background: #6c757d;">Start QR Scanner</button>
            </div>

            <?php if ($receipt_number): ?>
                <?php if ($payment): ?>
                    <div class="card success-card" style="margin-top: 20px; border-left: 5px solid green;">
                        <h3><i class="fas fa-check-circle" style="color: green;"></i> Valid Receipt</h3>
                        <div class="details-grid">
                            <p><strong>Student:</strong> <?php echo htmlspecialchars($payment['full_name']); ?> (<?php echo htmlspecialchars($payment['index_number']); ?>)</p>
                            <p><strong>Department:</strong> <?php echo htmlspecialchars($payment['department']); ?></p>
                            <p><strong>Amount:</strong> GHS <?php echo number_format($payment['amount'], 2); ?></p>
                            <p><strong>Date:</strong> <?php echo $payment['payment_date']; ?></p>
                            <p><strong>Purpose:</strong> <?php echo htmlspecialchars($payment['semester'] . ' ' . $payment['academic_year']); ?></p>
                        </div>
                    </div>

                    <div class="card" style="margin-top: 20px;">
                        <h3>Payment History for Student</h3>
                        <table class="table" style="width: 100%;">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Receipt #</th>
                                    <th>Amount</th>
                                    <th>Term</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $record): ?>
                                    <tr style="<?php echo $record['receipt_number'] === $receipt_number ? 'background: #e8f5e9;' : ''; ?>">
                                        <td><?php echo $record['payment_date']; ?></td>
                                        <td><?php echo $record['receipt_number']; ?></td>
                                        <td>GHS <?php echo number_format($record['amount'], 2); ?></td>
                                        <td><?php echo $record['semester'] . ' ' . $record['academic_year']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="card error-card" style="margin-top: 20px; border-left: 5px solid red;">
                        <h3><i class="fas fa-times-circle" style="color: red;"></i> Invalid Receipt</h3>
                        <p>No payment record found for receipt number: <strong><?php echo htmlspecialchars($receipt_number); ?></strong></p>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </main>
    </div>

    <script>
        function startScanner() {
            const html5QrCode = new Html5Qrcode("reader");
            document.getElementById('reader').style.display = 'block';
            html5QrCode.start(
                { facingMode: "environment" }, 
                { fps: 10, qrbox: { width: 250, height: 250 } },
                (decodedText, decodedResult) => {
                    // Handle on success condition with the decoded message.
                    console.log(`Scan result: ${decodedText}`);
                    // Extract receipt number from URL if it's a URL
                    let receipt = decodedText;
                    if (decodedText.includes('receipt=')) {
                        const url = new URL(decodedText);
                        receipt = url.searchParams.get('receipt');
                    }
                    window.location.href = `?receipt=${receipt}`;
                    html5QrCode.stop();
                },
                (errorMessage) => {
                    // parse error, ignore it.
                })
            .catch((err) => {
                // Start failed, handle it.
            });
        }
    </script>
</body>
</html>
