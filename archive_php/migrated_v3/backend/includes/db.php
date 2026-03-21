<?php
if (file_exists(__DIR__ . '/../../.env')) {
    $lines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '#') === 0 || strpos($line, '=') === false) {
            continue;
        }
        list($key, $value) = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if ($key !== '') {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
        }
    }
}

// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'infotess_sdms');

// Attempt to connect to MySQL database
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    // Set the PDO error mode to exception
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("ERROR: Could not connect. " . $e->getMessage());
}

// Start Session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'super_admin');
}

function isStudent() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'student';
}

function enforcePasswordReset() {
    if (isStudent() && isset($_SESSION['is_password_reset']) && $_SESSION['is_password_reset'] == 0) {
        $current_script = basename($_SERVER['SCRIPT_NAME']);
        if ($current_script !== 'password-reset.php' && $current_script !== 'logout.php') {
            // Need to determine the correct path to password-reset.php
            // If we are in student/ folder, it's just password-reset.php
            // If we are in root, it's student/password-reset.php
            if (strpos($_SERVER['SCRIPT_NAME'], '/student/') !== false) {
                redirect('password-reset.php');
            } else {
                redirect('student/password-reset.php');
            }
        }
    }
}

function redirect($url) {
    $url = (string)$url;
    $url = str_replace('login.php', 'login.html', $url);
    $url = str_replace('logout.php', 'logout.html', $url);
    header("Location: $url");
    exit;
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

function flash($name, $message = '', $class = 'success') {
    if (!empty($message)) {
        $_SESSION[$name] = $message;
        $_SESSION[$name . '_class'] = $class;
    } elseif (empty($message) && isset($_SESSION[$name])) {
        $class = !empty($_SESSION[$name . '_class']) ? $_SESSION[$name . '_class'] : 'success';
        echo '<div class="alert alert-' . $class . '">' . $_SESSION[$name] . '</div>';
        unset($_SESSION[$name]);
        unset($_SESSION[$name . '_class']);
    }
}
?>
