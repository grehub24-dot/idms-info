<?php
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_response(['error' => 'Method not allowed'], 405);
}

try {
    // Fetch department info from scraped data
    $stmt = $pdo->prepare("SELECT content FROM department_info WHERE key_name = ?");
    $stmt->execute(['dite_overview']);
    $overview = $stmt->fetchColumn();

    $staff_members = [
        [
            'name' => 'Prof. Yarhands Dissou Arthur',
            'role' => 'Dean, FASME', 
            'image' => 'images/PROF-YARHANDS.png',
            'bio' => 'Prof. Yarhands Dissou Arthur is the Dean of the Faculty of Applied Sciences and Mathematics Education. He is a distinguished scholar with extensive experience in educational leadership and research.',
            'email' => 'ydarthur@aamusted.edu.gh',
            'research' => ['Educational Leadership', 'Applied Sciences', 'Curriculum Development']
        ],
        [
            'name' => 'Dr. George Asante',
            'role' => 'H.O.D, Department of IT Education', 
            'image' => 'images/George-Asante.png',
            'bio' => 'Dr. George Asante serves as the Head of the Department of Information Technology Education. He is committed to advancing IT education and fostering a culture of innovation among students.',
            'email' => 'gasante@aamusted.edu.gh',
            'research' => ['Information Technology Education', 'E-Learning', 'Educational Technology']
        ],
        [
            'name' => 'Prof. Francis Ohene Boateng',
            'role' => 'Associate Professor', 
            'image' => 'images/PROF-FO-BOATENG.png',
            'bio' => 'Prof. Francis Ohene Boateng is an Associate Professor with a focus on computing and technology integration in education.',
            'email' => 'foboateng@aamusted.edu.gh',
            'research' => ['Computing', 'Artificial Intelligence', 'Data Science']
        ],
        [
            'name' => 'Prof. Ebenezer Bonyah',
            'role' => 'Professor', 
            'image' => 'images/PROF_BONYAH-.png',
            'bio' => 'Prof. Ebenezer Bonyah is a Professor known for his contributions to mathematics and its applications in technology.',
            'email' => 'ebonyah@aamusted.edu.gh',
            'research' => ['Mathematical Modeling', 'Applied Mathematics', 'Statistics']
        ],
        [
            'name' => 'Dr. Adasa Nkrumah Kofi Frimpong',
            'role' => 'Ag. Head, Academic & Admin Computing', 
            'image' => 'images/Dr.-Adasa-Nkrumah-K.-F.jpg',
            'bio' => 'Dr. Adasa Nkrumah Kofi Frimpong heads the Academic and Administrative Computing unit, ensuring robust digital infrastructure for the university.',
            'email' => 'ankfrimpong@aamusted.edu.gh',
            'research' => ['Cloud Computing', 'Network Security', 'IT Infrastructure']
        ],
        [
            'name' => 'Rev. Dr. Benjamin Adu Obeng',
            'role' => 'Lecturer', 
            'image' => 'images/Rev.-Dr.-Adu-Obeng.png',
            'bio' => 'Rev. Dr. Benjamin Adu Obeng combines his pastoral and academic roles to mentor students in both character and learning.',
            'email' => 'baobeng@aamusted.edu.gh',
            'research' => ['Ethics in IT', 'Software Engineering', 'Database Management']
        ],
        [
            'name' => 'Dr. Joseph Frank Gordon',
            'role' => 'Lecturer', 
            'image' => 'images/Dr.-Joseph-Gordon.png',
            'bio' => 'Dr. Joseph Frank Gordon is a dedicated lecturer with a passion for teaching and research in computer science.',
            'email' => 'jfgordon@aamusted.edu.gh',
            'research' => ['Computer Science Education', 'Programming', 'Algorithms']
        ],
        [
            'name' => 'Dr. Emmanuel Akweittey',
            'role' => 'Senior Lecturer', 
            'image' => 'images/AKWEITTEY-.jpg',
            'bio' => 'Dr. Emmanuel Akweittey is a Senior Lecturer with expertise in advanced computing concepts and methodologies.',
            'email' => 'eakweittey@aamusted.edu.gh',
            'research' => ['Advanced Computing', 'Machine Learning', 'Cybersecurity']
        ],
        [
            'name' => 'Dr. Ernest Larbi',
            'role' => 'Lecturer', 
            'image' => 'images/Mr.-Ernest-Larbi.png',
            'bio' => 'Dr. Ernest Larbi is a lecturer focused on practical IT skills and student development.',
            'email' => 'elarbi@aamusted.edu.gh',
            'research' => ['Web Technologies', 'Mobile Application Development', 'HCI']
        ],
        [
            'name' => 'Mr. Franco Osei-Wusu',
            'role' => 'Assistant Lecturer', 
            'image' => 'images/franco.png',
            'bio' => 'Mr. Franco Osei-Wusu is an Assistant Lecturer supporting the department in various academic and technical capacities.',
            'email' => 'foseiwusu@aamusted.edu.gh',
            'research' => ['Network Administration', 'System Analysis', 'Tech Support']
        ],
        [
            'name' => 'Mr. Kennedy Gyimah',
            'role' => 'Lecturer', 
            'image' => 'images/Kennedy-Gyimah.png',
            'bio' => 'Mr. Kennedy Gyimah is a Lecturer with expertise in Applied Mathematics, Machine Learning, and Computer Vision. He is dedicated to integrating technology into mathematical education.',
            'email' => 'kennedygyimah@aamusted.edu.gh',
            'research' => ['Applied Mathematics', 'Machine Learning', 'Computer Vision']
        ]
    ];

    json_response([
        'ok' => true,
        'overview' => $overview ?: null,
        'staff' => $staff_members
    ]);
} catch (Exception $e) {
    json_response(['error' => 'Failed to fetch department info'], 500);
}
