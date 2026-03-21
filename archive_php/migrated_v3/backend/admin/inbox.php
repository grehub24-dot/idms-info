<?php
require_once '../includes/db.php';
require_once '../includes/ImapHelper.php';

// Ensure Admin Access
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

// Check if IMAP extension is installed
$imap_installed = function_exists('imap_open');

$imap = null;
$emails = [];
$error = '';
$total_emails = 0;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 15;

if ($imap_installed) {
    $imap = new ImapHelper();

    if ($imap->isConnected()) {
        // Handle actions like delete or mark as read
        if (isset($_GET['action']) && isset($_GET['id'])) {
            $id = intval($_GET['id']);
            if ($_GET['action'] == 'read') {
                $imap->markAsRead($id);
                flash('inbox_message', 'Email marked as read.');
                redirect('inbox.php?page=' . $page);
            } elseif ($_GET['action'] == 'delete') {
                $imap->deleteEmail($id);
                flash('inbox_message', 'Email deleted.');
                redirect('inbox.php?page=' . $page);
            }
        }

        $total_emails = $imap->getEmailCount();
        $emails = $imap->getEmails($limit, $page);
    } else {
        $error = "Could not connect to Gmail IMAP server. Please check your credentials in includes/ImapHelper.php. Error: " . $imap->getError();
    }
} else {
    $error = "PHP IMAP extension is not installed or enabled. Please enable it in your php.ini.";
}

$total_pages = ceil($total_emails / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Inbox - INFOTESS SDMS</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .email-list {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .email-item {
            display: flex;
            padding: 15px 20px;
            border-bottom: 1px solid #eee;
            align-items: center;
            transition: background 0.2s;
            cursor: pointer;
            text-decoration: none;
            color: inherit;
        }
        .email-item:hover {
            background: #f9f9f9;
        }
        .email-item.unread {
            font-weight: bold;
            background: #f0f7ff;
        }
        .email-sender {
            width: 25%;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-right: 15px;
        }
        .email-subject {
            flex-grow: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            padding-right: 15px;
        }
        .email-date {
            width: 15%;
            text-align: right;
            color: #666;
            font-size: 0.9em;
        }
        .email-actions {
            width: 10%;
            text-align: right;
        }
        .email-actions a {
            color: #666;
            margin-left: 10px;
        }
        .email-actions a:hover {
            color: #d9534f;
        }
        .email-body-preview {
            color: #888;
            font-weight: normal;
            font-size: 0.9em;
        }
        .pagination {
            display: flex;
            justify-content: center;
            padding: 20px;
            gap: 10px;
        }
        .pagination a {
            padding: 8px 12px;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        .pagination a.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        /* Modal for viewing email */
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
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 70%;
            max-width: 800px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            max-height: 80vh;
            overflow-y: auto;
        }
        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-modal:hover {
            color: black;
        }
        .modal-header {
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .modal-body {
            line-height: 1.6;
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
                <li><a href="messaging.php"><i class="fas fa-envelope"></i> Messaging</a></li>
                <li><a href="inbox.php" class="active"><i class="fas fa-inbox"></i> Inbox</a></li>
                <li><a href="module_settings.php"><i class="fas fa-cogs"></i> Module Settings</a></li>
                <li><a href="settings.php"><i class="fas fa-tools"></i> System Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h2>Email Inbox</h2>
                <div class="user-info">
                    <span>Welcome, Admin</span>
                </div>
            </div>

            <div class="section">
                <?php flash('inbox_message'); ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        <p class="mt-2" style="font-size: 0.9em;">
                            <strong>Note:</strong> To use this feature, ensure you have set your Gmail credentials in <code>includes/ImapHelper.php</code> and that your server has the PHP IMAP extension enabled.
                        </p>
                    </div>
                <?php else: ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <p>Showing <?php echo count($emails); ?> of <?php echo $total_emails; ?> emails.</p>
                        <button onclick="location.reload()" class="btn btn-primary"><i class="fas fa-sync-alt"></i> Refresh</button>
                    </div>

                    <div class="email-list">
                        <?php if (empty($emails)): ?>
                            <div style="padding: 20px; text-align: center; color: #666;">
                                No emails found in your inbox.
                            </div>
                        <?php else: ?>
                            <?php foreach ($emails as $email): ?>
                                <div class="email-item <?php echo $email['seen'] ? '' : 'unread'; ?>" onclick="openEmail(<?php echo htmlspecialchars(json_encode($email)); ?>)">
                                    <div class="email-sender">
                                        <?php echo htmlspecialchars(strip_tags($email['from'])); ?>
                                    </div>
                                    <div class="email-subject">
                                        <?php echo htmlspecialchars($email['subject']); ?>
                                        <span class="email-body-preview"> - <?php echo htmlspecialchars(substr(strip_tags($email['body']), 0, 50)); ?>...</span>
                                    </div>
                                    <div class="email-date">
                                        <?php echo date('M j, Y g:i A', strtotime($email['date'])); ?>
                                    </div>
                                    <div class="email-actions" onclick="event.stopPropagation();">
                                        <?php if (!$email['seen']): ?>
                                            <a href="inbox.php?action=read&id=<?php echo $email['id']; ?>&page=<?php echo $page; ?>" title="Mark as Read"><i class="fas fa-envelope-open"></i></a>
                                        <?php endif; ?>
                                        <a href="inbox.php?action=delete&id=<?php echo $email['id']; ?>&page=<?php echo $page; ?>" title="Delete" onclick="return confirm('Are you sure you want to delete this email?');"><i class="fas fa-trash"></i></a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="inbox.php?page=<?php echo $page - 1; ?>">&laquo; Previous</a>
                            <?php endif; ?>
                            
                            <?php
                            $start = max(1, $page - 2);
                            $end = min($total_pages, $page + 2);
                            for ($i = $start; $i <= $end; $i++): 
                            ?>
                                <a href="inbox.php?page=<?php echo $i; ?>" class="<?php echo $i == $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="inbox.php?page=<?php echo $page + 1; ?>">Next &raquo;</a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Email View Modal -->
    <div id="emailModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeEmail()">&times;</span>
            <div class="modal-header">
                <h3 id="modalSubject" style="margin-bottom: 10px;">Subject</h3>
                <div style="display: flex; justify-content: space-between; color: #666; font-size: 0.9em;">
                    <div><strong>From:</strong> <span id="modalFrom">Sender</span></div>
                    <div id="modalDate">Date</div>
                </div>
            </div>
            <div class="modal-body" id="modalBody">
                Body content goes here...
            </div>
            <div style="margin-top: 20px; text-align: right;">
                <a href="#" id="replyBtn" class="btn btn-primary"><i class="fas fa-reply"></i> Reply in Gmail</a>
            </div>
        </div>
    </div>

    <script>
        function openEmail(emailData) {
            document.getElementById('modalSubject').textContent = emailData.subject;
            document.getElementById('modalFrom').textContent = emailData.from.replace(/<[^>]*>?/gm, ''); // Basic strip tags for safety
            
            let dateObj = new Date(emailData.date);
            document.getElementById('modalDate').textContent = dateObj.toLocaleString();
            
            // Render body. We put it in an iframe or just div if we trust it, but innerHTML is okay for now since we strip scripts
            let safeBody = emailData.body.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '');
            document.getElementById('modalBody').innerHTML = safeBody;
            
            // Set reply link to open Gmail in a new tab
            document.getElementById('replyBtn').href = 'https://mail.google.com/mail/u/0/#inbox';
            document.getElementById('replyBtn').target = '_blank';
            
            document.getElementById('emailModal').style.display = 'block';

            // Optional: If email is unseen, we could trigger an AJAX call here to mark it as read without reloading
        }

        function closeEmail() {
            document.getElementById('emailModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            let modal = document.getElementById('emailModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>