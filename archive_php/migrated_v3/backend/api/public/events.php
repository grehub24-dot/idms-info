<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

try {
    $stmt = $pdo->query("SELECT * FROM events ORDER BY event_date DESC LIMIT 12");
    $db_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($db_events)) {
        // Fallback static data if DB table is empty
        $events = [
            [
                'title' => '2026 Matriculation Ceremony',
                'event_date' => '2026-02-20',
                'location' => 'AAMUSTED, Kumasi & Mampong Campuses',
                'description' => 'Matriculation ceremony for fresh Postgraduate and Undergraduate students admitted for the 2025/2026 Academic Year.',
                'link' => 'https://aamusted.edu.gh/events/2026-matriculation-ceremony/'
            ],
            [
                'title' => 'Matriculation Ceremony 2025 – Sandwich Session',
                'event_date' => '2025-12-15',
                'location' => 'AAMUSTED Main Auditorium',
                'description' => 'Official matriculation for fresh students admitted to the Sandwich Session for the 2025 academic year.',
                'link' => 'https://aamusted.edu.gh/events/matriculation-ceremony-2025-sandwich-session/'
            ],
            [
                'title' => 'Medical Examinations for Fresh Students',
                'event_date' => '2025-12-01',
                'location' => 'University Clinic',
                'description' => 'Commencement of mandatory medical examinations for all newly admitted students for the 2025/2026 Academic Year.',
                'link' => 'https://aamusted.edu.gh/events/medical-examinations-for-fresh-students/'
            ]
        ];
    } else {
        $events = $db_events;
    }

    json_response(['ok' => true, 'events' => $events]);
} catch (Exception $e) {
    json_response(['ok' => false, 'error' => 'Failed to load events'], 500);
}
