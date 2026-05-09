<?php
// ============================================
// config.php — DB Connection + Session Start
// ============================================

session_start();

define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Default XAMPP MySQL user
define('DB_PASS', '');            // Default XAMPP MySQL password (empty)
define('DB_NAME', 'dyslexia_reader');

function getDB() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die(json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conn->connect_error]));
    }
    $conn->set_charset('utf8mb4');
    return $conn;
}

// Helper: return JSON response
function jsonResponse($success, $message, $data = []) {
    header('Content-Type: application/json');
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data));
    exit;
}

// Helper: check if user is logged in
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.html');
        exit;
    }
}

// Helper: check role
function requireRole($role) {
    requireLogin();
    if ($_SESSION['role'] !== $role && $_SESSION['role'] !== 'admin') {
        die('<h2 style="color:red;text-align:center;margin-top:50px;">⛔ Access Denied: Insufficient permissions.</h2>');
    }
}
?>
