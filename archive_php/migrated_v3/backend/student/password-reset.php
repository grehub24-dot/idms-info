<?php
require_once '../includes/db.php';
require_once '../includes/header.php';
require_once '../includes/SMSHelper.php';

// Check if logged in and is student
if (!isLoggedIn() || $_SESSION['role'] !== 'student') {
    redirect('../login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if (empty($new_password) || empty($confirm_password)) {
        $error = "All fields are required.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        $user_id = $_SESSION['user_id'];
        $hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        try {
            // Update password and set is_password_reset to 1
            $stmt = $pdo->prepare("UPDATE users SET password = :password, is_password_reset = 1 WHERE id = :id");
            $stmt->execute([
                'password' => $hash,
                'id' => $user_id
            ]);

            // Fetch student phone number for SMS
            $stmt_s = $pdo->prepare("SELECT phone_number, full_name FROM students WHERE user_id = :uid");
            $stmt_s->execute(['uid' => $user_id]);
            $student = $stmt_s->fetch();

            if ($student && $student['phone_number']) {
                $sms = new SMSHelper();
                $message = "Hello " . $student['full_name'] . ", your INFOTESS SDMS password has been successfully reset. You can now use your new password to login. Thank you.";
                $sms->send($student['phone_number'], $message);
            }
            
            $_SESSION['is_password_reset'] = 1;

            $success = "Password reset successfully! Redirecting to profile setup...";
            echo "<script>
                setTimeout(function() {
                    window.location.href = 'profile.php';
                }, 3000);
            </script>";
        } catch (PDOException $e) {
            $error = "An error occurred. Please try again later.";
        }
    }
}
?>

<div class="section" style="padding: 60px 20px; min-height: 60vh; display: flex; align-items: center; justify-content: center; background-color: #f9f9f9;">
    <div class="form-container" style="max-width: 500px; width: 100%; margin: 0 auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">
        <h2 class="section-title" style="text-align: center; margin-bottom: 10px; color: #333;">Reset Your Password</h2>
        <p style="text-align: center; margin-bottom: 30px; color: #666; font-size: 14px;">You are using a temporal password. Please set a new password to continue.</p>
        
        <?php if ($error): ?>
            <div class="alert alert-danger" style="background-color: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 14px;"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success" style="background-color: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin-bottom: 20px; font-size: 14px;"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <form action="password-reset.php" method="POST">
            <div class="form-group" style="margin-bottom: 20px;">
                <label for="new_password" style="display: block; margin-bottom: 8px; font-weight: 600; color: #444;">New Password</label>
                <input type="password" name="new_password" id="new_password" class="form-control" required placeholder="Enter New Password" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
            </div>
            
            <div class="form-group" style="margin-bottom: 25px;">
                <label for="confirm_password" style="display: block; margin-bottom: 8px; font-weight: 600; color: #444;">Confirm Password</label>
                <input type="password" name="confirm_password" id="confirm_password" class="form-control" required placeholder="Confirm New Password" style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 4px; font-size: 14px;">
            </div>
            
            <button type="submit" class="btn-submit" style="width: 100%; padding: 14px; background-color: var(--primary-color, #003366); color: white; border: none; border-radius: 4px; font-size: 16px; font-weight: bold; cursor: pointer; transition: background 0.3s;">Reset Password</button>
        </form>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
