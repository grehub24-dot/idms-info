<?php
require_once '../includes/db.php';
require_once '../includes/SMSHelper.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $title = sanitize($_POST['title'] ?? '');
    $content = sanitize($_POST['content'] ?? '');
    $send_sms = isset($_POST['send_sms']);

    if ($action === 'broadcast') {
        try {
            // Send In-App Notification (Broadcast to all students)
            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, title, content, is_broadcast) VALUES (?, ?, ?, 1)");
            $stmt->execute([$_SESSION['user_id'], $title, $content]);

            // Optional SMS (Caution: costly for large databases)
            if ($send_sms) {
                $sms = new SMSHelper();
                $students = $pdo->query("SELECT phone_number FROM students WHERE phone_number IS NOT NULL AND phone_number != ''")->fetchAll();
                foreach ($students as $student) {
                    $sms->send($student['phone_number'], "$title: $content");
                }
            }
            $message = "Broadcast message sent successfully!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    } elseif ($action === 'delete_message') {
        $msg_id = intval($_POST['message_id']);
        $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
        if ($stmt->execute([$msg_id])) {
            $message = "Message deleted successfully.";
        } else {
            $error = "Failed to delete message.";
        }
    } elseif ($action === 'send_sms_only') {
        try {
            $sms_content = sanitize($_POST['sms_content'] ?? '');
            $recipient_type = $_POST['recipient_type'] ?? 'all';
            $student_id = $_POST['student_id'] ?? null;

            if (empty($sms_content)) {
                $error = "SMS content cannot be empty.";
            } else {
                $sms = new SMSHelper();
                $count = 0;

                if ($recipient_type === 'all') {
                    $students = $pdo->query("SELECT phone_number FROM students WHERE phone_number IS NOT NULL AND phone_number != ''")->fetchAll();
                    foreach ($students as $student) {
                        if ($sms->send($student['phone_number'], $sms_content)) {
                            $count++;
                        }
                    }
                    // Log the bulk SMS as a broadcast message
                    $stmt = $pdo->prepare("INSERT INTO messages (sender_id, title, content, is_broadcast) VALUES (?, ?, ?, 1)");
                    $stmt->execute([$_SESSION['user_id'], "Bulk SMS", $sms_content]);
                    
                    $message = "SMS sent successfully to all ($count) students!";
                } else {
                    $stmt = $pdo->prepare("SELECT s.phone_number, s.full_name, s.user_id FROM students s WHERE s.id = ?");
                    $stmt->execute([$student_id]);
                    $student = $stmt->fetch();
                    if ($student && !empty($student['phone_number'])) {
                        if ($sms->send($student['phone_number'], $sms_content)) {
                            // Log the individual SMS
                            $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, title, content, is_broadcast) VALUES (?, ?, ?, ?, 0)");
                            $stmt->execute([$_SESSION['user_id'], $student['user_id'], "Individual SMS", $sms_content]);
                            
                            $message = "SMS sent successfully to " . htmlspecialchars($student['full_name']) . "!";
                        } else {
                            $error = "Failed to send SMS to " . htmlspecialchars($student['full_name']) . ".";
                        }
                    } else {
                        $error = "Selected student has no valid phone number.";
                    }
                }
            }
        } catch (Exception $e) {
            $error = "Error sending SMS: " . $e->getMessage();
        }
    }
}

// Fetch all students for the dropdown
$all_students = $pdo->query("SELECT id, full_name, index_number FROM students ORDER BY full_name ASC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messaging - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fff;
            margin: 10% auto;
            padding: 25px;
            border-radius: 8px;
            width: 500px;
            position: relative;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
        }
        .close-btn {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #888;
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
                <li><a href="payments.php"><i class="fas fa-money-bill-wave"></i> Payments</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
                <li><a href="verify.php"><i class="fas fa-qrcode"></i> Verify Receipt</a></li>
                <li><a href="users.php"><i class="fas fa-users-cog"></i> User Management</a></li>
                <li><a href="messaging.php" class="active"><i class="fas fa-envelope"></i> Messaging</a></li>
                <li><a href="inbox.php"><i class="fas fa-inbox"></i> Inbox</a></li>
                <li><a href="module_settings.php"><i class="fas fa-cogs"></i> Module Settings</a></li>
                <li><a href="settings.php"><i class="fas fa-tools"></i> System Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h2>Message Platform</h2>
                <div style="display:flex; gap:10px;">
                    <button onclick="document.getElementById('msgModal').style.display='block'" class="btn-primary"><i class="fas fa-paper-plane"></i> New Broadcast</button>
                    <button onclick="document.getElementById('smsModal').style.display='block'" class="btn-primary" style="background:#28a745; border-color:#28a745;"><i class="fas fa-sms"></i> Send SMS</button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="section">
                <div class="card">
                    <h3>Messages from Students</h3>
                    <?php
                    $student_msgs = $pdo->query("
                        SELECT m.*, s.full_name as sender_name, s.index_number 
                        FROM messages m 
                        JOIN students s ON m.sender_id = s.user_id 
                        WHERE m.is_broadcast = 0 AND m.receiver_id IN (SELECT id FROM users WHERE role = 'admin')
                        ORDER BY m.created_at DESC 
                        LIMIT 15
                    ")->fetchAll();
                    
                    if (empty($student_msgs)):
                    ?>
                        <p style="text-align:center; padding: 20px;">No messages from students yet.</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Student</th>
                                    <th>Subject</th>
                                    <th>Content</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($student_msgs as $msg): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($msg['sender_name']); ?></strong><br>
                                            <small><?php echo htmlspecialchars($msg['index_number']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars($msg['title']); ?></td>
                                        <td><?php echo htmlspecialchars($msg['content']); ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this message?');" style="display:inline;">
                                                <input type="hidden" name="action" value="delete_message">
                                                <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                <button type="submit" class="btn-login" style="background:#dc3545; padding: 5px 10px; font-size: 0.8rem;"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>

            <div class="section">
                <div class="card">
                    <h3>Message History</h3>
                    <?php
                    $all_msgs = $pdo->query("
                        SELECT m.*, s.full_name as recipient_name 
                        FROM messages m 
                        LEFT JOIN students s ON m.receiver_id = s.user_id 
                        ORDER BY m.created_at DESC 
                        LIMIT 20
                    ")->fetchAll();
                    
                    if (empty($all_msgs)):
                    ?>
                        <p style="text-align:center; padding: 20px;">No messages sent yet.</p>
                    <?php else: ?>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Category</th>
                                    <th>Recipient</th>
                                    <th>Subject</th>
                                    <th>Content</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_msgs as $msg): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($msg['created_at'])); ?></td>
                                        <td>
                                            <span class="badge" style="background: <?php echo $msg['is_broadcast'] ? '#003366' : '#28a745'; ?>; color: #fff; padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">
                                                <?php echo $msg['is_broadcast'] ? 'Bulk / Broadcast' : 'Individual'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $msg['is_broadcast'] ? '<em>All Students</em>' : htmlspecialchars($msg['recipient_name'] ?? 'Unknown'); ?></td>
                                        <td><strong><?php echo htmlspecialchars($msg['title']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($msg['content']); ?></td>
                                        <td>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this message?');" style="display:inline;">
                                                <input type="hidden" name="action" value="delete_message">
                                                <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                                <button type="submit" class="btn-login" style="background:#dc3545; padding: 5px 10px; font-size: 0.8rem;"><i class="fas fa-trash"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Message Modal -->
    <div id="msgModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('msgModal').style.display='none'">&times;</span>
            <h3>Send Broadcast Message</h3>
            <form method="POST" action="" style="margin-top: 20px;">
                <input type="hidden" name="action" value="broadcast">
                <div class="form-group">
                    <label>Title / Subject</label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Urgent Notice" required>
                </div>
                <div class="form-group">
                    <label>Message Content</label>
                    <textarea name="content" class="form-control" rows="5" placeholder="Type your message here..." required></textarea>
                </div>
                <div class="form-group">
                    <label><input type="checkbox" name="send_sms"> Also Send SMS (Caution: Costs apply)</label>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px;">Send to All Students</button>
            </form>
        </div>
    </div>

    <!-- SMS Only Modal -->
    <div id="smsModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('smsModal').style.display='none'">&times;</span>
            <h3>Send SMS</h3>
            <p style="font-size: 0.9rem; color: #666; margin-bottom: 15px;">Send a direct SMS message to students.</p>
            <form method="POST" action="" style="margin-top: 10px;">
                <input type="hidden" name="action" value="send_sms_only">
                
                <div class="form-group">
                    <label>Recipient Type</label>
                    <select name="recipient_type" id="recipientType" class="form-control" onchange="toggleStudentSelect()">
                        <option value="all">All Registered Students</option>
                        <option value="individual">Individual Student</option>
                    </select>
                </div>

                <div id="individualStudentSelect" class="form-group" style="display: none;">
                    <label>Select Student</label>
                    <select name="student_id" class="form-control">
                        <option value="">-- Search and Select Student --</option>
                        <?php foreach ($all_students as $student): ?>
                            <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['full_name']) . " (" . $student['index_number'] . ")"; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>SMS Content</label>
                    <textarea name="sms_content" class="form-control" rows="5" placeholder="Type your SMS message here..." required maxlength="160"></textarea>
                    <small id="charCount" style="color: #666;">Characters remaining: 160</small>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px; background:#28a745; border-color:#28a745;"><i class="fas fa-sms"></i> Send SMS Now</button>
            </form>
        </div>
    </div>

    <script>
        // Toggle individual student select dropdown
        function toggleStudentSelect() {
            const recipientType = document.getElementById('recipientType').value;
            const studentSelect = document.getElementById('individualStudentSelect');
            studentSelect.style.display = recipientType === 'individual' ? 'block' : 'none';
            
            const studentIdSelect = studentSelect.querySelector('select');
            if (recipientType === 'individual') {
                studentIdSelect.setAttribute('required', 'required');
            } else {
                studentIdSelect.removeAttribute('required');
            }
        }

        // SMS character counter
        const smsTextArea = document.querySelector('textarea[name="sms_content"]');
        const charCount = document.getElementById('charCount');
        
        smsTextArea.addEventListener('input', () => {
            const remaining = 160 - smsTextArea.value.length;
            charCount.textContent = `Characters remaining: ${remaining}`;
            if (remaining < 0) {
                charCount.style.color = 'red';
            } else {
                charCount.style.color = '#666';
            }
        });

        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = "none";
            }
        }
    </script>
</body>
</html>

