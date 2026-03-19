<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch recent news/events for the homepage
$stmt = $pdo->query("SELECT * FROM activities ORDER BY activity_date DESC LIMIT 3");
$activities = $stmt->fetchAll();
?>

<!-- Hero Section -->
<section class="hero">
    <h1>Welcome to INFOTESS</h1>
    <p>Information Technology Students’ Society of AAMUSTED. Empowering students through technology, innovation, and leadership.</p>
    <a href="about.php" class="btn-cta">Learn More</a>
</section>

<!-- About Preview -->
<section class="section">
    <div class="container">
        <h2 class="section-title">Who We Are</h2>
        <div style="text-align: center; max-width: 800px; margin: 0 auto;">
            <p>INFOTESS is the official student body for the Department of Information Technology Education (DITE) at AAMUSTED. We are dedicated to bridging the gap between academic theory and industry practice through workshops, seminars, and collaborative projects.</p>
        </div>
    </div>
</section>

<!-- Activities/News -->
<section class="section" style="background: var(--light-bg);">
    <div class="container">
        <h2 class="section-title">Latest Activities</h2>
        <div class="card-grid">
            <?php if (count($activities) > 0): ?>
                <?php foreach ($activities as $activity): ?>
                <div class="card">
                    <img src="<?php echo !empty($activity['image_url']) ? $activity['image_url'] : 'images/default-activity.jpg'; ?>" alt="Activity">
                    <div class="card-content">
                        <h3 class="card-title"><?php echo htmlspecialchars($activity['title']); ?></h3>
                        <p><?php echo substr(htmlspecialchars($activity['description']), 0, 100) . '...'; ?></p>
                        <a href="activities.php" style="color: var(--primary-color); font-weight: bold; margin-top: 10px; display: inline-block;">Read More &rarr;</a>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Static placeholders if no DB data -->
                <div class="card">
                    <img src="images/event1.jpg" alt="Event 1" style="background:#ccc;">
                    <div class="card-content">
                        <h3 class="card-title">Annual Tech Week</h3>
                        <p>Join us for a week of coding, innovation, and networking with industry experts.</p>
                    </div>
                </div>
                <div class="card">
                    <img src="images/event2.jpg" alt="Event 2" style="background:#ccc;">
                    <div class="card-content">
                        <h3 class="card-title">Freshers' Akwaaba</h3>
                        <p>Welcoming our new students to the DITE family with fun and orientation.</p>
                    </div>
                </div>
                <div class="card">
                    <img src="images/event3.jpg" alt="Event 3" style="background:#ccc;">
                    <div class="card-content">
                        <h3 class="card-title">Web Dev Bootcamp</h3>
                        <p>A hands-on session on modern web development technologies.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="section" style="text-align: center;">
    <div class="container">
        <h2>Ready to join the movement?</h2>
        <p style="margin: 20px 0;">Become an active member of INFOTESS today.</p>
        <a href="contact.php" class="btn-cta">Contact Us</a>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
