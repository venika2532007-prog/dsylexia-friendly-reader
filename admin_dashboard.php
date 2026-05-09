<?php
require_once 'config.php';
requireRole('admin');

$db = getDB();

// Fetch all users
$users_result = $db->query('SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC');
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Stats
$total_users    = $db->query('SELECT COUNT(*) as c FROM users')->fetch_assoc()['c'];
$total_history  = $db->query('SELECT COUNT(*) as c FROM reading_history')->fetch_assoc()['c'];
$total_students = $db->query("SELECT COUNT(*) as c FROM users WHERE role='student'")->fetch_assoc()['c'];
$total_teachers = $db->query("SELECT COUNT(*) as c FROM users WHERE role='teacher'")->fetch_assoc()['c'];

// Handle role update
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $uid  = intval($_POST['uid']);
    $role = $_POST['new_role'];
    if (in_array($role, ['admin','teacher','student'])) {
        $stmt = $db->prepare('UPDATE users SET role = ? WHERE id = ?');
        $stmt->bind_param('si', $role, $uid);
        $stmt->execute();
        $msg = 'Role updated successfully!';
        header('Location: admin_dashboard.php?msg=' . urlencode($msg));
        exit;
    }
}

// Handle user delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $uid = intval($_POST['uid']);
    if ($uid !== $_SESSION['user_id']) { // prevent self-delete
        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $msg = 'User deleted.';
        header('Location: admin_dashboard.php?msg=' . urlencode($msg));
        exit;
    }
}

$msg = $_GET['msg'] ?? '';
$db->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — Dyslexia Reader</title>
    <link href="https://fonts.googleapis.com/css2?family=DM+Serif+Display&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0f1117;
            --surface: #1a1d27;
            --surface2: #22263a;
            --border: #2e3347;
            --accent: #4f8ef7;
            --accent2: #7c5cfc;
            --green: #22c55e;
            --red: #ef4444;
            --yellow: #f59e0b;
            --text: #e8eaf0;
            --muted: #6b7280;
        }

        body {
            font-family: 'DM Sans', sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
        }

        header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 18px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        header h1 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.5rem;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-right {
            display: flex;
            align-items: center;
            gap: 16px;
            font-size: 0.9rem;
            color: var(--muted);
        }

        .badge {
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            color: white;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        a.logout {
            color: var(--red);
            text-decoration: none;
            font-weight: 500;
        }

        main { padding: 36px 40px; max-width: 1200px; margin: auto; }

        .toast {
            background: var(--green);
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            margin-bottom: 24px;
            font-weight: 500;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 18px;
            margin-bottom: 36px;
        }

        .stat-card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 14px;
            padding: 24px;
            text-align: center;
        }

        .stat-card .num {
            font-size: 2.5rem;
            font-family: 'DM Serif Display', serif;
            background: linear-gradient(135deg, var(--accent), var(--accent2));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .stat-card .label {
            color: var(--muted);
            font-size: 0.85rem;
            margin-top: 4px;
        }

        h2 {
            font-family: 'DM Serif Display', serif;
            font-size: 1.3rem;
            margin-bottom: 16px;
            color: var(--text);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: var(--surface);
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid var(--border);
        }

        thead { background: var(--surface2); }
        th, td { padding: 14px 18px; text-align: left; font-size: 0.9rem; }
        th { color: var(--muted); font-weight: 500; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.08em; }
        tr:not(:last-child) td { border-bottom: 1px solid var(--border); }

        .role-badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .role-admin   { background: rgba(79,142,247,0.15); color: var(--accent); }
        .role-teacher { background: rgba(245,158,11,0.15); color: var(--yellow); }
        .role-student { background: rgba(34,197,94,0.15);  color: var(--green); }

        select {
            background: var(--surface2);
            border: 1px solid var(--border);
            color: var(--text);
            padding: 6px 10px;
            border-radius: 8px;
            font-size: 0.85rem;
        }

        .btn {
            padding: 6px 14px;
            border: none;
            border-radius: 8px;
            font-size: 0.82rem;
            font-weight: 500;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.8; }
        .btn-blue { background: var(--accent); color: white; }
        .btn-red  { background: var(--red);   color: white; }
        .btn-back { background: var(--surface2); color: var(--text); border: 1px solid var(--border); text-decoration: none; padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; }
    </style>
</head>
<body>
<header>
    <h1>⚡ Admin Dashboard</h1>
    <div class="header-right">
        Logged in as <strong><?= htmlspecialchars($_SESSION['username']) ?></strong>
        <span class="badge">ADMIN</span>
        <a class="logout" href="logout.php">Logout</a>
    </div>
</header>

<main>
    <?php if ($msg): ?>
        <div class="toast">✅ <?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="stats">
        <div class="stat-card">
            <div class="num"><?= $total_users ?></div>
            <div class="label">Total Users</div>
        </div>
        <div class="stat-card">
            <div class="num"><?= $total_students ?></div>
            <div class="label">Students</div>
        </div>
        <div class="stat-card">
            <div class="num"><?= $total_teachers ?></div>
            <div class="label">Teachers</div>
        </div>
        <div class="stat-card">
            <div class="num"><?= $total_history ?></div>
            <div class="label">Total Reads</div>
        </div>
    </div>

    <!-- User Management Table -->
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <h2>👥 User Management</h2>
        <div style="display:flex;gap:10px;">
            <a class="btn-back" href="index.html">← Reader</a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Joined</th>
                <th>Change Role</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= $u['id'] ?></td>
                <td><?= htmlspecialchars($u['username']) ?></td>
                <td><?= htmlspecialchars($u['email'] ?: '—') ?></td>
                <td>
                    <span class="role-badge role-<?= $u['role'] ?>">
                        <?= strtoupper($u['role']) ?>
                    </span>
                </td>
                <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                <td>
                    <form method="POST" style="display:flex;gap:8px;align-items:center;">
                        <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                        <select name="new_role">
                            <option value="student" <?= $u['role']==='student'?'selected':'' ?>>Student</option>
                            <option value="teacher" <?= $u['role']==='teacher'?'selected':'' ?>>Teacher</option>
                            <option value="admin"   <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
                        </select>
                        <button class="btn btn-blue" name="update_role">Save</button>
                    </form>
                </td>
                <td>
                    <?php if ($u['id'] !== $_SESSION['user_id']): ?>
                    <form method="POST" onsubmit="return confirm('Delete this user?')">
                        <input type="hidden" name="uid" value="<?= $u['id'] ?>">
                        <button class="btn btn-red" name="delete_user">Delete</button>
                    </form>
                    <?php else: ?>
                    <span style="color:var(--muted);font-size:0.8rem;">You</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</main>
</body>
</html>
