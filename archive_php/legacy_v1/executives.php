<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch Executives from Database
$stmt = $pdo->query("SELECT * FROM executives ORDER BY id ASC");
$executives = $stmt->fetchAll();

// If no data in DB, use mock for fallback or show empty
if (empty($executives)) {
    $executives = [
        [
            'full_name' => 'John Doe',
            'position' => 'President',
            'image_url' => 'images/user-placeholder.png',
            'bio' => 'Passionate about leadership and tech.',
            'email' => 'president@infotess.org'
        ],
        [
            'full_name' => 'Jane Smith',
            'position' => 'Vice President',
            'image_url' => 'images/user-placeholder.png',
            'bio' => 'Dedicated to student welfare.',
            'email' => 'vp@infotess.org'
        ]
    ];
}
?>

<div class="hero" style="height: 50vh;">
    <h1>Our Leadership</h1>
    <p>Meet the executives serving the 2025/2026 administration.</p>
</div>

<div class="section">
    <div class="container">
        <div class="card-grid">
            <?php foreach ($executives as $exec): ?>
            <div class="card" style="text-align: center;">
                <div style="padding: 20px;">
                    <img src="<?php echo $exec['image_url'] ?: 'images/user-placeholder.png'; ?>" alt="<?php echo $exec['full_name']; ?>" style="width: 150px; height: 150px; border-radius: 50%; object-fit: cover;">
                </div>
                <div class="card-content">
                    <h3 class="card-title"><?php echo $exec['full_name']; ?></h3>
                    <p style="color: var(--secondary-color); font-weight: bold;"><?php echo $exec['position']; ?></p>
                    <p style="margin: 10px 0;"><?php echo $exec['bio'] ?? ''; ?></p>
                    <div style="margin-top: 15px;">
                        <?php if (isset($exec['email'])): ?>
                        <a href="mailto:<?php echo $exec['email']; ?>" style="color: var(--primary-color); margin: 0 10px;"><i class="fas fa-envelope"></i></a>
                        <?php endif; ?>
                        <?php if (isset($exec['linkedin_url'])): ?>
                        <a href="<?php echo $exec['linkedin_url']; ?>" style="color: var(--primary-color); margin: 0 10px;"><i class="fab fa-linkedin"></i></a>
                        <?php endif; ?>
                        <?php if (isset($exec['github_url'])): ?>
                        <a href="<?php echo $exec['github_url']; ?>" style="color: var(--primary-color); margin: 0 10px;"><i class="fab fa-github"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
