<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../../includes/Mailer.php';
require_once __DIR__ . '/../../includes/SMSHelper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);

$full_name = trim($input['full_name'] ?? '');
$index_number = trim($input['index_number'] ?? '');
$department = trim($input['department'] ?? '');
$level = trim($input['level'] ?? '');
$class = trim($input['class'] ?? '');
$stream = trim($input['stream'] ?? '');
$email = trim($input['email'] ?? '');
$phone = trim($input['phone_number'] ?? '');

if (empty($full_name) || empty($index_number) || empty($email) || empty($phone)) {
    json_response(['error' => 'Please fill all required fields.'], 400);
}

$pdo = db();

// Check duplicate index
$stmt = $pdo->prepare("SELECT id FROM students WHERE index_number = ?");
$stmt->execute([$index_number]);
if ($stmt->fetch()) {
    json_response(['error' => "Student with Index Number $index_number already exists."], 400);
}

// Check email duplicate
$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) {
    json_response(['error' => "Email address already registered."], 400);
}

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
    try {
        $stmt = $pdo->prepare("INSERT INTO students (user_id, index_number, full_name, department, level, class_name, stream, phone_number) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $index_number, $full_name, $department, $level, $class, $stream, $phone]);
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), "Unknown column 'class_name'") !== false || strpos($e->getMessage(), "Unknown column 'stream'") !== false) {
            $stmt = $pdo->prepare("INSERT INTO students (user_id, index_number, full_name, department, level, phone_number) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $index_number, $full_name, $department, $level, $phone]);
        } else {
            throw $e;
        }
    }

    $pdo->commit();

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
                    <div class='info-row'>
                        <div class='info-label'>Temporary Password:</div>
                        <div class='info-value'>" . htmlspecialchars($auto_password, ENT_QUOTES, 'UTF-8') . "</div>
                    </div>
                </div>
                
                <div class='notes'>
                    <strong>Important Information:</strong>
                    <ul>
                        <li>Keep your index number safe - you'll need it for all transactions</li>
                        <li>Use your temporary password to login, then reset it immediately</li>
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
    
    $email_sent = $mailer->sendHTML($email, $subject, $email_html) ? true : false;

    // Send SMS
    $sms_sent = false;
    if ($phone) {
        $smsHelper = new SMSHelper();
        $smsMsg = "Welcome to INFOTESS! Reg successful. Username (Index Number): $index_number. Temporary Password: $auto_password. Please login and change it.";
        $sms_sent = $smsHelper->send($phone, $smsMsg);
    }

    $delivery = [];
    if ($email_sent) {
        $delivery[] = 'email';
    }
    if ($sms_sent) {
        $delivery[] = 'phone';
    }

    $message = 'Registration successful! Use your index number as username.';
    if (!empty($delivery)) {
        $message .= ' A temporary password has been sent to your ' . implode(' and ', $delivery) . '.';
    } else {
        $message .= ' Temporary password delivery failed. Please contact admin support.';
    }
    $message .= ' Please login and reset your password.';

    json_response([
        'ok' => true,
        'message' => $message,
        'delivery' => [
            'email_sent' => $email_sent,
            'sms_sent' => $sms_sent
        ]
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    json_response(['error' => "Error: " . $e->getMessage()], 500);
}
