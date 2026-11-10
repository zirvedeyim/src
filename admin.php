<?php
session_start();

$admin_user = "zirvedeyim0";
$admin_pass = "wear5555.2013a";

$json_file = "keys.json";
$backup_file = "keys_backup.json";

if (!file_exists($json_file) && file_exists($backup_file)) {
    copy($backup_file, $json_file);
}
if (!file_exists($json_file)) {
    file_put_contents($json_file, json_encode([]));
}

$keys_data = json_decode(file_get_contents($json_file), true);
if (!is_array($keys_data)) $keys_data = [];

if (empty($keys_data) && file_exists($backup_file)) {
    $backup_data = json_decode(file_get_contents($backup_file), true);
    if (is_array($backup_data) && count($backup_data) > 0) {
        $keys_data = $backup_data;
        file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
    }
}

$data_changed = false;
foreach ($keys_data as $k => $v) {
    if (!isset($v['status'])) { $keys_data[$k]['status'] = 'active'; $data_changed = true; }
    if (!isset($v['expire'])) { $keys_data[$k]['expire'] = 0; $data_changed = true; }
    if (!isset($v['max_devices'])) { $keys_data[$k]['max_devices'] = 1; $data_changed = true; }
    if (!isset($v['devices']) || !is_array($v['devices'])) { $keys_data[$k]['devices'] = []; $data_changed = true; }
    if (!isset($v['note'])) { $keys_data[$k]['note'] = ''; $data_changed = true; }
}
if ($data_changed) {
    file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
    file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    unset($_SESSION['logged_in']);
    header("Location: admin.php");
    exit;
}

if (isset($_POST['login'])) {
    if ($_POST['username'] === $admin_user && $_POST['password'] === $admin_pass) {
        $_SESSION['logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $login_error = "Invalid username or password!";
    }
}

// GİRİŞ EKRANI TASARIMI
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Login — DARK IOS</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
            body {
                background-color: #050505;
                background-image: radial-gradient(circle at 50% 0%, #1a1a2e 0%, #000 80%);
                height: 100vh; display: flex; justify-content: center; align-items: center; color: #fff;
            }
            .login-box {
                background: rgba(20, 20, 25, 0.7);
                backdrop-filter: blur(20px); -webkit-backdrop-filter: blur(20px);
                padding: 50px 40px; border-radius: 24px; width: 100%; max-width: 420px;
                border: 1px solid rgba(255,255,255,0.05);
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
                text-align: center;
            }
            .login-box h2 { font-size: 26px; font-weight: 700; margin-bottom: 8px; letter-spacing: 1px; }
            .login-box .sub { color: #888; font-size: 14px; margin-bottom: 30px; }
            .input-group { margin-bottom: 20px; text-align: left; }
            .input-group label { display: block; font-size: 12px; font-weight: 600; color: #aaa; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 0.5px;}
            .input-group input {
                width: 100%; padding: 16px; background: rgba(0,0,0,0.4); border: 1px solid rgba(255,255,255,0.1);
                color: #fff; border-radius: 12px; font-size: 15px; outline: none; transition: all 0.3s;
            }
            .input-group input:focus { border-color: #60a5fa; background: rgba(0,0,0,0.6); box-shadow: 0 0 15px rgba(96,165,250,0.1); }
            button.btn-login {
                width: 100%; padding: 16px; margin-top: 10px; background: #fff; color: #000;
                border: none; font-weight: 700; font-size: 15px; border-radius: 12px; cursor: pointer;
                transition: transform 0.2s, box-shadow 0.2s;
            }
            button.btn-login:hover { box-shadow: 0 0 20px rgba(255,255,255,0.2); transform: scale(1.02); }
            .error { color: #ff4444; font-size: 13px; background: rgba(255, 68, 68, 0.1); padding: 12px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(255, 68, 68, 0.2); }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>DARK IOS</h2>
            <div class="sub">Sign in to Admin Panel</div>
            <?php if(isset($login_error)) echo "<div class='error'>$login_error</div>"; ?>
            <form method="POST">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter username" required autofocus>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Enter password" required>
                </div>
                <button type="submit" name="login" class="btn-login">Sign In</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_key'])) {
        $duration_type = $_POST['duration_type'];
        $note = htmlspecialchars($_POST['note']);
        $custom_key = trim($_POST['custom_key']);
        $max_devices = intval($_POST['max_devices']);
        if ($max_devices < 1) $max_devices = 1;

        $now = time();
        $expire_time = $now + 86400;
        $prefix = "DARKIOS-1D-";

        if ($duration_type == '1D') { $expire_time = $now + 86400; $prefix = "DARKIOS-1D-"; }
        elseif ($duration_type == '7D') { $expire_time = $now + (7 * 86400); $prefix = "DARKIOS-7D-"; }
        elseif ($duration_type == '30D') { $expire_time = $now + (30 * 86400); $prefix = "DARKIOS-30D-"; }
        elseif ($duration_type == 'LIFETIME') { $expire_time = 0; $prefix = "DARKIOS-LIFETIME-"; }
        elseif ($duration_type == 'TEST_1D') { $expire_time = $now + 86400; $prefix = "DARKIOS-TESTERKEY-"; }
        elseif ($duration_type == 'TEST_7D') { $expire_time = $now + (7 * 86400); $prefix = "DARKIOS-TESTERKEY-"; }
        elseif ($duration_type == 'TEST_30D') { $expire_time = $now + (30 * 86400); $prefix = "DARKIOS-TESTERKEY-"; }
        elseif ($duration_type == 'TEST_LIFETIME') { $expire_time = 0; $prefix = "DARKIOS-TESTERKEY-"; }

        $random_part = strtoupper(substr(bin2hex(random_bytes(8)), 0, 15));
        
        $key = !empty($custom_key) ? $custom_key : $prefix . $random_part;
        $keys_data[$key] = [
            "note" => $note,
            "expire" => $expire_time,
            "max_devices" => $max_devices,
            "devices" => [],
            "status" => "active"
        ];
        file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        header("Location: admin.php");
        exit;
    }

    if (isset($_POST['action_key'])) {
        $k = $_POST['key_id'];
        $act = $_POST['sub_action'];
        if (isset($keys_data[$k])) {
            if ($act == 'delete') { unset($keys_data[$k]); }
            elseif ($act == 'block') { $keys_data[$k]['status'] = ($keys_data[$k]['status'] == 'banned') ? 'active' : 'banned'; }
            elseif ($act == 'reset_hwid') { $keys_data[$k]['devices'] = []; }
            elseif ($act == 'add_time') { if ($keys_data[$k]['expire'] > 0) $keys_data[$k]['expire'] += 86400; }
            elseif ($act == 'limit_inc') { $keys_data[$k]['max_devices']++; }
            file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
            file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        }
        header("Location: admin.php");
        exit;
    }

    if (isset($_POST['delete_all'])) {
        $keys_data = [];
        file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        header("Location: admin.php");
        exit;
    }
}

function key_status_badge($v) {
    if ($v['status'] == 'banned') return '<span class="badge badge-danger">Banned</span>';
    if ($v['expire'] > 0 && $v['expire'] < time()) return '<span class="badge badge-muted">Expired</span>';
    return '<span class="badge badge-success">Active</span>';
}

function key_time_left($v) {
    if ($v['expire'] == 0) return '<span style="color:#a78bfa;">Lifetime</span>';
    $diff = $v['expire'] - time();
    if ($diff <= 0) return '<span style="color:#ef4444;">Expired</span>';
    $days = ceil($diff / 86400);
    return '<span style="color:#9ca3af;">' . $days . ' days left</span>';
}
?>

<!-- ANA PANEL TASARIMI -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DARK IOS — Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #050505; --panel: #0f0f13; --border: rgba(255,255,255,0.08);
            --text: #ffffff; --text-dim: #888; --accent: #60a5fa;
            --success: #10b981; --danger: #ef4444; --warning: #f59e0b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background-color: var(--bg); color: var(--text); display: flex; height: 100vh; overflow: hidden; }
        
        /* Yan Menü (Sidebar) */
        .sidebar { width: 260px; background: var(--panel); border-right: 1px solid var(--border); display: flex; flex-direction: column; padding: 30px 20px; }
        .logo-area { display: flex; align-items: center; gap: 12px; margin-bottom: 40px; padding: 0 10px;}
        .logo-icon { width: 36px; height: 36px; background: linear-gradient(135deg, #60a5fa, #a78bfa); border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; color: #000; }
        .logo-text { font-size: 20px; font-weight: 700; letter-spacing: 1px; }
        .nav-item { padding: 14px 16px; border-radius: 12px; color: var(--text-dim); font-size: 14px; font-weight: 500; cursor: pointer; margin-bottom: 8px; transition: all 0.2s; display: flex; align-items: center; gap: 10px; }
        .nav-item:hover { background: rgba(255,255,255,0.05); color: var(--text); }
        .nav-item.active { background: rgba(96, 165, 250, 0.1); color: var(--accent); border: 1px solid rgba(96, 165, 250, 0.2); }
        
        /* Ana İçerik */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; background: radial-gradient(circle at 100% 0%, #111 0%, #050505 50%); }
        .topbar { height: 80px; border-bottom: 1px solid var(--border); display: flex; align-items: center; justify-content: space-between; padding: 0 40px; backdrop-filter: blur(10px); }
        .breadcrumbs { font-size: 14px; color: var(--text-dim); font-weight: 500; }
        .breadcrumbs span { color: var(--text); }
        
        .topbar-actions { display: flex; align-items: center; gap: 16px; }
        .search-box input { padding: 12px 20px; border-radius: 10px; border: 1px solid var(--border); background: var(--panel); color: var(--text); font-size: 14px; width: 250px; outline: none; transition: all 0.3s; }
        .search-box input:focus { border-color: var(--accent); box-shadow: 0 0 10px rgba(96, 165, 250, 0.1); }
        .btn-logout { padding: 12px 20px; border-radius: 10px; background: rgba(255,255,255,0.05); border: 1px solid var(--border); color: var(--text); font-size: 14px; font-weight: 600; cursor: pointer; text-decoration: none; transition: all 0.2s; }
        .btn-logout:hover { background: rgba(239, 68, 68, 0.1); color: var(--danger); border-color: rgba(239, 68, 68, 0.3); }

        .content { padding: 40px; overflow-y: auto; flex: 1; }
        
        /* Form Kartı */
        .form-card { background: var(--panel); border: 1px solid var(--border); border-radius: 16px; padding: 30px; margin-bottom: 30px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .form-card h3 { margin-bottom: 20px; font-size: 18px; font-weight: 600; color: #fff; }
        .form-row { display: flex; gap: 16px; flex-wrap: wrap; margin-bottom: 16px; }
        .form-row > * { flex: 1; min-width: 200px; }
        input, select { width: 100%; padding: 14px 16px; background: rgba(0,0,0,0.3); border: 1px solid var(--border); color: var(--text); border-radius: 10px; font-size: 14px; outline: none; transition: border 0.2s; }
        input:focus, select:focus { border-color: var(--accent); }
        select option { background: var(--panel); }
        
        button.btn-primary { padding: 14px 24px; background: var(--accent); color: #000; border: none; border-radius: 10px; font-weight: 600; font-size: 14px; cursor: pointer; width: 100%; transition: all 0.2s; }
        button.btn-primary:hover { filter: brightness(1.1); transform: translateY(-1px); box-shadow: 0 5px 15px rgba(96,165,250,0.3); }

        /* Tablo */
        .table-container { border: 1px solid var(--border); border-radius: 16px; background: var(--panel); overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 18px 24px; border-bottom: 1px solid var(--border); color: var(--text-dim); font-weight: 500; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;}
        td { padding: 18px 24px; border-bottom: 1px solid var(--border); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        
        /* Durum Etiketleri (Badges) */
        .badge { padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-success { background: rgba(16, 185, 129, 0.1); color: var(--success); border: 1px solid rgba(16, 185, 129, 0.2); }
        .badge-danger { background: rgba(239, 68, 68, 0.1); color: var(--danger); border: 1px solid rgba(239, 68, 68, 0.2); }
        .badge-muted { background: rgba(255,255,255,0.05); color: var(--text-dim); border: 1px solid var(--border); }

        /* Aksiyon Butonları */
        .action-btns { display: flex; gap: 8px; flex-wrap: wrap; }
        .action-btns form { display: inline; }
        .action-btns button { padding: 8px 12px; font-size: 12px; border-radius: 8px; border: 1px solid var(--border); background: rgba(255,255,255,0.03); color: var(--text-dim); cursor: pointer; transition: all 0.2s; font-weight: 500;}
        .action-btns button:hover { background: rgba(255,255,255,0.08); color: var(--text); }
        .action-btns .btn-danger { color: #ff6b6b; border-color: rgba(239,68,68,0.2); }
        .action-btns .btn-danger:hover { background: rgba(239,68,68,0.1); }
        .action-btns .btn-warning { color: var(--warning); border-color: rgba(245,158,11,0.2); }
        .action-btns .btn-warning:hover { background: rgba(245,158,11,0.1); }
        .action-btns .btn-success { color: var(--success); border-color: rgba(16,185,129,0.2); }
        .action-btns .btn-success:hover { background: rgba(16,185,129,0.1); }
        
        .key-code { font-family: monospace; font-weight: 600; color: var(--accent); font-size: 15px; }
        .key-note { color: var(--text-dim); font-size: 12px; margin-top: 6px; }
        
        /* Modal */
        #settingsModal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center; backdrop-filter: blur(5px); }
        .modal-content { background: var(--panel); padding: 40px; border-radius: 20px; width: 400px; border: 1px solid var(--border); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
        .modal-content h3 { font-size: 20px; margin-bottom: 16px; }
        .modal-content p { font-size: 14px; color: var(--text-dim); margin-bottom: 24px; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="logo-area">
            <div class="logo-icon">D</div>
            <div class="logo-text">DARK IOS</div>
        </div>
        <a href="#" class="nav-item active">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"></path></svg>
            API Keys
        </a>
        <a href="#" class="nav-item" onclick="document.getElementById('settingsModal').style.display='flex'">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
            Settings
        </a>
    </div>

    <div class="main-content">
        <div class="topbar">
            <div class="breadcrumbs">Dashboard / <span>API Keys</span></div>
            <div class="topbar-actions">
                <div class="search-box">
                    <input type="text" id="searchInput" placeholder="Search keys or notes..." onkeyup="filterKeys()">
                </div>
                <a href="admin.php?action=logout" class="btn-logout">Logout</a>
            </div>
        </div>

        <div class="content">
            <!-- Yeni Key Oluşturma -->
            <div class="form-card">
                <h3>Create New Key</h3>
                <form method="POST">
                    <div class="form-row">
                        <input type="text" name="note" placeholder="Note (username, etc.)">
                        <input type="number" name="max_devices" value="1" min="1" placeholder="Max Devices">
                    </div>
                    <div class="form-row">
                        <select name="duration_type">
                            <option value="1D">1 Day</option>
                            <option value="7D">7 Days</option>
                            <option value="30D">30 Days</option>
                            <option value="LIFETIME">Lifetime</option>
                            <option disabled>──────────</option>
                            <option value="TEST_1D">1 Day (Tester)</option>
                            <option value="TEST_7D">7 Days (Tester)</option>
                            <option value="TEST_30D">30 Days (Tester)</option>
                            <option value="TEST_LIFETIME">Lifetime (Tester)</option>
                        </select>
                        <input type="text" name="custom_key" placeholder="Custom Key (optional)">
                    </div>
                    <button type="submit" name="create_key" class="btn-primary">+ Generate Key</button>
                </form>
            </div>

            <!-- Key Listesi -->
            <div class="table-container">
                <?php if (count($keys_data) === 0): ?>
                    <div style="text-align:center; padding:60px; color:var(--text-dim); font-size: 15px;">No keys generated yet. Create your first key above.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>License Key</th>
                                <th>Status</th>
                                <th>Duration</th>
                                <th>Devices</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="keysContainer">
                            <?php foreach($keys_data as $k => $v): ?>
                                <?php $is_banned = ($v['status'] == 'banned'); ?>
                                <tr class="key-row" data-key="<?= htmlspecialchars(strtolower($k)) ?>" data-note="<?= htmlspecialchars(strtolower($v['note'])) ?>">
                                    <td>
                                        <div class="key-code"><?= htmlspecialchars($k) ?></div>
                                        <?php if(!empty($v['note'])): ?>
                                            <div class="key-note"><?= htmlspecialchars($v['note']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= key_status_badge($v) ?></td>
                                    <td><?= key_time_left($v) ?></td>
                                    <td><span style="font-weight:600; color:#fff;"><?= count($v['devices']) ?></span> / <?= $v['max_devices'] ?></td>
                                    <td>
                                        <div class="action-btns">
                                            <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="reset_hwid"><button type="submit" name="action_key" class="btn-warning">Reset HWID</button></form>
                                            <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="block"><button type="submit" name="action_key" class="btn-danger"><?= $is_banned ? 'Unban' : 'Ban' ?></button></form>
                                            <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="add_time"><button type="submit" name="action_key" class="btn-success">+1 Day</button></form>
                                            <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="limit_inc"><button type="submit" name="action_key" class="btn-warning">+Dev</button></form>
                                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this key?');"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="delete"><button type="submit" name="action_key" class="btn-danger">Delete</button></form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="noResults" style="display:none; text-align:center; padding:40px; color:var(--text-dim); font-size: 15px;">No keys found matching your search.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Settings Modal -->
    <div id="settingsModal">
        <div class="modal-content">
            <h3>Settings</h3>
            <p>Manage your application settings and data here.</p>
            
            <div style="border-top:1px solid var(--border); padding-top:24px;">
                <h4 style="margin-bottom:12px; font-size:14px; color:var(--danger);">Danger Zone</h4>
                <p style="margin-bottom:16px;">This will permanently delete all keys and reset the system.</p>
                <form method="POST" onsubmit="return confirm('Are you absolutely sure? This will delete ALL keys!');">
                    <button type="submit" name="delete_all" style="background:rgba(239, 68, 68, 0.1); color:var(--danger); border:1px solid rgba(239, 68, 68, 0.2); padding:12px 20px; border-radius:10px; font-weight:600; cursor:pointer; width:100%; transition:all 0.2s;">Reset All Keys</button>
                </form>
            </div>

            <div style="margin-top:24px; text-align:right;">
                <button type="button" onclick="document.getElementById('settingsModal').style.display='none'" style="background:rgba(255,255,255,0.05); border:1px solid var(--border); color:#fff; padding:10px 20px; border-radius:10px; cursor:pointer; font-weight:500;">Close</button>
            </div>
        </div>
    </div>

    <script>
        function filterKeys() {
            var input = document.getElementById('searchInput');
            var filter = input.value.toLowerCase();
            var rows = document.querySelectorAll('.key-row');
            var visibleCount = 0;
            rows.forEach(function(row) {
                var keyName = row.getAttribute('data-key');
                var noteName = row.getAttribute('data-note');
                if (keyName.includes(filter) || noteName.includes(filter)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
            var noRes = document.getElementById('noResults');
            if(noRes) {
                if (visibleCount === 0 && rows.length > 0) {
                    noRes.style.display = 'block';
                } else {
                    noRes.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html>
