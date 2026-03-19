<?php
require_once 'includes/db.php';
require_once 'includes/Mailer.php';
require_once 'includes/SMSHelper.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'register') {
    $full_name = sanitize($_POST['full_name']);
    $index_number = sanitize($_POST['index_number']);
    $department = sanitize($_POST['department']);
    $level = sanitize($_POST['level']);
    $class = sanitize($_POST['class']);
    $stream = sanitize($_POST['stream']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone_number']);

    // Check duplicate
    $stmt = $pdo->prepare("SELECT id FROM students WHERE index_number = ?");
    $stmt->execute([$index_number]);
    if ($stmt->fetch()) {
        $error = "Student with Index Number $index_number already exists.";
    } else {
        // Check email duplicate
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = "Email address already registered.";
        } else {
                $pdo->beginTransaction();
                try {
                    // Generate a random 6-character password
                    $auto_password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 6);
                    
                    // 1. Create User Account
                    $password_hash = password_hash($auto_password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'student')");
                    $stmt->execute([$email, $password_hash]);
                    $user_id = $pdo->lastInsertId();

                    // 2. Create Student Record
                    // Try to insert with class_name and stream. If it fails (column missing), catch and insert without it.
                    try {
                        $stmt = $pdo->prepare("INSERT INTO students (user_id, index_number, full_name, department, level, class_name, stream, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$user_id, $index_number, $full_name, $department, $level, $class, $stream, $phone]);
                    } catch (PDOException $e) {
                        if (strpos($e->getMessage(), "Unknown column 'class_name'") !== false || strpos($e->getMessage(), "Unknown column 'stream'") !== false) {
                            // Column missing, fallback
                            $stmt = $pdo->prepare("INSERT INTO students (user_id, index_number, full_name, department, level, phone_number) VALUES (?, ?, ?, ?, ?, ?)");
                            $stmt->execute([$user_id, $index_number, $full_name, $department, $level, $phone]);
                        } else {
                            throw $e;
                        }
                    }

                    $pdo->commit();
                    $message = "Registration successful! Please login.";

                    // Send Email
                    $mailer = new Mailer();
                    $subject = "Welcome to AAMUSTED - Infotess!";
                    $dateStr = date('n/j/Y');
                    
                    $email_html = "
                    <!DOCTYPE html>
                    <html>
                    <head>
                        <meta charset='UTF-8'>
                        <style>
                            body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
                            .email-container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                            .header { background: linear-gradient(to right, #6b66d6, #7a3fa0); color: white; text-align: center; padding: 40px 20px; }
                            .header h1 { margin: 0; font-size: 26px; }
                            .header p { margin: 10px 0 0 0; font-size: 14px; }
                            .content { padding: 30px; color: #333; font-size: 14px; }
                            .info-box { border: 1px solid #eee; border-left: 4px solid #4a90e2; border-radius: 4px; padding: 0; margin-top: 20px; }
                            .info-title { color: #4a90e2; font-size: 16px; font-weight: bold; padding: 15px 20px; }
                            .info-row { padding: 12px 20px; border-top: 1px solid #eee; display: flex; justify-content: flex-start; }
                            .info-label { color: #333; font-weight: bold; width: 150px; }
                            .info-value { color: #555; }
                            .notes { margin-top: 30px; font-size: 13px; color: #333; }
                            .notes ul { padding-left: 20px; margin-top: 10px; }
                            .notes li { margin-bottom: 5px; }
                            .footer { text-align: center; padding: 30px; font-size: 12px; color: #666; border-top: 1px solid #eee; }
                            .footer a { color: #0056b3; text-decoration: none; }
                        </style>
                    </head>
                    <body>
                        <div class='email-container'>
                            <div class='header'>
                                <h1>Welcome to AAMUSTED - Infotess!</h1>
                                <p>Student Registration Successful</p>
                            </div>
                            <div class='content'>
                                <p>Dear <strong>" . htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                                <p>Congratulations! You have been successfully registered in our system. Below are your details:</p>
                                
                                <div class='info-box'>
                                    <div class='info-title'>Student Information</div>
                                    
                                    <div class='info-row'>
                                        <div class='info-label'>Full Name:</div>
                                        <div class='info-value'>" . htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') . "</div>
                                    </div>
                                    <div class='info-row'>
                                        <div class='info-label'>Index Number:</div>
                                        <div class='info-value'>" . htmlspecialchars($index_number, ENT_QUOTES, 'UTF-8') . "</div>
                                    </div>
                                    <div class='info-row'>
                                        <div class='info-label'>Level:</div>
                                        <div class='info-value'>Level " . htmlspecialchars($level, ENT_QUOTES, 'UTF-8') . "</div>
                                    </div>
                                    <div class='info-row'>
                                        <div class='info-label'>Class:</div>
                                        <div class='info-value'>Class " . htmlspecialchars($class ?? 'E', ENT_QUOTES, 'UTF-8') . "</div>
                                    </div>
                                    <div class='info-row'>
                                        <div class='info-label'>Department:</div>
                                        <div class='info-value'>" . htmlspecialchars($department, ENT_QUOTES, 'UTF-8') . "</div>
                                    </div>
                                    <div class='info-row'>
                                        <div class='info-label'>Email:</div>
                                        <div class='info-value'><a href='mailto:" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "'>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</a></div>
                                    </div>
                                    <div class='info-row'>
                                        <div class='info-label'>Phone:</div>
                                        <div class='info-value'>" . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . "</div>
                                    </div>
                                    <div class='info-row'>
                                        <div class='info-label'>Registration Date:</div>
                                        <div class='info-value'>" . $dateStr . "</div>
                                    </div>
                                </div>
                                
                                <div class='notes'>
                                    <strong>Important Information:</strong>
                                    <ul>
                                        <li>Keep your index number safe - you'll need it for all transactions</li>
                                        <li>All payment receipts will be sent to this email address</li>
                                        <li>Contact the finance office for any payment-related queries</li>
                                    </ul>
                                    <p style='margin-top: 15px;'>If you have any questions or notice any incorrect information, please contact the administration office immediately.</p>
                                </div>
                            </div>
                            
                            <div class='footer'>
                                <p><strong>AAMUSTED - Infotess</strong></p>
                                <p><a href='http://usted.edu.gh'>usted.edu.gh</a>, Kumasi, Ghana</p>
                                <p>Phone: +233 24 091 8031</p>
                                <p style='color: #999; margin-top: 20px;'>This is an automated email. Please do not reply to this message.</p>
                            </div>
                        </div>
                    </body>
                    </html>
                    ";
                    
                    $mailer->sendHTML($email, $subject, $email_html);

                    // Send SMS
                    if ($phone) {
                        $smsHelper = new SMSHelper();
                        $smsMsg = "Welcome to INFOTESS! Reg successful. Index: $index_number. Your password is: $auto_password. Please login and change it.";
                        $smsHelper->send($phone, $smsMsg);
                    }

                    // Redirect to login after 3 seconds
                    header("refresh:3;url=login.php");

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Error: " . $e->getMessage();
                }
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="container" style="padding: 40px 0; max-width: 800px; margin: 0 auto;">
    <div class="card">
        <div class="card-header" style="text-align: center; border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px;">
            <h2>Student Registration</h2>
            <p>Join INFOTESS to access exclusive benefits</p>
        </div>

        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
            <input type="hidden" name="action" value="register">
            
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Index Number</label>
                <input type="text" name="index_number" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone_number" class="form-control" required>
            </div>
            <div class="form-group" style="grid-column: span 2;">
                <label>Programme / Department</label>
                <select name="department" class="form-control" required>
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
            <div class="form-group">
                <label>Level</label>
                <select name="level" class="form-control" required>
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="300">300</option>
                    <option value="400">400</option>
                </select>
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
            
            <div style="grid-column: span 2; text-align: center; margin-top: 20px;">
                <button type="submit" class="btn-primary" style="width: 100%; max-width: 300px; padding: 15px;">Register Now</button>
            </div>
            
            <div style="grid-column: span 2; text-align: center; margin-top: 10px;">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>