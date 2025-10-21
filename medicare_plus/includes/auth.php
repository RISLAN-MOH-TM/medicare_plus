<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Check user role
function hasRole($role) {
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        // Get the relative path to login.php from current file
        $path_parts = explode('/', $_SERVER['REQUEST_URI']);
        $depth = count(array_filter($path_parts)) - 2; // -2 for domain and current file
        $prefix = str_repeat('../', max(0, $depth));
        header('Location: ' . $prefix . 'login.php');
        exit();
    }
}

// Require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        // Get the relative path to index.php from current file
        $path_parts = explode('/', $_SERVER['REQUEST_URI']);
        $depth = count(array_filter($path_parts)) - 2;
        $prefix = str_repeat('../', max(0, $depth));
        header('Location: ' . $prefix . 'index.php');
        exit();
    }
}

// Login user
function loginUser($user_id, $username, $email, $role) {
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $username;
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $role;
    $_SESSION['logged_in_time'] = time();
}

// Logout user
function logoutUser() {
    session_unset();
    session_destroy();
    // Get the relative path to index.php from current file
    $path_parts = explode('/', $_SERVER['REQUEST_URI']);
    $depth = count(array_filter($path_parts)) - 2;
    $prefix = str_repeat('../', max(0, $depth));
    header('Location: ' . $prefix . 'index.php');
    exit();
}

// Get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Get current user role
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Hash password
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

// Verify password
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

// Generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Display error message
function setErrorMessage($message) {
    $_SESSION['error_message'] = $message;
}

// Display success message
function setSuccessMessage($message) {
    $_SESSION['success_message'] = $message;
}

// Get and clear error message
function getErrorMessage() {
    if (isset($_SESSION['error_message'])) {
        $message = $_SESSION['error_message'];
        unset($_SESSION['error_message']);
        return $message;
    }
    return null;
}

// Get and clear success message
function getSuccessMessage() {
    if (isset($_SESSION['success_message'])) {
        $message = $_SESSION['success_message'];
        unset($_SESSION['success_message']);
        return $message;
    }
    return null;
}
?>
