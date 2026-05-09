<?php
// ============================================
// settings.php — Save / Load User Settings
// ============================================

require_once 'config.php';
requireLogin();

$action  = $_POST['action'] ?? $_GET['action'] ?? '';
$db      = getDB();
$user_id = $_SESSION['user_id'];

// ── SAVE ─────────────────────────────────────
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $font_size      = intval($_POST['font_size']      ?? 18);
    $letter_spacing = floatval($_POST['letter_spacing'] ?? 1.0);
    $line_height    = floatval($_POST['line_height']    ?? 1.5);
    $bg_color       = preg_replace('/[^#a-fA-F0-9]/', '', $_POST['bg_color'] ?? '#ffffff');

    // Upsert (insert or update)
    $stmt = $db->prepare(
        'INSERT INTO user_settings (user_id, font_size, letter_spacing, line_height, bg_color)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE
           font_size = VALUES(font_size),
           letter_spacing = VALUES(letter_spacing),
           line_height = VALUES(line_height),
           bg_color = VALUES(bg_color)'
    );
    $stmt->bind_param('iidds', $user_id, $font_size, $letter_spacing, $line_height, $bg_color);

    if ($stmt->execute()) {
        jsonResponse(true, 'Settings saved successfully!');
    } else {
        jsonResponse(false, 'Failed to save settings.');
    }
}

// ── LOAD ─────────────────────────────────────
if ($action === 'load') {
    $stmt = $db->prepare('SELECT font_size, letter_spacing, line_height, bg_color FROM user_settings WHERE user_id = ?');
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $settings = $result->fetch_assoc();
        jsonResponse(true, 'OK', ['settings' => $settings]);
    } else {
        // Return defaults
        jsonResponse(true, 'defaults', ['settings' => [
            'font_size'      => 18,
            'letter_spacing' => 1.0,
            'line_height'    => 1.5,
            'bg_color'       => '#ffffff'
        ]]);
    }
}

jsonResponse(false, 'Unknown action.');
$db->close();
?>
