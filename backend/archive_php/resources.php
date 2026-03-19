<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch Resources from Database
$stmt = $pdo->query("SELECT * FROM student_resources ORDER BY created_at DESC");
$resources = $stmt->fetchAll();
?>

<div class="hero" style="height: 50vh;">
    <h1>Student Resources</h1>
    <p>Access learning materials, lecture notes, and software tools.</p>
</div>

<div class="section">
    <div class="container">
        <?php if (empty($resources)): ?>
            <div class="card" style="text-align: center; padding: 40px;">
                <i class="fas fa-folder-open" style="font-size: 3rem; color: #ccc; margin-bottom: 20px;"></i>
                <p>No resources have been uploaded yet. Check back soon!</p>
            </div>
        <?php else: ?>
            <div class="card-grid">
                <?php foreach ($resources as $res): ?>
                <div class="card">
                    <div class="card-content" style="display: flex; align-items: center; gap: 20px;">
                        <div style="font-size: 2.5rem; color: var(--primary-color);">
                            <i class="fas <?php 
                                echo strpos(strtolower($res['resource_type']), 'pdf') !== false ? 'fa-file-pdf' : 
                                    (strpos(strtolower($res['resource_type']), 'doc') !== false ? 'fa-file-word' : 'fa-file-alt'); 
                            ?>"></i>
                        </div>
                        <div style="flex: 1;">
                            <h3 class="card-title" style="margin-bottom: 5px;"><?php echo $res['title']; ?></h3>
                            <p style="font-size: 0.9rem; margin-bottom: 10px;"><?php echo $res['description']; ?></p>
                            <a href="<?php echo $res['file_url']; ?>" class="btn-login" style="padding: 5px 15px; font-size: 0.8rem; display: inline-block;" download>
                                <i class="fas fa-download"></i> Download <?php echo $res['resource_type']; ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>