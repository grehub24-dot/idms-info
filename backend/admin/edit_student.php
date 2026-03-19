<?php
require_once '../includes/db.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$id = $_GET['id'] ?? null;
if (!$id) {
    redirect('students.php');
}

$message = '';
$error = '';

// Handle Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $index_number = sanitize($_POST['index_number']);
    $department = sanitize($_POST['department']);
    $level = sanitize($_POST['level']);
    $class_name = sanitize($_POST['class_name'] ?? '');
    $stream = sanitize($_POST['stream'] ?? '');
    $phone = sanitize($_POST['phone_number']);
    $email = sanitize($_POST['email']);

    // Handle Profile Picture
    $profile_picture = $_POST['current_picture'] ?? null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../images/profiles/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $filename = $index_number . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $filename)) {
            $profile_picture = 'images/profiles/' . $filename;
        }
    }

    try {
        $pdo->beginTransaction();

        // Update Student
        $stmt = $pdo->prepare("UPDATE students SET full_name = ?, index_number = ?, department = ?, level = ?, class_name = ?, stream = ?, phone_number = ?, profile_picture = ? WHERE id = ?");
        $stmt->execute([$full_name, $index_number, $department, $level, $class_name, $stream, $phone, $profile_picture, $id]);

        // Update User Email (if needed)
        // First get user_id
        $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id = ?");
        $stmt->execute([$id]);
        $user_id = $stmt->fetchColumn();

        if ($user_id) {
            $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
            $stmt->execute([$email, $user_id]);
        }

        $pdo->commit();
        $message = "Student details updated successfully.";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error updating student: " . $e->getMessage();
    }
}

// Fetch Student Data
$stmt = $pdo->prepare("
    SELECT s.*, u.email 
    FROM students s 
    LEFT JOIN users u ON s.user_id = u.id 
    WHERE s.id = ?
");
$stmt->execute([$id]);
$student = $stmt->fetch();

if (!$student) {
    redirect('students.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Student - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <li><a href="students.php" class="active"><i class="fas fa-user-graduate"></i> Students</a></li>
                <li><a href="payments.php"><i class="fas fa-money-bill-wave"></i> Payments</a></li>
                <li><a href="reports.php"><i class="fas fa-chart-bar"></i> Reports</a></li>
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
                <h2>Edit Student</h2>
                <a href="students.php" class="btn-secondary">Back to List</a>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <form action="" method="POST" enctype="multipart/form-data" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                    <input type="hidden" name="current_picture" value="<?php echo $student['profile_picture']; ?>">
                    
                    <div style="grid-column: span 2; text-align: center; margin-bottom: 10px;">
                        <img src="../<?php echo $student['profile_picture'] ?? 'images/user-placeholder.png'; ?>" alt="Current Profile" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 2px solid #ddd; margin-bottom: 10px;">
                        <br>
                        <label>Update Profile Picture</label><br>
                        <input type="file" name="profile_picture" class="form-control" accept="image/*">
                    </div>

                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Index Number</label>
                        <input type="text" name="index_number" class="form-control" value="<?php echo htmlspecialchars($student['index_number']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($student['phone_number']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Programme / Department</label>
                        <select name="department" class="form-control" required>
                            <option value="">-- Select Programme --</option>
                            <optgroup label="Bachelor's Degree Programmes">
                                <option value="B.Sc. Information Technology" <?php echo $student['department'] === 'B.Sc. Information Technology' ? 'selected' : ''; ?>>B.Sc. Information Technology</option>
                                <option value="B.Sc. Cyber Security and Digital Forensics" <?php echo $student['department'] === 'B.Sc. Cyber Security and Digital Forensics' ? 'selected' : ''; ?>>B.Sc. Cyber Security and Digital Forensics</option>
                                <option value="B.Ed. Computing with Artificial Intelligence (AI)" <?php echo $student['department'] === 'B.Ed. Computing with Artificial Intelligence (AI)' ? 'selected' : ''; ?>>B.Ed. Computing with Artificial Intelligence (AI)</option>
                                <option value="B.Ed. Computing with Internet of Things (IOT)" <?php echo $student['department'] === 'B.Ed. Computing with Internet of Things (IOT)' ? 'selected' : ''; ?>>B.Ed. Computing with Internet of Things (IOT)</option>
                                <option value="B.Ed. Information Technology" <?php echo $student['department'] === 'B.Ed. Information Technology' ? 'selected' : ''; ?>>B.Ed. Information Technology</option>
                            </optgroup>
                            <optgroup label="Diploma Programmes">
                                <option value="Diploma in Cyber Security and Digital Forensics" <?php echo $student['department'] === 'Diploma in Cyber Security and Digital Forensics' ? 'selected' : ''; ?>>Diploma in Cyber Security and Digital Forensics</option>
                                <option value="Diploma in Information Technology" <?php echo $student['department'] === 'Diploma in Information Technology' ? 'selected' : ''; ?>>Diploma in Information Technology</option>
                            </optgroup>
                            <optgroup label="Postgraduate Programmes">
                                <option value="M. Phil. Information Technology" <?php echo $student['department'] === 'M. Phil. Information Technology' ? 'selected' : ''; ?>>M. Phil. Information Technology</option>
                                <option value="M. Sc. Information Technology Education" <?php echo $student['department'] === 'M. Sc. Information Technology Education' ? 'selected' : ''; ?>>M. Sc. Information Technology Education</option>
                                <option value="M. Phil Information Technology (Top-up)" <?php echo $student['department'] === 'M. Phil Information Technology (Top-up)' ? 'selected' : ''; ?>>M. Phil Information Technology (Top-up)</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Level</label>
                        <select name="level" class="form-control" required>
                            <option value="100" <?php echo $student['level'] === '100' ? 'selected' : ''; ?>>100</option>
                            <option value="200" <?php echo $student['level'] === '200' ? 'selected' : ''; ?>>200</option>
                            <option value="300" <?php echo $student['level'] === '300' ? 'selected' : ''; ?>>300</option>
                            <option value="400" <?php echo $student['level'] === '400' ? 'selected' : ''; ?>>400</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Class</label>
                        <select name="class_name" class="form-control">
                            <option value="">-- Select Class --</option>
                            <optgroup label="IT">
                                <?php foreach(['IT A', 'IT B', 'IT C', 'IT D', 'IT E', 'IT F', 'IT G', 'IT H'] as $c): ?>
                                    <option value="<?php echo $c; ?>" <?php echo ($student['class_name'] ?? '') === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="ITE">
                                <?php foreach(['ITE A', 'ITE B', 'ITE C', 'ITE D', 'ITE E', 'ITE F', 'ITE G', 'ITE H', 'ITE I', 'ITE J', 'ITE K'] as $c): ?>
                                    <option value="<?php echo $c; ?>" <?php echo ($student['class_name'] ?? '') === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                            <optgroup label="CB">
                                <?php foreach(['CB A', 'CB B', 'CB C', 'CB D', 'CB E', 'CB F', 'CB G', 'CB H'] as $c): ?>
                                    <option value="<?php echo $c; ?>" <?php echo ($student['class_name'] ?? '') === $c ? 'selected' : ''; ?>><?php echo $c; ?></option>
                                <?php endforeach; ?>
                            </optgroup>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Stream</label>
                        <select name="stream" class="form-control">
                            <option value="">-- Select Stream --</option>
                            <?php foreach(['Regular', 'Sandwich', 'Evening'] as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo ($student['stream'] ?? '') === $s ? 'selected' : ''; ?>><?php echo $s; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div style="grid-column: span 2; margin-top: 20px;">
                        <button type="submit" class="btn-primary">Update Student Details</button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>
</html>
