<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch department info from scraped data
$stmt = $pdo->prepare("SELECT content FROM department_info WHERE key_name = ?");
$stmt->execute(['dite_overview']);
$overview = $stmt->fetchColumn();

// Staff Images Array (Based on provided files)
$staff_members = [
    ['name' => 'Prof. Yarhands Dissou Arthur', 'image' => 'images/PROF-YARHANDS.png', 'role' => 'Dean, FASME'],
    ['name' => 'Dr. George Asante', 'image' => 'images/George-Asante.png', 'role' => 'H.O.D, Department of IT Education'],
    ['name' => 'Prof. Francis Ohene Boateng', 'image' => 'images/PROF-FO-BOATENG.png', 'role' => 'Associate Professor'],
    ['name' => 'Prof. Ebenezer Bonyah', 'image' => 'images/PROF_BONYAH-.png', 'role' => 'Professor'],
    ['name' => 'Dr. Adasa Nkrumah Kofi Frimpong', 'image' => 'images/Dr.-Adasa-Nkrumah-K.-F.jpg', 'role' => 'Ag. Head, Academic & Admin Computing'],
    ['name' => 'Rev. Dr. Benjamin Adu Obeng', 'image' => 'images/Rev.-Dr.-Adu-Obeng.png', 'role' => 'Lecturer'],
    ['name' => 'Dr. Joseph Frank Gordon', 'image' => 'images/Dr.-Joseph-Gordon.png', 'role' => 'Lecturer'],
    ['name' => 'Dr. Emmanuel Akweittey', 'image' => 'images/AKWEITTEY-.jpg', 'role' => 'Senior Lecturer'],
    ['name' => 'Dr. Ernest Larbi', 'image' => 'images/Mr.-Ernest-Larbi.png', 'role' => 'Lecturer'],
    ['name' => 'Mr. Franco Osei-Wusu', 'image' => 'images/franco.png', 'role' => 'Assistant Lecturer'],
    ['name' => 'Mr. Kennedy Gyimah', 'image' => 'images/Kennedy-Gyimah.png', 'role' => 'Lecturer']
];
?>

<div class="container" style="padding: 40px 0;">
    <h1>Department Overview</h1>
    
    <!-- Department Content -->
    <div class="department-content" style="margin-bottom: 50px;">
        <?php if ($overview): ?>
            <?php echo $overview; ?>
        <?php else: ?>
            <p>The Department of Information Technology Education (DITE) is a leading department in the Faculty of Applied Sciences and Mathematics Education (FASME) at AAMUSTED.</p>
            <p>We are dedicated to training competent teachers and professionals in Information Technology who can contribute meaningfully to national development.</p>
            
            <h3>Our Mission</h3>
            <p>To provide high-quality education in Information Technology, fostering innovation, research, and community service.</p>
            
            <h3>Programs Offered</h3>
            <ul>
                <li>B.Sc. Information Technology Education</li>
                <li>Diploma in Information Technology</li>
                <li>M.Phil. Information Technology</li>
            </ul>
        <?php endif; ?>
    </div>

    <!-- Faculty & Staff Section -->
    <h2 class="section-title">Meet Our Faculty & Staff</h2>
    <div class="card-grid">
        <?php foreach ($staff_members as $staff): ?>
            <a href="staff_profile.php?name=<?php echo urlencode($staff['name']); ?>" class="card" style="text-align: center; text-decoration: none; color: inherit; display: block;">
                <img src="<?php echo $staff['image']; ?>" alt="<?php echo htmlspecialchars($staff['name']); ?>" style="height: 250px; object-fit: cover; width: 100%;">
                <div class="card-content">
                    <h3 style="margin-bottom: 5px;"><?php echo htmlspecialchars($staff['name']); ?></h3>
                    <p style="color: var(--secondary-color); font-weight: bold;"><?php echo htmlspecialchars($staff['role']); ?></p>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>