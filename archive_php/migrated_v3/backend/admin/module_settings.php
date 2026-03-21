<?php
require_once '../includes/db.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('../login.php');
}

$message = '';
$error = '';

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_executive') {
        $name = sanitize($_POST['full_name']);
        $pos = sanitize($_POST['position']);
        $image_url = 'images/user-placeholder.png';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../images/executives/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = time() . '_' . $_FILES['image']['name'];
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_url = 'images/executives/' . $filename;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO executives (full_name, position, image_url) VALUES (?, ?, ?)");
        $stmt->execute([$name, $pos, $image_url]);
        $message = "Executive added successfully!";
    } elseif ($action === 'add_alumni') {
        $name = sanitize($_POST['full_name']);
        $year = sanitize($_POST['graduation_year']);
        $image_url = 'images/user-placeholder.png';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../images/alumni/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = time() . '_' . $_FILES['image']['name'];
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_url = 'images/alumni/' . $filename;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO alumni (full_name, graduation_year, image_url) VALUES (?, ?, ?)");
        $stmt->execute([$name, $year, $image_url]);
        $message = "Alumni added successfully!";
    } elseif ($action === 'add_gallery') {
        $title = sanitize($_POST['title']);
        $image_url = 'images/gallery-placeholder.png';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../images/gallery/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = time() . '_' . $_FILES['image']['name'];
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_url = 'images/gallery/' . $filename;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO gallery (title, image_url) VALUES (?, ?)");
        $stmt->execute([$title, $image_url]);
        $message = "Gallery item added successfully!";
    } elseif ($action === 'add_project') {
        $title = sanitize($_POST['title']);
        $desc = sanitize($_POST['description']);
        $status = sanitize($_POST['status']);
        $date = sanitize($_POST['project_date']);
        $image_url = 'images/project-placeholder.png';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../images/projects/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $filename = time() . '_' . $_FILES['image']['name'];
            if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $filename)) {
                $image_url = 'images/projects/' . $filename;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO projects (title, description, status, project_date, image_url) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$title, $desc, $status, $date, $image_url]);
        $message = "Project added successfully!";
    } elseif ($action === 'respond_contact') {
        $sub_id = intval($_POST['submission_id']);
        $response = sanitize($_POST['response']);
        $stmt = $pdo->prepare("UPDATE contact_submissions SET response = ?, responded_at = NOW() WHERE id = ?");
        $stmt->execute([$response, $sub_id]);
        $message = "Response saved successfully!";
    } elseif ($action === 'delete_executive') {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM executives WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Executive deleted successfully!";
    } elseif ($action === 'delete_alumni') {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM alumni WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Alumni deleted successfully!";
    } elseif ($action === 'delete_gallery') {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Gallery item deleted successfully!";
    } elseif ($action === 'delete_project') {
        $id = intval($_POST['id']);
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$id]);
        $message = "Project deleted successfully!";
    }
}

// Fetch Data for Tables
$executives = $pdo->query("SELECT * FROM executives")->fetchAll();
$alumni = $pdo->query("SELECT * FROM alumni")->fetchAll();
$gallery = $pdo->query("SELECT * FROM gallery ORDER BY created_at DESC")->fetchAll();
$projects = $pdo->query("SELECT * FROM projects ORDER BY project_date DESC")->fetchAll();
$submissions = $pdo->query("SELECT * FROM contact_submissions ORDER BY created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Module Settings - Admin</title>
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
            margin: 10% auto;
            padding: 20px;
            border-radius: 8px;
            width: 400px;
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
                <li><a href="module_settings.php" class="active"><i class="fas fa-cogs"></i> Module Settings</a></li>
                <li><a href="settings.php"><i class="fas fa-tools"></i> System Settings</a></li>
                <li><a href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <div class="top-bar">
                <h2>Module & Contact Settings</h2>
                <div style="display:flex; gap:10px; flex-wrap: wrap;">
                    <button onclick="document.getElementById('execModal').style.display='block'" class="btn-primary"><i class="fas fa-user-tie"></i> Add Executive</button>
                    <button onclick="document.getElementById('alumniModal').style.display='block'" class="btn-primary"><i class="fas fa-graduation-cap"></i> Add Alumni</button>
                    <button onclick="document.getElementById('galleryModal').style.display='block'" class="btn-primary"><i class="fas fa-images"></i> Add Gallery</button>
                    <button onclick="document.getElementById('projectModal').style.display='block'" class="btn-primary"><i class="fas fa-project-diagram"></i> Add Project</button>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>

            <!-- Contact Submissions -->
            <div class="section">
                <div class="card">
                    <h3>Contact Us Submissions</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subject</th>
                                <th>Message</th>
                                <th>Response</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($submissions as $sub): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($sub['name']); ?></td>
                                    <td><?php echo htmlspecialchars($sub['email']); ?></td>
                                    <td><?php echo htmlspecialchars($sub['subject']); ?></td>
                                    <td><?php echo htmlspecialchars($sub['message']); ?></td>
                                    <td><?php echo $sub['response'] ? htmlspecialchars($sub['response']) : '<em>Pending</em>'; ?></td>
                                    <td>
                                        <button onclick="document.getElementById('resp-<?php echo $sub['id']; ?>').style.display='block'" class="btn-primary">Respond</button>
                                        <div id="resp-<?php echo $sub['id']; ?>" style="display:none; margin-top:10px;">
                                            <form method="POST">
                                                <input type="hidden" name="action" value="respond_contact">
                                                <input type="hidden" name="submission_id" value="<?php echo $sub['id']; ?>">
                                                <textarea name="response" class="form-control" placeholder="Type response..."></textarea>
                                                <button type="submit" class="btn-submit">Submit Response</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div class="card">
                    <h3>Current Executives</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Position</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($executives as $exec): ?>
                                <tr>
                                    <td><img src="../<?php echo $exec['image_url'] ?: 'images/user-placeholder.png'; ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover;"></td>
                                    <td><?php echo htmlspecialchars($exec['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($exec['position']); ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this executive?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_executive">
                                            <input type="hidden" name="id" value="<?php echo $exec['id']; ?>">
                                            <button type="submit" class="btn-login" style="background:#dc3545; padding: 5px 10px; font-size: 0.8rem;"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card">
                    <h3>Current Alumni</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Year</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($alumni as $alum): ?>
                                <tr>
                                    <td><img src="../<?php echo $alum['image_url'] ?: 'images/user-placeholder.png'; ?>" style="width:40px; height:40px; border-radius:50%; object-fit:cover;"></td>
                                    <td><?php echo htmlspecialchars($alum['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($alum['graduation_year']); ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this alumni?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_alumni">
                                            <input type="hidden" name="id" value="<?php echo $alum['id']; ?>">
                                            <button type="submit" class="btn-login" style="background:#dc3545; padding: 5px 10px; font-size: 0.8rem;"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="section" style="display:grid; grid-template-columns: 1fr 1fr; gap:20px; margin-top: 30px;">
                <div class="card">
                    <h3>Current Gallery Items</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($gallery as $item): ?>
                                <tr>
                                    <td><img src="../<?php echo $item['image_url']; ?>" style="width:60px; height:40px; object-fit:cover; border-radius:4px;"></td>
                                    <td><?php echo htmlspecialchars($item['title']); ?></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this gallery item?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_gallery">
                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn-login" style="background:#dc3545; padding: 5px 10px; font-size: 0.8rem;"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="card">
                    <h3>Current Projects</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Image</th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td><img src="../<?php echo $project['image_url'] ?: 'images/project-placeholder.png'; ?>" style="width:60px; height:40px; object-fit:cover; border-radius:4px;"></td>
                                    <td><?php echo htmlspecialchars($project['title']); ?></td>
                                    <td><span class="badge" style="background:#17a2b8; color:white; padding:2px 6px; border-radius:4px; font-size:0.75rem;"><?php echo ucfirst($project['status']); ?></span></td>
                                    <td>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this project?');" style="display:inline;">
                                            <input type="hidden" name="action" value="delete_project">
                                            <input type="hidden" name="id" value="<?php echo $project['id']; ?>">
                                            <button type="submit" class="btn-login" style="background:#dc3545; padding: 5px 10px; font-size: 0.8rem;"><i class="fas fa-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Executive Modal -->
    <div id="execModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('execModal').style.display='none'">&times;</span>
            <h3>Add Executive</h3>
            <form method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
                <input type="hidden" name="action" value="add_executive">
                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Enter Full Name" required>
                </div>
                <div class="form-group">
                    <label>Position</label>
                    <input type="text" name="position" class="form-control" placeholder="Enter Position" required>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">Add Executive</button>
            </form>
        </div>
    </div>

    <!-- Alumni Modal -->
    <div id="alumniModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('alumniModal').style.display='none'">&times;</span>
            <h3>Add Alumni</h3>
            <form method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
                <input type="hidden" name="action" value="add_alumni">
                <div class="form-group">
                    <label>Profile Picture</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="full_name" class="form-control" placeholder="Enter Full Name" required>
                </div>
                <div class="form-group">
                    <label>Year</label>
                    <input type="text" name="graduation_year" class="form-control" placeholder="Enter Graduation Year" required>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">Add Alumni</button>
            </form>
        </div>
    </div>

    <!-- Project Modal -->
    <div id="projectModal" class="modal">
        <div class="modal-content" style="width: 500px;">
            <span class="close-btn" onclick="document.getElementById('projectModal').style.display='none'">&times;</span>
            <h3>Add Project</h3>
            <form method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
                <input type="hidden" name="action" value="add_project">
                <div class="form-group">
                    <label>Project Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
                <div class="form-group">
                    <label>Title</label>
                    <input type="text" name="title" class="form-control" placeholder="Project Title" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Brief description"></textarea>
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px;">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" class="form-control">
                            <option value="completed">Completed</option>
                            <option value="ongoing">Ongoing</option>
                            <option value="planned">Planned</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Date</label>
                        <input type="date" name="project_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">Add Project</button>
            </form>
        </div>
    </div>

    <!-- Gallery Modal -->
    <div id="galleryModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="document.getElementById('galleryModal').style.display='none'">&times;</span>
            <h3>Add Gallery Item</h3>
            <form method="POST" enctype="multipart/form-data" style="margin-top: 15px;">
                <input type="hidden" name="action" value="add_gallery">
                <div class="form-group">
                    <label>Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*" required>
                </div>
                <div class="form-group">
                    <label>Title/Caption</label>
                    <input type="text" name="title" class="form-control" placeholder="Image Title" required>
                </div>
                <button type="submit" class="btn-primary" style="width: 100%;">Add to Gallery</button>
            </form>
        </div>
    </div>

    <script>
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.className === 'modal') {
                event.target.style.display = "none";
            }
        }
    </script>
</body>
</html>

