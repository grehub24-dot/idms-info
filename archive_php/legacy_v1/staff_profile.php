<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

// Staff Data (Same as in department.php)
$staff_members = [
    'Prof. Yarhands Dissou Arthur' => [
        'role' => 'Dean, FASME', 
        'image' => 'images/PROF-YARHANDS.png',
        'bio' => 'Prof. Yarhands Dissou Arthur is the Dean of the Faculty of Applied Sciences and Mathematics Education. He is a distinguished scholar with extensive experience in educational leadership and research.',
        'email' => 'ydarthur@aamusted.edu.gh',
        'research' => ['Educational Leadership', 'Applied Sciences', 'Curriculum Development']
    ],
    'Dr. George Asante' => [
        'role' => 'H.O.D, Department of IT Education', 
        'image' => 'images/George-Asante.png',
        'bio' => 'Dr. George Asante serves as the Head of the Department of Information Technology Education. He is committed to advancing IT education and fostering a culture of innovation among students.',
        'email' => 'gasante@aamusted.edu.gh',
        'research' => ['Information Technology Education', 'E-Learning', 'Educational Technology']
    ],
    'Prof. Francis Ohene Boateng' => [
        'role' => 'Associate Professor', 
        'image' => 'images/PROF-FO-BOATENG.png',
        'bio' => 'Prof. Francis Ohene Boateng is an Associate Professor with a focus on computing and technology integration in education.',
        'email' => 'foboateng@aamusted.edu.gh',
        'research' => ['Computing', 'Artificial Intelligence', 'Data Science']
    ],
    'Prof. Ebenezer Bonyah' => [
        'role' => 'Professor', 
        'image' => 'images/PROF_BONYAH-.png',
        'bio' => 'Prof. Ebenezer Bonyah is a Professor known for his contributions to mathematics and its applications in technology.',
        'email' => 'ebonyah@aamusted.edu.gh',
        'research' => ['Mathematical Modeling', 'Applied Mathematics', 'Statistics']
    ],
    'Dr. Adasa Nkrumah Kofi Frimpong' => [
        'role' => 'Ag. Head, Academic & Admin Computing', 
        'image' => 'images/Dr.-Adasa-Nkrumah-K.-F.jpg',
        'bio' => 'Dr. Adasa Nkrumah Kofi Frimpong heads the Academic and Administrative Computing unit, ensuring robust digital infrastructure for the university.',
        'email' => 'ankfrimpong@aamusted.edu.gh',
        'research' => ['Cloud Computing', 'Network Security', 'IT Infrastructure']
    ],
    'Rev. Dr. Benjamin Adu Obeng' => [
        'role' => 'Lecturer', 
        'image' => 'images/Rev.-Dr.-Adu-Obeng.png',
        'bio' => 'Rev. Dr. Benjamin Adu Obeng combines his pastoral and academic roles to mentor students in both character and learning.',
        'email' => 'baobeng@aamusted.edu.gh',
        'research' => ['Ethics in IT', 'Software Engineering', 'Database Management']
    ],
    'Dr. Joseph Frank Gordon' => [
        'role' => 'Lecturer', 
        'image' => 'images/Dr.-Joseph-Gordon.png',
        'bio' => 'Dr. Joseph Frank Gordon is a dedicated lecturer with a passion for teaching and research in computer science.',
        'email' => 'jfgordon@aamusted.edu.gh',
        'research' => ['Computer Science Education', 'Programming', 'Algorithms']
    ],
    'Dr. Emmanuel Akweittey' => [
        'role' => 'Senior Lecturer', 
        'image' => 'images/AKWEITTEY-.jpg',
        'bio' => 'Dr. Emmanuel Akweittey is a Senior Lecturer with expertise in advanced computing concepts and methodologies.',
        'email' => 'eakweittey@aamusted.edu.gh',
        'research' => ['Advanced Computing', 'Machine Learning', 'Cybersecurity']
    ],
    'Dr. Ernest Larbi' => [
        'role' => 'Lecturer', 
        'image' => 'images/Mr.-Ernest-Larbi.png',
        'bio' => 'Dr. Ernest Larbi is a lecturer focused on practical IT skills and student development.',
        'email' => 'elarbi@aamusted.edu.gh',
        'research' => ['Web Technologies', 'Mobile Application Development', 'HCI']
    ],
    'Mr. Franco Osei-Wusu' => [
        'role' => 'Assistant Lecturer', 
        'image' => 'images/franco.png',
        'bio' => 'Mr. Franco Osei-Wusu is an Assistant Lecturer supporting the department in various academic and technical capacities.',
        'email' => 'foseiwusu@aamusted.edu.gh',
        'research' => ['Network Administration', 'System Analysis', 'Tech Support']
    ],
    'Mr. Kennedy Gyimah' => [
        'role' => 'Lecturer', 
        'image' => 'images/Kennedy-Gyimah.png',
        'bio' => 'Mr. Kennedy Gyimah is a Lecturer with expertise in Applied Mathematics, Machine Learning, and Computer Vision. He is dedicated to integrating technology into mathematical education.',
        'email' => 'kennedygyimah@aamusted.edu.gh',
        'research' => ['Applied Mathematics', 'Machine Learning', 'Computer Vision']
    ]
];

$name = isset($_GET['name']) ? urldecode($_GET['name']) : '';
$staff = isset($staff_members[$name]) ? $staff_members[$name] : null;

if (!$staff) {
    echo '<div class="container" style="padding: 100px 0; text-align: center;"><h2>Staff Member Not Found</h2><a href="department.php" class="btn-primary">Back to Department</a></div>';
    require_once 'includes/footer.php';
    exit;
}
?>

<div class="section" style="background: var(--light-bg);">
    <div class="container">
        <a href="department.php" style="display: inline-block; margin-bottom: 20px; color: var(--primary-color); font-weight: bold;">&larr; Back to Department</a>
        
        <div class="card" style="display: flex; flex-direction: column; md:flex-row; overflow: hidden;">
            <div style="display: flex; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 300px; max-width: 400px;">
                    <img src="<?php echo $staff['image']; ?>" alt="<?php echo htmlspecialchars($name); ?>" style="width: 100%; height: 100%; object-fit: cover; min-height: 400px;">
                </div>
                <div style="flex: 2; padding: 40px; min-width: 300px;">
                    <h1 style="color: var(--primary-color); margin-bottom: 10px;"><?php echo htmlspecialchars($name); ?></h1>
                    <h3 style="color: var(--secondary-color); margin-bottom: 20px;"><?php echo htmlspecialchars($staff['role']); ?></h3>
                    
                    <div style="margin-bottom: 30px;">
                        <h4 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">Biography</h4>
                        <p style="line-height: 1.8; color: #555;"><?php echo $staff['bio']; ?></p>
                    </div>

                    <div style="margin-bottom: 30px;">
                        <h4 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">Contact Information</h4>
                        <p><i class="fas fa-envelope" style="width: 25px; color: var(--primary-color);"></i> <a href="mailto:<?php echo $staff['email']; ?>"><?php echo $staff['email']; ?></a></p>
                        <p><i class="fas fa-map-marker-alt" style="width: 25px; color: var(--primary-color);"></i> Department of IT Education, AAMUSTED</p>
                    </div>

                    <?php if (!empty($staff['research'])): ?>
                    <div>
                        <h4 style="border-bottom: 2px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">Research Interests</h4>
                        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                            <?php foreach ($staff['research'] as $interest): ?>
                                <span style="background: #e9ecef; padding: 5px 15px; border-radius: 20px; font-size: 0.9rem; color: #495057;"><?php echo htmlspecialchars($interest); ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>