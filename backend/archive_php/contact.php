<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Process contact form
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $subject = sanitize($_POST['subject'] ?? 'No Subject');
    $msg = sanitize($_POST['message']);
    
    // Save to database
    try {
        $stmt = $pdo->prepare("INSERT INTO contact_submissions (name, email, subject, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $email, $subject, $msg]);
        $message = "Thank you for contacting us, $name. Your message has been received and we will get back to you shortly.";
    } catch (PDOException $e) {
        $message = "Sorry, there was an error sending your message. Please try again later.";
    }
}
?>

<div class="hero" style="height: 40vh;">
    <h1>Contact Us</h1>
    <p>We'd love to hear from you. Get in touch with our team.</p>
</div>

<div class="section">
    <div class="container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px;">
            <div class="card">
                <h3>Send us a Message</h3>
                <form method="POST" action="" style="margin-top: 20px;">
                    <div class="form-group">
                        <label for="name">Full Name</label>
                        <input type="text" id="name" name="name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <input type="text" id="subject" name="subject" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="message">Your Message</label>
                        <textarea id="message" name="message" class="form-control" rows="5" required></textarea>
                    </div>
                    <button type="submit" class="btn-primary">Send Message</button>
                </form>
            </div>
            
            <div class="card">
                <h3>Contact Information</h3>
                <div style="margin-top: 20px;">
                    <p><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong><br>
                    Department of Information Technology Education (DITE)<br>
                    Faculty of Applied Sciences and Mathematics Education (FASME)<br>
                    AAMUSTED, Kumasi Campus</p>
                    
                    <p style="margin-top: 20px;"><i class="fas fa-phone"></i> <strong>Phone:</strong><br>
                    +233 24 091 8031</p>
                    
                    <p style="margin-top: 20px;"><i class="fas fa-envelope"></i> <strong>Email:</strong><br>
                    info@infotess.org</p>
                    
                    <div style="margin-top: 30px;">
                        <h3>Office Hours</h3>
                        <p>Monday - Friday: 8:00 AM - 5:00 PM<br>
                        Saturday & Sunday: Closed</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>