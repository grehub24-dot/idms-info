<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INFOTESS - SDMS</title>
    <!-- CSS -->
    <?php
    $base_url = '/Infotess/';
    ?>
    <link rel="stylesheet" href="<?php echo $base_url; ?>css/style.css">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="<?php echo $base_url; ?>index.php" class="logo">
                <img src="<?php echo $base_url; ?>images/infotess.png" alt="INFOTESS Logo" height="40"> INFOTESS
            </a>
            <ul class="nav-links">
                <li><a href="<?php echo $base_url; ?>index.php">Home</a></li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">About <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="<?php echo $base_url; ?>about.php">About INFOTESS</a>
                        <a href="<?php echo $base_url; ?>department.php">Department</a>
                        <a href="<?php echo $base_url; ?>executives.php">Executives</a>
                        <a href="<?php echo $base_url; ?>alumni.php">Alumni</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Activities <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="<?php echo $base_url; ?>activities.php">Activities</a>
                        <a href="<?php echo $base_url; ?>events.php">Events</a>
                        <a href="<?php echo $base_url; ?>projects.php">Projects</a>
                        <a href="<?php echo $base_url; ?>gallery.php">Gallery</a>
                    </div>
                </li>
                <li class="dropdown">
                    <a href="#" class="dropbtn">Resources <i class="fas fa-caret-down"></i></a>
                    <div class="dropdown-content">
                        <a href="<?php echo $base_url; ?>fees.php">Fees & Payments</a>
                        <a href="<?php echo $base_url; ?>resources.php">Student Resources</a>
                        <a href="<?php echo $base_url; ?>membership.php">Membership</a>
                    </div>
                </li>
                <li><a href="<?php echo $base_url; ?>news.php">News</a></li>
                <li><a href="<?php echo $base_url; ?>contact.php">Contact</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin'): ?>
                        <li><a href="<?php echo $base_url; ?>admin/dashboard.html" class="btn-login">Admin Panel</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $base_url; ?>student/dashboard.html" class="btn-login">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo $base_url; ?>logout.html">Logout</a></li>
                <?php else: ?>
                    <li><a href="<?php echo $base_url; ?>login.html" class="btn-login">Login</a></li>
                <?php endif; ?>
            </ul>
            <div class="hamburger">
                <i class="fas fa-bars"></i>
            </div>
        </div>
    </nav>
