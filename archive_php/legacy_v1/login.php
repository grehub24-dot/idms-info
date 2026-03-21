<?php
require_once 'includes/db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('admin/dashboard.php');
    } else {
        redirect('student/dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']); // Email or Index Number
    $password = $_POST['password'];

    if (empty($identifier) || empty($password)) {
        $error = "Please enter both identifier and password.";
    } else {
        // Check if it's an email (Admin) or Index Number (Student)
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            // Admin/Executive Login
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
            $stmt->execute(['email' => $identifier]);
            $user = $stmt->fetch();
        } else {
            // Student Login (Lookup user via students table)
            // First find student by index number to get user_id
            $stmt = $pdo->prepare("
                SELECT u.*, s.index_number 
                FROM users u 
                JOIN students s ON u.id = s.user_id 
                WHERE s.index_number = :index_number
            ");
            $stmt->execute(['index_number' => $identifier]);
            $user = $stmt->fetch();
        }

        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] !== 'active') {
                $error = "Your account is inactive or banned. Please contact support.";
            } else {
                // Login Success
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // If student, store student details in session
                if ($user['role'] === 'student') {
                    $stmt_s = $pdo->prepare("SELECT * FROM students WHERE user_id = :uid");
                    $stmt_s->execute(['uid' => $user['id']]);
                    $student = $stmt_s->fetch();
                    $_SESSION['student_id'] = $student['id'];
                    $_SESSION['index_number'] = $student['index_number'];
                    $_SESSION['name'] = $student['full_name'];
                    
                    // If password has NOT been reset (is temporal), redirect to reset page
                    // We check if the column exists or is 0. If it doesn't exist, we assume 0 (force reset).
                    $is_reset = isset($user['is_password_reset']) ? $user['is_password_reset'] : 0;
                    $_SESSION['is_password_reset'] = $is_reset;
                    
                    if ($is_reset == 0) {
                        redirect('student/password-reset.php');
                    }
                    
                    redirect('student/dashboard.php');
                } else {
                    $_SESSION['name'] = "Admin"; // Can fetch executive name if needed
                    redirect('admin/dashboard.php');
                }
            }
        } else {
            $error = "Invalid credentials. Please try again.";
        }
    }
}

require_once 'includes/header.php';
?>

<div class="section">
    <div class="form-container" style="text-align: center;">
        <img src="images/infotess.png" alt="INFOTESS Logo" style="width: 100px; margin-bottom: 20px;">
        <h2 class="section-title">Login to SDMS</h2>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form action="login.php" method="POST" style="text-align: left;">
            <div class="form-group">
                <label for="identifier">Email or Index Number</label>
                <input type="text" name="identifier" id="identifier" class="form-control" required placeholder="Enter Email (Admin) or Index No. (Student)">
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" class="form-control" required placeholder="Enter Password">
            </div>
            
            <button type="submit" class="btn-submit">Login</button>
            
            <div style="margin-top: 15px; text-align: center;">
                <a href="forgot-password.php" style="color: var(--primary-color);">Forgot Password?</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
