<?php
// ============================================
// login.php — Handle User Authentication
// ============================================

require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Invalid request method.');
}

$username = trim($_POST['username'] ?? '');
$password = trim($_POST['password'] ?? '');

if (empty($username) || empty($password)) {
    jsonResponse(false, 'Please enter username and password.');
}

$db = getDB();

$stmt = $db->prepare('SELECT id, username, password, role FROM users WHERE username = ?');
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    jsonResponse(false, 'Invalid username or password.');
}

$user = $result->fetch_assoc();

if (!password_verify($password, $user['password'])) {
    jsonResponse(false, 'Invalid username or password.');
}

// Set session
$_SESSION['user_id']  = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role']     = $user['role'];

// Redirect based on role
$redirect = match($user['role']) {
    'admin'   => 'admin_dashboard.php',
    'teacher' => 'index.html',
    default   => 'index.html',
};

jsonResponse(true, 'Login successful!', [
    'role'     => $user['role'],
    'username' => $user['username'],
    'redirect' => $redirect
]);

$db->close();
?>
