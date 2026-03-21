<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Mock Scraped Events Data (In production, use a dedicated scraper script)
// Based on the provided search results from aamusted.edu.gh/events/
$events = [
    [
        'title' => '2026 Matriculation Ceremony',
        'date' => '2026-02-20',
        'location' => 'AAMUSTED, Kumasi & Mampong Campuses',
        'description' => 'Matriculation ceremony for fresh Postgraduate and Undergraduate students admitted for the 2025/2026 Academic Year.',
        'link' => 'https://aamusted.edu.gh/events/2026-matriculation-ceremony/'
    ],
    [
        'title' => 'Matriculation Ceremony 2025 – Sandwich Session',
        'date' => '2025-12-15',
        'location' => 'AAMUSTED Main Auditorium',
        'description' => 'Official matriculation for fresh students admitted to the Sandwich Session for the 2025 academic year.',
        'link' => 'https://aamusted.edu.gh/events/matriculation-ceremony-2025-sandwich-session/'
    ],
    [
        'title' => 'Medical Examinations for Fresh Students',
        'date' => '2025-12-01',
        'location' => 'University Clinic',
        'description' => 'Commencement of mandatory medical examinations for all newly admitted students for the 2025/2026 Academic Year.',
        'link' => 'https://aamusted.edu.gh/events/medical-examinations-for-fresh-students/'
    ],
    [
        'title' => 'First Congregation',
        'date' => '2023-05-17',
        'location' => 'Ceremonial Grounds',
        'description' => 'Graduation ceremony for the 2022 Graduands of Akenten Appiah-Menka University of Skills Training and Entrepreneurial Development.',
        'link' => 'https://aamusted.edu.gh/events/first-congregation/'
    ],
    [
        'title' => 'FTE Graduate Seminar Series',
        'date' => '2023-03-21',
        'location' => 'Virtual (Zoom)',
        'description' => 'A two-day virtual seminar series organized by the Faculty of Technical Education (FTE) for graduate students and researchers.',
        'link' => 'https://aamusted.edu.gh/events/fte-graduate-seminar-series/'
    ]
];
?>

<div class="hero" style="height: 40vh; background: linear-gradient(rgba(0,51,102,0.8), rgba(0,51,102,0.8)), url('images/aamusted.jpg') center/cover;">
    <h1>Upcoming Events</h1>
    <p>Join us for our upcoming academic and social gatherings</p>
</div>

<div class="section">
    <div class="container">
        <h2 class="section-title">Calendar of Events</h2>
        
        <div class="card-grid">
            <?php foreach ($events as $event): ?>
                <div class="card">
                    <div class="card-content">
                        <div style="display: flex; align-items: center; margin-bottom: 15px;">
                            <div style="background: var(--secondary-color); color: var(--primary-color); padding: 10px; border-radius: 5px; text-align: center; margin-right: 15px; min-width: 60px;">
                                <div style="font-size: 1.5rem; font-weight: bold;"><?php echo date('d', strtotime($event['date'])); ?></div>
                                <div style="font-size: 0.8rem; text-transform: uppercase;"><?php echo date('M', strtotime($event['date'])); ?></div>
                            </div>
                            <div>
                                <h3 style="margin: 0; font-size: 1.1rem; color: var(--primary-color);"><?php echo htmlspecialchars($event['title']); ?></h3>
                                <span style="font-size: 0.9rem; color: #666;"><i class="fas fa-clock"></i> <?php echo date('l, Y', strtotime($event['date'])); ?></span>
                            </div>
                        </div>
                        
                        <p style="margin-bottom: 10px;"><strong><i class="fas fa-map-marker-alt" style="color: var(--secondary-color);"></i> Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                        <p style="color: #555; line-height: 1.6; margin-bottom: 15px;"><?php echo htmlspecialchars($event['description']); ?></p>
                        <a href="<?php echo $event['link']; ?>" target="_blank" style="display: inline-block; color: var(--secondary-color); font-weight: bold;">View Event Details &rarr;</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div style="text-align: center; margin-top: 40px;">
            <a href="https://aamusted.edu.gh/events/" target="_blank" class="btn-primary">View Full Calendar on Official Site</a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>