<?php
require_once '../includes/db.php';
require_once '../includes/Mailer.php';
require_once '../includes/SMSHelper.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.html');
}

$message = '';
$error = '';

// Handle Student Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_student') {
    $full_name = sanitize($_POST['full_name']);
    $index_number = sanitize($_POST['index_number']);
    $department = sanitize($_POST['department']);
    $level = sanitize($_POST['level']);
    $class = sanitize($_POST['class'] ?? '');
    $stream = sanitize($_POST['stream'] ?? '');
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone_number']);
    
    // Handle Profile Picture
    $profile_picture = null;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../../images/profiles/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
        $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $filename = $index_number . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $filename)) {
            $profile_picture = 'images/profiles/' . $filename;
        }
    }

    // Check duplicate
    $stmt = $pdo->prepare("SELECT id FROM students WHERE index_number = ?");
    $stmt->execute([$index_number]);
    if ($stmt->fetch()) {
        $error = "Student with Index Number $index_number already exists.";
    } else {
        $pdo->beginTransaction();
        try {
            // 1. Create User Account
            $temporary_password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
            $password_hash = password_hash($temporary_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'student')");
            $stmt->execute([$email, $password_hash]);
            $user_id = $pdo->lastInsertId();

            // 2. Create Student Record
            $stmt = $pdo->prepare("INSERT INTO students (user_id, index_number, full_name, department, level, class_name, stream, phone_number, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $index_number, $full_name, $department, $level, $class, $stream, $phone, $profile_picture]);
            
            $student_id = $pdo->lastInsertId();

            $pdo->commit();
            $message = "Student registered successfully.";

            $sms_sent = false;
            if (!empty($phone)) {
                try {
                    $sms = new SMSHelper();
                    $smsMessage = "Hi " . $full_name . ", you have been registered successfully on INFOTESS. Username (Index Number): " . $index_number . ". Temporary Password: " . $temporary_password . ". Please login and change it.";
                    $sms_sent = $sms->send($phone, $smsMessage);
                } catch (Exception $e) {
                    // Do not fail registration if SMS fails
                }
            }

            if ($email) {
                $mailer = new Mailer();
                $subject = "Welcome to AAMUSTED - Infotess!";
                $dateStr = date('n/j/Y');
                $html = "<div style=\"font-family: Arial, sans-serif; max-width: 640px; margin: 0 auto; background: #ffffff; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;\">
                    <div style=\"background: linear-gradient(90deg,#4b6cb7,#182848); color:#fff; padding: 24px; text-align:center;\">
                        <div style=\"font-size: 20px; font-weight: 700;\">Welcome to AAMUSTED - Infotess!</div>
                        <div style=\"margin-top:8px; font-size:14px; opacity:0.9;\">Student Registration Successful</div>
                    </div>
                    <div style=\"padding: 24px; color:#111827;\">
                        <p>Dear <strong>" . htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                        <p>Congratulations! You have been successfully registered in our system. Below are your details:</p>
                        <div style=\"border:1px solid #e5e7eb; border-radius:8px; padding:16px; background:#f9fafb; margin-top:12px;\">
                            <div style=\"display:grid; grid-template-columns: 180px 1fr; gap:8px; font-size:14px;\">
                                <div><strong>Full Name:</strong></div><div>" . htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') . "</div>
                                <div><strong>Index Number:</strong></div><div>" . htmlspecialchars($index_number, ENT_QUOTES, 'UTF-8') . "</div>
                                <div><strong>Level:</strong></div><div>Level " . htmlspecialchars($level, ENT_QUOTES, 'UTF-8') . "</div>
                                <div><strong>Class:</strong></div><div>" . htmlspecialchars($class, ENT_QUOTES, 'UTF-8') . "</div>
                                <div><strong>Stream:</strong></div><div>" . htmlspecialchars($stream, ENT_QUOTES, 'UTF-8') . "</div>
                                <div><strong>Department:</strong></div><div>" . htmlspecialchars($department, ENT_QUOTES, 'UTF-8') . "</div>
                                <div><strong>Email:</strong></div><div>" . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . "</div>
                                <div><strong>Phone:</strong></div><div>" . htmlspecialchars($phone, ENT_QUOTES, 'UTF-8') . "</div>
                                <div><strong>Registration Date:</strong></div><div>" . $dateStr . "</div>
                                <div><strong>Temporary Password:</strong></div><div>" . htmlspecialchars($temporary_password, ENT_QUOTES, 'UTF-8') . "</div>
                            </div>
                        </div>
                        <div style=\"margin-top:16px;\">
                            <div style=\"font-weight:600; margin-bottom:8px;\">Important Information:</div>
                            <ul style=\"margin:0; padding-left:20px; color:#374151; font-size:14px;\">
                                <li>Keep your index number safe - you'll need it for all transactions</li>
                                <li>Your username is your index number</li>
                                <li>Use your temporary password to login, then reset it immediately</li>
                                <li>All payment receipts will be sent to this email address</li>
                                <li>Contact the finance office for any payment-related queries</li>
                            </ul>
                        </div>
                        <p style=\"margin-top:16px; font-size:14px; color:#374151;\">If you have any questions or notice any incorrect information, please contact the administration office immediately.</p>
                        <hr style=\"border:none; border-top:1px solid #e5e7eb; margin:20px 0;\"/>
                        <div style=\"font-size:13px; color:#6b7280; text-align:center;\">
                            <div style=\"font-weight:600;\">AAMUSTED - Infotess</div>
                            <div>usted.edu.gh, Kumasi, Ghana</div>
                            <div>Phone: +233 24 091 8031</div>
                            <div style=\"margin-top:8px; font-size:12px;\">This is an automated email. Please do not reply to this message.</div>
                        </div>
                    </div>
                </div>";
                $mailer->sendHTML($email, $subject, $html);
            }

            if (!empty($phone) && !$sms_sent) {
                $message .= " SMS delivery failed. Check SMS API settings.";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Pagination settings
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Fetch Students
$search = $_GET['search'] ?? '';
$query = "SELECT SQL_CALC_FOUND_ROWS * FROM students";
$params = [];
if ($search) {
    $query .= " WHERE full_name LIKE ? OR index_number LIKE ?";
    $params = ["%$search%", "%$search%"];
}
$query .= " ORDER BY created_at DESC LIMIT $limit OFFSET $offset";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$students = $stmt->fetchAll();

$total_stmt = $pdo->query("SELECT FOUND_ROWS()");
$total_rows = (int)$total_stmt->fetchColumn();
$total_pages = ceil($total_rows / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Students - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Modal Styles */
        .modal {
            display: none; 
            position: fixed; 
            z-index: 1000; 
            left: 0;
            top: 0;
            width: 100%; 
            height: 100%; 
            overflow: auto; 
            background-color: rgba(0,0,0,0.5); 
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto; 
            padding: 20px;
            border: 1px solid #888;
            width: 80%; 
            max-width: 600px;
            border-radius: 8px;
            position: relative;
        }
        .close-btn {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-btn:hover,
        .close-btn:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar (Reused) -->
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
                <li><a href="../logout.html"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h2>Student Management</h2>
                <button id="openModalBtn" class="btn-primary" style="padding: 10px 20px;"><i class="fas fa-plus"></i> Add New Student</button>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <!-- Add Student Modal -->
            <div id="studentModal" class="modal">
                <div class="modal-content">
                    <span class="close-btn">&times;</span>
                    <h3>Register New Student</h3>
                    <form action="students.php" method="POST" enctype="multipart/form-data" style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-top: 15px;">
                        <input type="hidden" name="action" value="add_student">
                        
                        <div style="grid-column: span 2; text-align: center; margin-bottom: 10px;">
                            <label>Profile Picture</label><br>
                            <input type="file" name="profile_picture" class="form-control" accept="image/*">
                        </div>

                        <div>
                            <label>Full Name</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div>
                            <label>Index Number</label>
                            <input type="text" name="index_number" class="form-control" required>
                        </div>
                        <div>
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" required>
                        </div>
                        <div>
                            <label>Phone Number</label>
                            <input type="text" name="phone_number" class="form-control">
                        </div>
                        <div>
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
                        <div>
                            <label>Level</label>
                            <select name="level" class="form-control" required>
                                <option value="100">100</option>
                                <option value="200">200</option>
                                <option value="300">300</option>
                                <option value="400">400</option>
                            </select>
                        </div>
                        <div>
                            <label>Class</label>
                            <select name="class" class="form-control">
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
                        <div>
                            <label>Stream</label>
                            <select name="stream" class="form-control">
                                <option value="">-- Select Stream --</option>
                                <option value="Regular">Regular</option>
                                <option value="Sandwich">Sandwich</option>
                                <option value="Evening">Evening</option>
                            </select>
                        </div>
                        <div style="grid-column: span 2; margin-top: 10px;">
                            <button type="submit" class="btn-submit">Register Student</button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Student List -->
            <div class="section">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                    <h3>Registered Students</h3>
                    <form action="students.php" method="GET" style="display:flex; gap:10px;">
                        <input type="text" name="search" placeholder="Search name or index..." class="form-control" value="<?php echo htmlspecialchars($search); ?>">
                        <button type="submit" class="btn-login"><i class="fas fa-search"></i></button>
                    </form>
                </div>
                
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Index Number</th>
                                <th>Name</th>
                                <th>Programme</th>
                                <th>Level</th>
                                <th>Class</th>
                                <th>Stream</th>
                                <th>Contact</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($students as $student): ?>
                            <tr>
                                <td>
                                    <?php
                                        $profilePath = $student['profile_picture'] ?? '';
                                        $profileSrc = '../images/infotess.png';
                                        if (!empty($profilePath)) {
                                            $rootProfilePath = __DIR__ . '/../../' . ltrim($profilePath, '/');
                                            $legacyProfilePath = __DIR__ . '/../' . ltrim($profilePath, '/');
                                            if (is_file($rootProfilePath)) {
                                                $profileSrc = '../' . ltrim($profilePath, '/');
                                            } elseif (is_file($legacyProfilePath)) {
                                                $profileSrc = '../backend/' . ltrim($profilePath, '/');
                                            }
                                        }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($profileSrc, ENT_QUOTES, 'UTF-8'); ?>" alt="Profile" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #ddd;">
                                </td>
                                <td><?php echo htmlspecialchars($student['index_number']); ?></td>
                                <td><?php echo htmlspecialchars($student['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($student['department']); ?></td>
                                <td><?php echo htmlspecialchars($student['level']); ?></td>
                                <td><?php echo htmlspecialchars($student['class_name'] ?? '-'); ?></td>
                                <td><?php echo htmlspecialchars($student['stream'] ?? '-'); ?></td>
                                <td>
                                    <?php echo htmlspecialchars($student['phone_number']); ?>
                                </td>
                                <td>
                                    <a href="edit_student.php?id=<?php echo $student['id']; ?>" class="btn-login" style="background:#f0ad4e;">Edit</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; margin-top: 20px; gap: 5px;">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn-login" style="background: #f8f9fa; color: #333; border: 1px solid #ddd;">&laquo; Prev</a>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn-login" style="<?php echo $i == $page ? 'background: var(--primary-color);' : 'background: #f8f9fa; color: #333; border: 1px solid #ddd;'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search='.urlencode($search) : ''; ?>" class="btn-login" style="background: #f8f9fa; color: #333; border: 1px solid #ddd;">Next &raquo;</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
        // Modal Logic
        const modal = document.getElementById("studentModal");
        const btn = document.getElementById("openModalBtn");
        const span = document.getElementsByClassName("close-btn")[0];

        // When the user clicks the button, open the modal 
        btn.onclick = function() {
            modal.style.display = "block";
        }

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        }

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>
