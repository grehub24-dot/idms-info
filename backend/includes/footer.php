    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About INFOTESS</h3>
                    <p>The Information Technology Students’ Society (INFOTESS) of AAMUSTED is dedicated to promoting technology education and student welfare.</p>
                </div>
                <div class="footer-section">
                    <?php $base_url = '/Infotess/'; ?>
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="<?php echo $base_url; ?>index.php">Home</a></li>
                        <li><a href="<?php echo $base_url; ?>about.php">About Us</a></li>
                        <li><a href="<?php echo $base_url; ?>events.php">Events</a></li>
                        <li><a href="<?php echo $base_url; ?>contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> AAMUSTED, Kumasi Campus</p>
                    <p><i class="fas fa-envelope"></i> info@infotess.org</p>
                    <p><i class="fas fa-phone"></i> +233 123 456 789</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> INFOTESS. All Rights Reserved.</p>
            </div>
        </div>
    </footer>

    <!-- JS -->
    <script src="<?php echo $base_url; ?>js/main.js"></script>
</body>
</html>
