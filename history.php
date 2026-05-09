<?php
// ============================================
// history.php — Save / Fetch / Delete History
// ============================================

require_once 'config.php';
requireLogin();

$action = $_POST['action'] ?? $_GET['action'] ?? '';

$db = getDB();
$user_id = $_SESSION['user_id'];

// ── SAVE ─────────────────────────────────────
if ($action === 'save' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $text  = trim($_POST['text'] ?? '');
    if (empty($text)) jsonResponse(false, 'No text provided.');

    $words      = str_word_count($text);
    $difficulty = 'easy';
    if ($words >= 10 && $words < 20) $difficulty = 'medium';
    if ($words >= 20)                $difficulty = 'hard';

    $stmt = $db->prepare(
        'INSERT INTO reading_history (user_id, original_text, word_count, difficulty) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('isis', $user_id, $text, $words, $difficulty);

    if ($stmt->execute()) {
        jsonResponse(true, 'History saved.');
    } else {
        jsonResponse(false, 'Failed to save history.');
    }
}

// ── FETCH ─────────────────────────────────────
if ($action === 'fetch') {
    // Teachers/admins can see all; students see only their own
    if ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'teacher') {
        $stmt = $db->prepare(
            'SELECT rh.id, u.username, rh.original_text, rh.word_count, rh.difficulty, rh.created_at
             FROM reading_history rh
             JOIN users u ON rh.user_id = u.id
             ORDER BY rh.created_at DESC
             LIMIT 100'
        );
        $stmt->execute();
    } else {
        $stmt = $db->prepare(
            'SELECT id, original_text, word_count, difficulty, created_at
             FROM reading_history
             WHERE user_id = ?
             ORDER BY created_at DESC'
        );
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
    }

    $result = $stmt->get_result();
    $rows   = $result->fetch_all(MYSQLI_ASSOC);

    jsonResponse(true, 'OK', ['history' => $rows]);
}

// ── DELETE ONE ────────────────────────────────
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = intval($_POST['id'] ?? 0);

    // Admins can delete any; users only their own
    if ($_SESSION['role'] === 'admin') {
        $stmt = $db->prepare('DELETE FROM reading_history WHERE id = ?');
        $stmt->bind_param('i', $id);
    } else {
        $stmt = $db->prepare('DELETE FROM reading_history WHERE id = ? AND user_id = ?');
        $stmt->bind_param('ii', $id, $user_id);
    }

    $stmt->execute();
    jsonResponse(true, 'Deleted.');
}

// ── CLEAR ALL ─────────────────────────────────
if ($action === 'clear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($_SESSION['role'] === 'admin') {
        $db->query('DELETE FROM reading_history');
    } else {
        $stmt = $db->prepare('DELETE FROM reading_history WHERE user_id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
    }
    jsonResponse(true, 'All history cleared.');
}

jsonResponse(false, 'Unknown action.');
$db->close();
?>
