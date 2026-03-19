<?php
require_once '../includes/db.php';

if (!isLoggedIn() || !isStudent()) {
    redirect('../login.php');
}

enforcePasswordReset();

$student_id = $_SESSION['student_id'];
$user_id = $_SESSION['user_id'];
$message_status = '';
$error_status = '';

// Handle Sending Message to Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'send_to_admin') {
    $subject = sanitize($_POST['subject']);
    $content = sanitize($_POST['content']);
    
    // Find an admin user to receive the message (or just send to all admins if preferred, but usually there's a system admin)
    // For simplicity, we'll find the first admin
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
    $admin_id = $stmt->fetchColumn();

    if ($admin_id) {
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, title, content, is_broadcast) VALUES (?, ?, ?, ?, 0)");
        if ($stmt->execute([$user_id, $admin_id, $subject, $content])) {
            $message_status = "Message sent to administrator successfully!";
        } else {
            $error_status = "Failed to send message.";
        }
    } else {
        $error_status = "No administrator found to receive the message.";
    }
}

// Fetch Broadcast Messages
$broadcasts = $pdo->query("SELECT * FROM messages WHERE is_broadcast = 1 ORDER BY created_at DESC LIMIT 10")->fetchAll();

// Fetch Direct Messages for this student
$direct_messages = $pdo->prepare("SELECT * FROM messages WHERE receiver_id = ? AND is_broadcast = 0 ORDER BY created_at DESC");
$direct_messages->execute([$user_id]);
$direct_messages = $direct_messages->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Messages - INFOTESS</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .msg-card {
            border-left: 4px solid var(--primary-color);
            margin-bottom: 15px;
            cursor: pointer;
            transition: background 0.2s;
        }
        .msg-card:hover {
            background: #f8f9fa;
        }
        .msg-card.broadcast {
            border-left-color: #003366;
        }
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
            <div class="sidebar-header">
                <h3>My Portal</h3>
            </div>
            <ul class="sidebar-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="profile.php"><i class="fas fa-user"></i> My Profile</a></li>
                <li><a href="messages.php" class="active"><i class="fas fa-envelope"></i> Messages 
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
                        <span id="sidebar-msg-badge" class="badge" style="background:#dc3545; color:white; padding:2px 6px; border-radius:50%; font-size:0.7rem;"><?php echo $msg_count; ?></span>
                    <?php endif; ?>
                </a></li>
                <li><a href="history.php"><i class="fas fa-history"></i> Payment History</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h2>Message Center</h2>
                <button onclick="document.getElementById('sendModal').style.display='block'" class="btn-primary"><i class="fas fa-paper-plane"></i> Contact Admin</button>
            </div>

            <?php if ($message_status): ?>
                <div class="alert alert-success"><?php echo $message_status; ?></div>
            <?php endif; ?>
            <?php if ($error_status): ?>
                <div class="alert alert-danger"><?php echo $error_status; ?></div>
            <?php endif; ?>

            <div class="section">
                <h3>Announcements & Broadcasts</h3>
                <?php if (empty($broadcasts)): ?>
                    <p>No announcements at this time.</p>
                <?php else: ?>
                    <?php foreach ($broadcasts as $msg): ?>
                        <div class="card msg-card broadcast" onclick="viewMessage(<?php echo $msg['id']; ?>, '<?php echo addslashes($msg['title']); ?>', '<?php echo addslashes($msg['content']); ?>', '<?php echo $msg['created_at']; ?>')">
                            <div style="display:flex; justify-content:space-between;">
                                <strong><?php echo htmlspecialchars($msg['title']); ?></strong>
                                <small><?php echo date('M d, Y', strtotime($msg['created_at'])); ?></small>
                            </div>
                            <p style="margin-top:5px; color:#666;"><?php echo htmlspecialchars(substr($msg['content'], 0, 100)) . '...'; ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="section" style="margin-top:30px;">
                <h3>Direct Messages</h3>
                <?php if (empty($direct_messages)): ?>
                    <p>No direct messages.</p>
                <?php else: ?>
                    <?php foreach ($direct_messages as $msg): ?>
                        <div class="card msg-card" onclick="viewMessage(<?php echo $msg['id']; ?>, '<?php echo addslashes($msg['title']); ?>', '<?php echo addslashes($msg['content']); ?>', '<?php echo $msg['created_at']; ?>')">
                            <div style="display:flex; justify-content:space-between;">
                                <strong><?php echo htmlspecialchars($msg['title']); ?></strong>
                                <small><?php echo date('M d, Y', strtotime($msg['created_at'])); ?></small>
                            </div>
                            <p style="margin-top:5px; color:#666;"><?php echo htmlspecialchars(substr($msg['content'], 0, 100)) . '...'; ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- View Message Modal -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('viewModal').style.display='none'">&times;</span>
            <h3 id="viewTitle"></h3>
            <p id="viewDate" style="font-size: 0.8rem; color: #888; margin-bottom: 15px;"></p>
            <div id="viewContent" style="line-height: 1.6; color: #333; margin-bottom: 20px;"></div>
            
            <div style="text-align: right; border-top: 1px solid #eee; padding-top: 15px;">
                <button type="button" class="btn-primary" id="replyBtn" onclick="replyToMessage()">
                    <i class="fas fa-reply"></i> Reply to Admin
                </button>
            </div>
        </div>
    </div>

    <!-- Send Message Modal -->
    <div id="sendModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('sendModal').style.display='none'">&times;</span>
            <h3>Send Message to Admin</h3>
            <form method="POST" action="" style="margin-top: 20px;">
                <input type="hidden" name="action" value="send_to_admin">
                <div class="form-group">
                    <label>Subject</label>
                    <input type="text" name="subject" id="replySubject" class="form-control" placeholder="e.g. Question about dues" required>
                </div>
                <div class="form-group">
                    <label>Message Content</label>
                    <textarea name="content" class="form-control" rows="5" placeholder="Type your message here..." required></textarea>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px;">Send Message</button>
            </form>
        </div>
    </div>

    <script>
        function viewMessage(id, title, content, date) {
            document.getElementById('viewTitle').innerText = title;
            document.getElementById('viewContent').innerText = content;
            document.getElementById('viewDate').innerText = "Received on: " + new Date(date).toLocaleString();
            
            // Store title for reply
            document.getElementById('replyBtn').setAttribute('data-title', title);
            
            document.getElementById('viewModal').style.display = 'block';

            // Mark as read via AJAX
            fetch('../api/student/mark_message_read.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ message_id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.action === 'marked_read') {
                    // Update the badge count on the sidebar dynamically
                    let badge = document.getElementById('sidebar-msg-badge');
                    if (badge) {
                        let count = parseInt(badge.innerText);
                        if (!isNaN(count) && count > 0) {
                            count--;
                            if (count === 0) {
                                badge.style.display = 'none';
                            } else {
                                badge.innerText = count;
                            }
                        }
                    }
                    // Find and remove visual "unread" styling from the card if we had any
                }
            })
            .catch(error => console.error('Error marking message as read:', error));
        }

        function replyToMessage() {
            var title = document.getElementById('replyBtn').getAttribute('data-title');
            document.getElementById('viewModal').style.display = 'none';
            document.getElementById('sendModal').style.display = 'block';
            document.getElementById('replySubject').value = "Re: " + title;
        }

        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = "none";
            }
        }
    </script>
</body>
</html>