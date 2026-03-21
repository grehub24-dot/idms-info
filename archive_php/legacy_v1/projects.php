<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch Projects from Database
$stmt = $pdo->query("SELECT * FROM projects ORDER BY project_date DESC");
$projects = $stmt->fetchAll();
?>

<div class="hero" style="height: 50vh;">
    <h1>Projects & Innovations</h1>
    <p>Showcase of student projects and department innovations.</p>
</div>

<div class="section">
    <div class="container">
        <?php if (empty($projects)): ?>
            <p style="text-align: center;">Project showcase is being updated. Check back soon!</p>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($projects as $project): ?>
                <div class="card">
                    <?php if ($project['image_url']): ?>
                    <img src="<?php echo $project['image_url']; ?>" alt="<?php echo $project['title']; ?>" style="width: 100%; height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-content">
                        <span class="badge" style="background: <?php 
                            echo $project['status'] === 'completed' ? '#28a745' : ($project['status'] === 'ongoing' ? '#ffc107' : '#17a2b8'); 
                        ?>; color: #fff; padding: 5px 10px; border-radius: 4px; font-size: 0.8rem;">
                            <?php echo ucfirst($project['status']); ?>
                        </span>
                        <h3 class="card-title" style="margin-top: 10px;"><?php echo $project['title']; ?></h3>
                        <p><?php echo $project['description']; ?></p>
                        <p style="margin-top: 10px;"><small><i class="fas fa-calendar"></i> <?php echo date('M d, Y', strtotime($project['project_date'])); ?></small></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>