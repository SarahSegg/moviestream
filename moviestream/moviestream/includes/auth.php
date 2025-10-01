<?php
// Enhanced authentication functions

// Simple user authentication
if (!function_exists('authenticate_user')) {
    function authenticate_user($username, $password) {
        global $pdo;
        
        $stmt = $pdo->prepare("SELECT user_id, username, password, is_admin FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            return $user;
        }
        return false;
    }
}

// Login function
if (!function_exists('login')) {
    function login($user_id, $username, $is_admin = false) {
        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['is_admin'] = $is_admin;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
    }
}

// Logout function
if (!function_exists('logout')) {
    function logout() {
        session_unset();
        session_destroy();
        session_start(); // Start fresh session for flash messages
    }
}

// Check if user is logged in
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
}

// Get current user ID
if (!function_exists('get_user_id')) {
    function get_user_id() {
        return $_SESSION['user_id'] ?? 0;
    }
}

// Get current username
if (!function_exists('get_username')) {
    function get_username() {
        return $_SESSION['username'] ?? '';
    }
}

// Check if user is admin
if (!function_exists('is_admin')) {
    function is_admin() {
        return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
    }
}

// Require login - redirect if not logged in
if (!function_exists('require_login')) {
    function require_login() {
        if (!is_logged_in()) {
            $_SESSION['redirect_to'] = $_SERVER['REQUEST_URI'];
            header('Location: login.php');
            exit;
        }
    }
}

// Require admin privileges
if (!function_exists('require_admin')) {
    function require_admin() {
        require_login();
        if (!is_admin()) {
            flash("Access denied. Administrator privileges required.", "error");
            header('Location: ../index.php');
            exit;
        }
    }
}

// Check if user can access admin area
if (!function_exists('can_access_admin')) {
    function can_access_admin() {
        return is_logged_in() && is_admin();
    }
}

// Generate CSRF token
if (!function_exists('generate_csrf_token')) {
    function generate_csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

// Validate CSRF token
if (!function_exists('validate_csrf_token')) {
    function validate_csrf_token($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Check for brute force protection
if (!function_exists('check_brute_force')) {
    function check_brute_force($username) {
        global $pdo;
        
        $now = time();
        $valid_attempts = $now - (2 * 60 * 60); // 2 hours
        
        $stmt = $pdo->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE username = ? AND time > ?");
        $stmt->execute([$username, $valid_attempts]);
        $result = $stmt->fetch();
        
        return ($result['attempts'] >= 5); // More than 5 attempts in 2 hours
    }
}

// Record login attempt
if (!function_exists('record_login_attempt')) {
    function record_login_attempt($username, $success) {
        global $pdo;
        
        $stmt = $pdo->prepare("INSERT INTO login_attempts (username, time, success) VALUES (?, ?, ?)");
        $stmt->execute([$username, time(), $success ? 1 : 0]);
    }
}

// Clean old login attempts
if (!function_exists('clean_old_attempts')) {
    function clean_old_attempts() {
        global $pdo;
        
        $two_hours_ago = time() - (2 * 60 * 60);
        $pdo->prepare("DELETE FROM login_attempts WHERE time < ?")->execute([$two_hours_ago]);
    }
}
?>