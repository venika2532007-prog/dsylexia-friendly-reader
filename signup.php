<?php
// ============================================
// signup.php — Handle User Registration
// ============================================

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');
$email    = trim($_POST['email'] ?? '');
$role     = trim($_POST['role'] ?? 'student');

// Validation
if (empty($username) || empty($password)) {
    jsonResponse(false, 'Username and password are required.');
}

if (strlen($password) < 4) {
    jsonResponse(false, 'Password must be at least 4 characters.');
}

// Only allow valid roles
$allowed_roles = ['student', 'teacher', 'admin'];
if (!in_array($role, $allowed_roles)) {
    $role = 'student';
}

$db = getDB();

// Check for duplicate username
$stmt = $db->prepare('SELECT id FROM users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    jsonResponse(false, 'Username already exists. Please choose another.');
}
$stmt->close();

// Hash password and insert user
$hashed = password_hash($password, PASSWORD_BCRYPT);

$stmt = $db->prepare('INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)');
$stmt->bind_param('ssss', $username, $hashed, $email, $role);

if ($stmt->execute()) {
    $new_id = $stmt->insert_id;

    // Create default settings for new user
    $def = $db->prepare('INSERT INTO user_settings (user_id) VALUES (?)');
    $def->bind_param('i', $new_id);
    $def->execute();

    jsonResponse(true, 'Account created successfully! Redirecting to login...');
} else {
    jsonResponse(false, 'Registration failed. Please try again.');
}

$db->close();
?>
