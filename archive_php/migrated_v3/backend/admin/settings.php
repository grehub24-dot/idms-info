<?php
require_once '../includes/db.php';

// Ensure Admin Access
if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$message = '';
$error = '';

// Handle Settings Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    try {
        $pdo->beginTransaction();
        
        $settings = [
            'current_academic_year' => sanitize($_POST['current_academic_year']),
            'current_semester' => sanitize($_POST['current_semester']),
            'annual_dues_amount' => sanitize($_POST['annual_dues_amount']),
            'payment_modes' => sanitize($_POST['payment_modes']),
            'department_name' => sanitize($_POST['department_name']),
            'institution_name' => sanitize($_POST['institution_name'])
        ];

        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        
        foreach ($settings as $key => $value) {
            $stmt->execute([$key, $value]);
        }

        $pdo->commit();
        $message = "System settings updated successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating settings: " . $e->getMessage();
    }
}

// Fetch Current Settings
$settings = [];
$stmt = $pdo->query("SELECT setting_key, setting_value FROM system_settings");
while ($row = $stmt->fetch()) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Default values if not set
$defaults = [
    'current_academic_year' => '2025/2026',
    'current_semester' => '1',
    'annual_dues_amount' => '100.00',
    'payment_modes' => 'Cash,Mobile Money,Bank Transfer',
    'department_name' => 'Information Technology Education',
    'institution_name' => 'AAMUSTED'
];
$settings = array_merge($defaults, $settings);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - INFOTESS SDMS</title>
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
            margin: 5% auto;
            padding: 20px;
            border-radius: 8px;
            width: 50%;
            position: relative;
        }
        .close-btn {
            position: absolute;
            right: 15px;
            top: 10px;
            font-size: 24px;
            cursor: pointer;
            color: #888;
        }
        .settings-grid {
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px;
            margin-bottom: 20px;
        }
        .setting-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .setting-label {
            font-weight: bold;
            color: #555;
            display: block;
            margin-bottom: 5px;
        }
        .setting-value {
            font-size: 1.1em;
            color: #333;
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
                <li><a href="inbox.php"><i class="fas fa-inbox"></i> Inbox</a></li>
                <li><a href="module_settings.php"><i class="fas fa-cogs"></i> Module Settings</a></li>
                <li><a href="settings.php" class="active"><i class="fas fa-tools"></i> System Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="top-bar">
                <h2>System Configuration</h2>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="section">
                <div class="card">
                    <div class="card-header" style="display: flex; justify-content: space-between; align-items: center;">
                        <h3>General Settings</h3>
                        <button onclick="document.getElementById('settingsModal').style.display='block'" class="btn-primary"><i class="fas fa-edit"></i> Edit Configuration</button>
                    </div>
                    <div class="card-content">
                        <div class="settings-grid">
                            <div class="setting-item">
                                <span class="setting-label">Current Academic Year</span>
                                <span class="setting-value"><?php echo htmlspecialchars($settings['current_academic_year']); ?></span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Current Semester</span>
                                <span class="setting-value"><?php echo $settings['current_semester'] == '1' ? 'First Semester' : 'Second Semester'; ?></span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Annual Dues Amount</span>
                                <span class="setting-value">GHS <?php echo htmlspecialchars($settings['annual_dues_amount']); ?></span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Accepted Payment Modes</span>
                                <span class="setting-value"><?php echo htmlspecialchars($settings['payment_modes']); ?></span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Department Name</span>
                                <span class="setting-value"><?php echo htmlspecialchars($settings['department_name']); ?></span>
                            </div>
                            <div class="setting-item">
                                <span class="setting-label">Institution Name</span>
                                <span class="setting-value"><?php echo htmlspecialchars($settings['institution_name']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>

    <!-- Settings Modal -->
    <div id="settingsModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('settingsModal').style.display='none'">&times;</span>
            <h2 style="margin-bottom: 20px;">General Settings</h2>
            <form action="settings.php" method="POST">
                <input type="hidden" name="action" value="update_settings">
                
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="form-group">
                        <label>Current Academic Year</label>
                        <input type="text" name="current_academic_year" class="form-control" value="<?php echo htmlspecialchars($settings['current_academic_year']); ?>" required placeholder="e.g. 2025/2026">
                    </div>
                    <div class="form-group">
                        <label>Current Semester</label>
                        <select name="current_semester" class="form-control" required>
                            <option value="1" <?php echo $settings['current_semester'] == '1' ? 'selected' : ''; ?>>First Semester</option>
                            <option value="2" <?php echo $settings['current_semester'] == '2' ? 'selected' : ''; ?>>Second Semester</option>
                        </select>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="form-group">
                        <label>Annual Dues Amount (GHS)</label>
                        <input type="number" step="0.01" name="annual_dues_amount" class="form-control" value="<?php echo htmlspecialchars($settings['annual_dues_amount']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Accepted Payment Modes (Comma separated)</label>
                        <input type="text" name="payment_modes" class="form-control" value="<?php echo htmlspecialchars($settings['payment_modes']); ?>" required placeholder="e.g. Cash, Mobile Money, Bank Transfer">
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <div class="form-group">
                        <label>Department Name</label>
                        <input type="text" name="department_name" class="form-control" value="<?php echo htmlspecialchars($settings['department_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Institution Name</label>
                        <input type="text" name="institution_name" class="form-control" value="<?php echo htmlspecialchars($settings['institution_name']); ?>" required>
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn-primary" style="padding: 12px 25px;"><i class="fas fa-save"></i> Save Configuration</button>
                </div>
            </form>
        </div>
    </div>
            </div>

            <div class="section">
                <div class="card">
                    <div class="card-header">
                        <h3>Configuration Summary</h3>
                    </div>
                    <div class="card-content">
                        <p>These settings control various aspects of the SDMS, including the academic year displayed on receipts and the default dues amount for compliance tracking.</p>
                        <ul style="margin-top: 10px; padding-left: 20px;">
                            <li><strong>Academic Year:</strong> Used for tracking payments and compliance.</li>
                            <li><strong>Dues Amount:</strong> Used to calculate the compliance rate on the dashboard.</li>
                            <li><strong>Payment Modes:</strong> These appear as options when recording a new payment.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        // Close modal when clicking outside of it
        window.onclick = function(event) {
            var modal = document.getElementById('settingsModal');
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>

