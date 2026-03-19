<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Fetch News from Database (Scraped)
$stmt = $pdo->query("SELECT * FROM news ORDER BY published_at DESC LIMIT 12");
$db_news = $stmt->fetchAll();

// Fallback to Mock if DB is empty
if (empty($db_news)) {
    $news_items = [
        [
            'title' => 'AAMUSTED Honours Former Staff',
            'published_at' => '2026-03-05',
            'content' => 'The Akenten Appiah-Menka University of Skills Training and Entrepreneurial Development (AAMUSTED) has presented a brand-new Royal Super Motorcycle to Mr. Kofi Karikari, a former member...',
            'source_url' => 'https://aamusted.edu.gh/aamusted-honours-former-staff/',
            'image_url' => 'images/aamusted.jpg'
        ],
        [
            'title' => 'AAMUSTED’s First Valedictorian',
            'published_at' => '2026-02-04',
            'content' => 'The Akenten Appiah-Menka University of Skills Training and Entrepreneurial Development (AAMUSTED) made history with the delivery of its first-ever valedictory speech on the final day...',
            'source_url' => 'https://aamusted.edu.gh/aamusteds-first-valedictorian/',
            'image_url' => 'images/aamusted.jpg'
        ]
    ];
} else {
    $news_items = $db_news;
}
?>

<div class="hero" style="height: 40vh; background: linear-gradient(rgba(0,51,102,0.8), rgba(0,51,102,0.8)), url('images/aamusted.jpg') center/cover;">
    <h1>News & Updates</h1>
    <p>Stay informed with the latest news from AAMUSTED</p>
</div>

<div class="section">
    <div class="container">
        <h2 class="section-title">Latest News</h2>
        
        <div class="card-grid">
            <?php foreach ($news_items as $news): ?>
            <div class="card">
                <div style="height: 200px; overflow: hidden;">
                    <img src="<?php echo $news['image_url'] ?: 'images/aamusted.jpg'; ?>" alt="News Image" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;">
                </div>
                <div class="card-content">
                    <span style="font-size: 0.8rem; color: #666; display: block; margin-bottom: 5px;">
                        <i class="far fa-calendar-alt"></i> <?php echo date('F j, Y', strtotime($news['published_at'])); ?>
                    </span>
                    <h3 style="font-size: 1.2rem; margin-bottom: 10px; color: var(--primary-color);">
                        <a href="<?php echo $news['source_url']; ?>" target="_blank" style="text-decoration: none; color: inherit;">
                            <?php echo htmlspecialchars($news['title']); ?>
                        </a>
                    </h3>
                    <p style="color: #555; font-size: 0.95rem; line-height: 1.6;">
                        <?php echo htmlspecialchars(substr($news['content'], 0, 150)) . '...'; ?>
                    </p>
                    <a href="<?php echo $news['source_url']; ?>" target="_blank" style="display: inline-block; margin-top: 15px; color: var(--secondary-color); font-weight: bold;">Read More &rarr;</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="https://aamusted.edu.gh/news/" target="_blank" class="btn-primary">View All News on Official Site</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>