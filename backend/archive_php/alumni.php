<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch Alumni from Database
$stmt = $pdo->query("SELECT * FROM alumni ORDER BY graduation_year DESC");
$alumni = $stmt->fetchAll();
?>

<div class="hero" style="height: 50vh;">
    <h1>Alumni Network</h1>
    <p>Connect with former students and industry professionals.</p>
</div>

<div class="section">
    <div class="container">
        <?php if (empty($alumni)): ?>
            <p style="text-align: center;">Our alumni records are being updated. Check back soon!</p>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($alumni as $alum): ?>
                <div class="card">
                    <div style="padding: 20px; text-align: center;">
                        <img src="<?php echo $alum['image_url'] ?: 'images/user-placeholder.png'; ?>" alt="<?php echo $alum['full_name']; ?>" style="width: 120px; height: 120px; border-radius: 50%; object-fit: cover;">
                    </div>
                    <div class="card-content">
                        <h3 class="card-title"><?php echo $alum['full_name']; ?></h3>
                        <p style="color: var(--secondary-color); font-weight: bold;">Class of <?php echo $alum['graduation_year']; ?></p>
                        <p style="margin: 10px 0;"><strong><?php echo $alum['position'] ?? ''; ?></strong> at <?php echo $alum['company'] ?? ''; ?></p>
                        <p style="font-style: italic; font-size: 0.9rem; margin-top: 10px;">"<?php echo $alum['testimonial'] ?? ''; ?>"</p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>