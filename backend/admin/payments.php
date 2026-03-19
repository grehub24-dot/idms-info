<?php
require_once '../includes/db.php';
// In a real scenario, use require_once '../vendor/autoload.php';
// For now, we will simulate the Receipt Generation class
require_once '../includes/ReceiptGenerator.php'; 
require_once '../includes/SMSHelper.php';
require_once '../includes/Mailer.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Fetch Settings
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}
$current_academic_year = $settings['current_academic_year'] ?? date('Y') . '/' . (date('Y') + 1);
$current_semester = $settings['current_semester'] ?? '1';
$payment_modes = explode(',', $settings['payment_modes'] ?? 'Cash,Mobile Money,Bank Transfer');

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_payment') {
    $index_number = sanitize($_POST['index_number']);
    $level = isset($_POST['level']) ? sanitize($_POST['level']) : '';
    $class = isset($_POST['class']) ? sanitize($_POST['class']) : '';
    $stream = isset($_POST['stream']) ? sanitize($_POST['stream']) : '';
    $programme = isset($_POST['programme']) ? sanitize($_POST['programme']) : '';
    $amount = floatval($_POST['amount']);
    $year = sanitize($_POST['academic_year']);
    $semester = sanitize($_POST['semester']);
    $method = sanitize($_POST['payment_method']);
    $date = sanitize($_POST['payment_date']);

    // Find Student
    $stmt = $pdo->prepare("
        SELECT s.id, s.full_name, s.index_number, s.phone_number, u.email 
        FROM students s 
        LEFT JOIN users u ON s.user_id = u.id 
        WHERE s.index_number = ?
    ");
    $stmt->execute([$index_number]);
    $student = $stmt->fetch();

    if (!$student) {
        $error = "Student with Index Number $index_number not found.";
    } else {
        // We will allow multiple payments per semester to pay off the balance
        // So we just proceed with recording the payment
        try {
            $pdo->beginTransaction();
            
            // Generate Receipt Number: INFO + YEAR + MONTH + RANDOM
            // Example: INFO-2603-7482
            $receipt_number = "INFO-" . date('ym') . "-" . rand(1000, 9999);

            // Insert Payment
            $stmt = $pdo->prepare("INSERT INTO payments (student_id, amount, academic_year, semester, payment_method, payment_date, receipt_number, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student['id'], $amount, $year, $semester, $method, $date, $receipt_number, $_SESSION['user_id']]);
            $payment_id = $pdo->lastInsertId();

            // Generate Receipt PDF (Simulation)
            $generator = new ReceiptGenerator();
            
            // Fetch student total paid for the year to calculate balance
            $stmt_paid = $pdo->prepare("SELECT SUM(amount) FROM payments WHERE student_id = ? AND academic_year = ?");
            $stmt_paid->execute([$student['id'], $year]);
            $total_paid = (float)$stmt_paid->fetchColumn();
            
            // Fetch required dues from settings
            $stmt_settings = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'annual_dues_amount'");
            $settings_dues = $stmt_settings->fetchColumn();
            $required_dues = $settings_dues !== false ? (float)$settings_dues : 100.00;
            
            $current_balance = max(0, $required_dues - $total_paid);

            $receipt_path = $generator->generate($payment_id, $receipt_number, $student, $amount, $date, $level, $class, $programme, $current_balance, $year, $semester, $method, $stream);
            
            // Save Receipt Record
            $hash = md5($receipt_number . $payment_id . 'SALT'); // Simple hash
            $stmt = $pdo->prepare("INSERT INTO receipts (payment_id, receipt_file_path, verification_hash) VALUES (?, ?, ?)");
            $stmt->execute([$payment_id, $receipt_path, $hash]);

            $pdo->commit();
            $message = "Payment recorded and receipt generated successfully. Receipt #: $receipt_number";
            
            // Send SMS notification
            if (!empty($student['phone_number'])) {
                $sms = new SMSHelper();
                $sms_message = "Hello " . $student['full_name'] . ", your payment of GHS " . number_format($amount, 2) . " for " . $year . " " . $semester . " has been received. Receipt #: " . $receipt_number . ". Thank you.";
                $sms->send($student['phone_number'], $sms_message);
            }

            // Send Email with Receipt
            if (!empty($student['email'])) {
                $mailer = new Mailer();
                
                // Create custom email template matching the provided image
                $email_html = "
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset='UTF-8'>
                    <style>
                        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                        .email-container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                        .header { background-color: #1a9e65; color: white; text-align: center; padding: 40px 20px; }
                        .header h1 { margin: 0; font-size: 28px; }
                        .header p { margin: 10px 0 0 0; font-size: 14px; }
                        .content { padding: 30px; color: #333; }
                        .receipt-box { border: 1px solid #1a9e65; border-radius: 8px; padding: 20px; margin-top: 20px; }
                        .receipt-title { text-align: center; color: #1a9e65; margin-bottom: 20px; }
                        .receipt-title h2 { margin: 0; font-size: 20px; }
                        .receipt-title p { margin: 5px 0 0 0; color: #555; font-size: 14px; }
                        .receipt-row { padding: 12px 0; border-bottom: 1px solid #eee; display: flex; justify-content: space-between; }
                        .receipt-row:last-child { border-bottom: none; }
                        .amount-box { background-color: #1a9e65; color: white; text-align: center; padding: 20px; border-radius: 8px; margin-top: 20px; }
                        .amount-box p { margin: 0 0 5px 0; font-size: 14px; }
                        .amount-box h2 { margin: 0; font-size: 28px; }
                        .paid-badge { background-color: #1a9e65; color: white; padding: 5px 15px; border-radius: 15px; display: inline-block; margin-top: 15px; font-weight: bold; font-size: 14px; }
                        .notes { margin-top: 30px; font-size: 12px; color: #333; }
                        .notes ul { padding-left: 20px; }
                        .footer { text-align: center; padding: 30px; font-size: 12px; color: #666; border-top: 1px solid #eee; }
                        .footer a { color: #0056b3; text-decoration: none; }
                    </style>
                </head>
                <body>
                    <div class='email-container'>
                        <div class='header'>
                            <h1>✓ Payment Received!</h1>
                            <p>AAMUSTED - Infotess Dues Payment Confirmation</p>
                        </div>
                        <div class='content'>
                            <p>Dear <strong>{$student['full_name']}</strong>,</p>
                            <p>Your payment has been successfully received and recorded in our system.</p>
                            
                            <div class='receipt-box'>
                                <div class='receipt-title'>
                                    <h2>OFFICIAL RECEIPT</h2>
                                    <p>Receipt No: $receipt_number</p>
                                </div>
                                
                                <div class='receipt-row'>
                                    <span style='color: #666;'>Student Name:</span>
                                    <strong>{$student['full_name']}</strong>
                                </div>
                                <div class='receipt-row'>
                                    <span style='color: #666;'>Index Number:</span>
                                    <strong>{$student['index_number']}</strong>
                                </div>
                                <div class='receipt-row'>
                                    <span style='color: #666;'>Level:</span>
                                    <strong>Level " . (!empty($level) ? htmlspecialchars($level) : '100') . "</strong>
                                </div>
                                <div class='receipt-row'>
                                    <span style='color: #666;'>Class:</span>
                                    <strong>Class " . (!empty($class) ? htmlspecialchars($class) : 'E') . "</strong>
                                </div>
                                <div class='receipt-row'>
                                    <span style='color: #666;'>Stream:</span>
                                    <strong>" . (!empty($stream) ? htmlspecialchars($stream) : 'Regular') . "</strong>
                                </div>
                                <div class='receipt-row'>
                                    <span style='color: #666;'>Academic Year:</span>
                                    <strong>$year</strong>
                                </div>
                                <div class='receipt-row'>
                                    <span style='color: #666;'>Semester:</span>
                                    <strong>$semester</strong>
                                </div>
                                <div class='receipt-row'>
                                    <span style='color: #666;'>Payment Method:</span>
                                    <strong>$method</strong>
                                </div>
                                <div class='receipt-row'>
                                    <span style='color: #666;'>Payment Date:</span>
                                    <strong>$date</strong>
                                </div>
                                
                                <div class='amount-box'>
                                    <p>Amount Paid</p>
                                    <h2>GH₵ " . number_format($amount, 2) . "</h2>
                                </div>
                                
                                <div style='text-align: center; margin-top: 20px;'>
                                    <img src='https://api.qrserver.com/v1/create-qr-code/?size=100x100&data=" . urlencode("http://localhost/Infotess/verify_public.php?receipt=" . $receipt_number) . "' alt='QR Code' style='width: 100px; height: 100px; margin-bottom: 10px;' />
                                    <br>
                                    <div class='paid-badge'>✓ PAID</div>
                                </div>
                            </div>
                            
                            <div class='notes'>
                                <strong>Important Notes:</strong>
                                <ul>
                                    <li>Keep this email for your records</li>
                                    <li>This receipt is valid for graduation clearance</li>
                                    <li>You can access this receipt anytime from the system</li>
                                    <li>Receipt Number: <strong>$receipt_number</strong></li>
                                </ul>
                                <p>Thank you for your prompt payment!</p>
                            </div>
                        </div>
                        
                        <div class='footer'>
                            <p><strong>AAMUSTED - Infotess - Finance Office</strong></p>
                            <p><a href='http://usted.edu.gh'>usted.edu.gh</a>, Kumasi, Ghana</p>
                            <p>Phone: +233 24 091 8031</p>
                            <p style='color: #999; margin-top: 20px;'>This is an automated email. Please do not reply to this message.<br>For inquiries, contact the finance office directly.</p>
                        </div>
                    </div>
                </body>
                </html>
                ";

                $mailer->sendHTML($student['email'], "Payment Receipt - " . $receipt_number, $email_html);
            }

        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Record Payment - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modal Styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); 
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; 
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
            max-width: 600px;
            border-radius: 8px;
            position: relative;
        }
        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-btn:hover,
        .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
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
                <li><a href="payments.php" class="active"><i class="fas fa-money-bill-wave"></i> Payments</a></li>
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

        <main class="main-content">
            <div class="top-bar">
                <h2>Record Payment</h2>
                <button id="openModalBtn" class="btn-primary" style="padding: 10px 20px;"><i class="fas fa-plus"></i> Record New Payment</button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Payment Records List -->
            <div class="section">
                <h3>Recent Payments</h3>
                <?php
                // Fetch required dues from settings
                $stmt_settings = $pdo->query("SELECT setting_value FROM system_settings WHERE setting_key = 'annual_dues_amount'");
                $settings_dues = $stmt_settings->fetchColumn();
                $required_dues = $settings_dues !== false ? (float)$settings_dues : 100.00;
                
                // Pagination settings
                $limit = 10;
                $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                if ($page < 1) $page = 1;
                $offset = ($page - 1) * $limit;

                // Fetch recent payments for display with pagination
                $stmt = $pdo->prepare("
                    SELECT SQL_CALC_FOUND_ROWS p.*, s.full_name, s.index_number,
                           (SELECT SUM(amount) FROM payments WHERE student_id = s.id AND academic_year = :year_sub) as total_paid
                    FROM payments p 
                    JOIN students s ON p.student_id = s.id 
                    ORDER BY p.created_at DESC 
                    LIMIT $limit OFFSET $offset
                ");
                $stmt->execute(['year_sub' => $current_academic_year]);
                $recent_payments = $stmt->fetchAll();

                $total_stmt = $pdo->query("SELECT FOUND_ROWS()");
                $total_rows = (int)$total_stmt->fetchColumn();
                $total_pages = ceil($total_rows / $limit);
                ?>
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
                                    <a href="../receipts/receipt_<?php echo htmlspecialchars($payment['receipt_number']); ?>.html" target="_blank" class="btn-login" style="padding: 5px 10px; font-size: 0.8rem;">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; margin-top: 20px; gap: 5px;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?>" class="btn-login" style="background: #f8f9fa; color: #333; border: 1px solid #ddd;">&laquo; Prev</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="btn-login" style="<?php echo $i == $page ? 'background: var(--primary-color);' : 'background: #f8f9fa; color: #333; border: 1px solid #ddd;'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>" class="btn-login" style="background: #f8f9fa; color: #333; border: 1px solid #ddd;">Next &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Record Payment Modal -->
            <div id="paymentModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn">&times;</span>
                    <h3>Record Payment</h3>
                    <form action="payments.php" method="POST" class="card-content" style="margin-top: 15px;">
                        <input type="hidden" name="action" value="record_payment">
                        
                        <div class="form-group">
                            <label>Student Index Number</label>
                            <input type="text" name="index_number" class="form-control" required placeholder="e.g. 5231230001">
                        </div>

                        <div class="form-group">
                            <label>Programme / Department</label>
                            <select name="programme" class="form-control" required>
                                <option value="">-- Select Programme --</option>
                                <optgroup label="Bachelor's Degree Programmes">
                                    <option value="B.Sc. Information Technology">B.Sc. Information Technology</option>
                                    <option value="B.Sc. Cyber Security and Digital Forensics">B.Sc. Cyber Security and Digital Forensics</option>
                                    <option value="B.Ed. Computing with Artificial Intelligence (AI)">B.Ed. Computing with Artificial Intelligence (AI)</option>
                                    <option value="B.Ed. Computing with Internet of Things (IOT)">B.Ed. Computing with Internet of Things (IOT)</option>
                                    <option value="B.Ed. Information Technology">B.Ed. Information Technology</option>
                                </optgroup>
                                <optgroup label="Diploma Programmes">
                                    <option value="Diploma in Cyber Security and Digital Forensics">Diploma in Cyber Security and Digital Forensics</option>
                                    <option value="Diploma in Information Technology">Diploma in Information Technology</option>
                                </optgroup>
                                <optgroup label="Postgraduate Programmes">
                                    <option value="M. Phil. Information Technology">M. Phil. Information Technology</option>
                                    <option value="M. Sc. Information Technology Education">M. Sc. Information Technology Education</option>
                                    <option value="M. Phil Information Technology (Top-up)">M. Phil Information Technology (Top-up)</option>
                                </optgroup>
                            </select>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr 1fr; gap:15px;">
                            <div class="form-group">
                                <label>Level</label>
                                <input type="text" name="level" class="form-control" required placeholder="e.g. 300">
                            </div>
                            <div class="form-group">
                                <label>Class</label>
                                <select name="class" class="form-control" required>
                                    <option value="">-- Select Class --</option>
                                    <optgroup label="IT">
                                        <option value="IT A">IT A</option>
                                        <option value="IT B">IT B</option>
                                        <option value="IT C">IT C</option>
                                        <option value="IT D">IT D</option>
                                        <option value="IT E">IT E</option>
                                        <option value="IT F">IT F</option>
                                        <option value="IT G">IT G</option>
                                        <option value="IT H">IT H</option>
                                    </optgroup>
                                    <optgroup label="ITE">
                                        <option value="ITE A">ITE A</option>
                                        <option value="ITE B">ITE B</option>
                                        <option value="ITE C">ITE C</option>
                                        <option value="ITE D">ITE D</option>
                                        <option value="ITE E">ITE E</option>
                                        <option value="ITE F">ITE F</option>
                                        <option value="ITE G">ITE G</option>
                                        <option value="ITE H">ITE H</option>
                                        <option value="ITE I">ITE I</option>
                                        <option value="ITE J">ITE J</option>
                                        <option value="ITE K">ITE K</option>
                                    </optgroup>
                                    <optgroup label="CB">
                                        <option value="CB A">CB A</option>
                                        <option value="CB B">CB B</option>
                                        <option value="CB C">CB C</option>
                                        <option value="CB D">CB D</option>
                                        <option value="CB E">CB E</option>
                                        <option value="CB F">CB F</option>
                                        <option value="CB G">CB G</option>
                                        <option value="CB H">CB H</option>
                                    </optgroup>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Stream</label>
                                <select name="stream" class="form-control" required>
                                    <option value="">-- Select Stream --</option>
                                    <option value="Regular">Regular</option>
                                    <option value="Sandwich">Sandwich</option>
                                    <option value="Evening">Evening</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Amount (GHS)</label>
                            <input type="number" step="0.01" name="amount" class="form-control" required>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                            <div class="form-group">
                                <label>Academic Year</label>
                                <input type="text" name="academic_year" class="form-control" value="<?php echo htmlspecialchars($current_academic_year); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Semester</label>
                                <select name="semester" class="form-control" required>
                                    <option value="1" <?php echo $current_semester == '1' ? 'selected' : ''; ?>>First Semester</option>
                                    <option value="2" <?php echo $current_semester == '2' ? 'selected' : ''; ?>>Second Semester</option>
                                </select>
                            </div>
                        </div>

                        <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                            <div class="form-group">
                                <label>Payment Method</label>
                                <select name="payment_method" class="form-control" required>
                                    <?php foreach ($payment_modes as $mode): ?>
                                        <option value="<?php echo htmlspecialchars(trim($mode)); ?>"><?php echo htmlspecialchars(trim($mode)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Payment Date</label>
                                <input type="date" name="payment_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <button type="submit" class="btn-submit" style="margin-top: 10px;">Record Payment & Generate Receipt</button>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        // Modal Logic
        const modal = document.getElementById("paymentModal");
        const btn = document.getElementById("openModalBtn");
        const span = document.getElementsByClassName("close-btn")[0];

        // When the user clicks the button, open the modal 
        btn.onclick = function() {
            modal.style.display = "block";
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        // Auto-fill Student Details
        document.querySelector('input[name="index_number"]').addEventListener('blur', function() {
            const indexNumber = this.value.trim();
            if (indexNumber.length > 0) {
                fetch(`../api/admin/get_student_by_index.php?index_number=${encodeURIComponent(indexNumber)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.ok) {
                            const student = data.student;
                            
                            // Fill Programme / Department
                            const programmeSelect = document.querySelector('select[name="programme"]');
                            if (programmeSelect && student.department) {
                                // Try to find a matching option
                                let found = false;
                                for (let i = 0; i < programmeSelect.options.length; i++) {
                                    if (programmeSelect.options[i].value === student.department) {
                                        programmeSelect.selectedIndex = i;
                                        found = true;
                                        break;
                                    }
                                }
                                // If not found, maybe just set the value (if it allows custom values or just select the first one?)
                                // Or maybe add it as a new option?
                                if (!found) {
                                    // Create a new option and select it
                                    const option = document.createElement('option');
                                    option.value = student.department;
                                    option.text = student.department;
                                    programmeSelect.add(option);
                                    programmeSelect.value = student.department;
                                }
                            }

                            // Fill Level
                            const levelInput = document.querySelector('input[name="level"]');
                            if (levelInput && student.level) {
                                levelInput.value = student.level;
                            }

                            // Fill Class
                            const classSelect = document.querySelector('select[name="class"]');
                            if (classSelect && student.class_name) {
                                classSelect.value = student.class_name;
                            }

                            // Fill Stream
                            const streamSelect = document.querySelector('select[name="stream"]');
                            if (streamSelect && student.stream) {
                                streamSelect.value = student.stream;
                            }
                        } else {
                            // Clear fields if student not found? Or just leave them?
                            // Maybe show a small message?
                            console.log('Student not found');
                        }
                    })
                    .catch(error => console.error('Error fetching student:', error));
            }
        });
    </script>
</body>
</html>

