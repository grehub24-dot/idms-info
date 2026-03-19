<?php
require_once '../includes/db.php';

if (!isLoggedIn() || !isStudent()) {
    redirect('../login.php');
}

enforcePasswordReset();

$student_id = $_SESSION['student_id'];
$message = '';
$error = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = sanitize($_POST['phone_number']);
    $email = sanitize($_POST['email']);
    
    // Handle Profile Picture Upload
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../images/profiles/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $file_name = $_SESSION['index_number'] . '_' . time() . '.' . $file_extension;
        $target_file = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
            $profile_picture = 'images/profiles/' . $file_name;
        }
    }

    // Update user email
    $stmt = $pdo->prepare("UPDATE users SET email = ? WHERE id = ?");
    try {
        $stmt->execute([$email, $_SESSION['user_id']]);
        // Update student phone and picture
        if ($profile_picture) {
            $stmt = $pdo->prepare("UPDATE students SET phone_number = ?, profile_picture = ? WHERE id = ?");
            $stmt->execute([$phone, $profile_picture, $student_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE students SET phone_number = ? WHERE id = ?");
            $stmt->execute([$phone, $student_id]);
        }
        $message = "Profile updated successfully.";
    } catch (PDOException $e) {
        $error = "Error updating profile: " . $e->getMessage();
    }
}

// Fetch Student Data
$stmt = $pdo->prepare("SELECT s.*, u.email FROM students s JOIN users u ON s.user_id = u.id WHERE s.id = ?");
$stmt->execute([$student_id]);
$student = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Profile - INFOTESS</title>
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
                <li><a href="profile.php" class="active"><i class="fas fa-user"></i> My Profile</a></li>
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
                <li><a href="history.php"><i class="fas fa-history"></i> Payment History</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h2>My Profile</h2>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card">
                <h3>Personal Information</h3>
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="form-group" style="text-align:center;">
                        <img src="../<?php echo $student['profile_picture'] ?? 'images/default-profile.png'; ?>" alt="Profile Picture" style="width:150px; height:150px; border-radius:50%; object-fit:cover; margin-bottom:10px;">
                        <br>
                        <label for="profile_picture" class="btn-login" style="cursor:pointer; display:inline-block;">Change Picture</label>
                        <input type="file" name="profile_picture" id="profile_picture" style="display:none;">
                    </div>
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['full_name']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Index Number</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['index_number']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['department']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Level</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['level']); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Class</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['class_name'] ?? ''); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Stream</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['stream'] ?? ''); ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($student['email']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($student['phone_number']); ?>">
                    </div>
                    <button type="submit" class="btn-primary">Update Profile</button>
                </form>
            </div>
        </main>
    </div>
</body>
</html>