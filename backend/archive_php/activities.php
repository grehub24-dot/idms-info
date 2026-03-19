<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$stmt = $pdo->query("SELECT * FROM activities ORDER BY activity_date DESC");
$activities = $stmt->fetchAll();
?>

<div class="container" style="padding: 40px 0;">
    <h1>INFOTESS Activities</h1>
    <div class="card-grid">
        <?php foreach ($activities as $activity): ?>
            <div class="card">
                <img src="<?php echo !empty($activity['image_url']) ? $activity['image_url'] : 'images/default-activity.jpg'; ?>" alt="Activity">
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($activity['title']); ?></h3>
                    <p class="date"><?php echo date('F j, Y', strtotime($activity['activity_date'])); ?></p>
                    <p><?php echo htmlspecialchars($activity['description']); ?></p>
                    <?php if (!empty($activity['registration_link'])): ?>
                        <a href="<?php echo htmlspecialchars($activity['registration_link']); ?>" class="btn-primary">Register</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (empty($activities)): ?>
            <p>No upcoming activities at the moment.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>