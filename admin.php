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

// GİRİŞ EKRANI (Yine şık ama biraz farklı)
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
            body { background: #0f172a; display: flex; justify-content: center; align-items: center; height: 100vh; color: #fff; }
            .login-box { background: #1e293b; padding: 40px; border-radius: 16px; width: 100%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.5); border: 1px solid #334155; }
            .login-box h2 { text-align: center; margin-bottom: 24px; font-weight: 600; color: #e2e8f0; }
            .input-group { margin-bottom: 16px; }
            .input-group label { display: block; font-size: 13px; color: #94a3b8; margin-bottom: 6px; }
            .input-group input { width: 100%; padding: 12px; background: #0f172a; border: 1px solid #334155; border-radius: 8px; color: #fff; outline: none; transition: 0.2s; }
            .input-group input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
            button.btn-login { width: 100%; padding: 12px; background: #3b82f6; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; transition: 0.2s; }
            button.btn-login:hover { background: #2563eb; }
            .error { color: #ef4444; font-size: 13px; text-align: center; margin-bottom: 16px; background: rgba(239,68,68,0.1); padding: 10px; border-radius: 6px; }
        </style>
    </head>
    <body>
        <div class="login-box">
            <h2>DARK IOS</h2>
            <?php if(isset($login_error)) echo "<div class='error'>$login_error</div>"; ?>
            <form method="POST">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" required autofocus>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="password" name="password" required>
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
    if ($v['status'] == 'banned') return '<span class="status-badge status-banned">Banned</span>';
    if ($v['expire'] > 0 && $v['expire'] < time()) return '<span class="status-badge status-expired">Expired</span>';
    return '<span class="status-badge status-active">Active</span>';
}

function key_time_left($v) {
    if ($v['expire'] == 0) return '<span style="color:#a78bfa; font-weight:600;">Lifetime</span>';
    $diff = $v['expire'] - time();
    if ($diff <= 0) return '<span style="color:#ef4444; font-weight:600;">Expired</span>';
    $days = ceil($diff / 86400);
    return '<span style="color:#9ca3af;">' . $days . ' days left</span>';
}
?>

<!-- ANA PANEL: YATAY MENÜ VE YENİ TASARIM -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DARK IOS Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: #0b0f19; color: #fff; display: flex; flex-direction: column; min-height: 100vh; }

        /* YATAY MENÜ (Üst Bar) */
        .navbar { background: #111827; border-bottom: 1px solid #1f2937; display: flex; align-items: center; justify-content: space-between; padding: 0 30px; height: 70px; position: sticky; top: 0; z-index: 100; }
        .nav-brand { display: flex; align-items: center; gap: 12px; font-size: 20px; font-weight: 700; color: #fff; letter-spacing: 1px; }
        .nav-brand .brand-icon { background: #3b82f6; color: #fff; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; font-size: 16px; }
        
        .nav-links { display: flex; gap: 8px; }
        .nav-link { padding: 10px 16px; border-radius: 8px; font-size: 14px; font-weight: 500; color: #9ca3af; text-decoration: none; cursor: pointer; transition: 0.2s; display: flex; align-items: center; gap: 8px; }
        .nav-link:hover, .nav-link.active { background: #1f2937; color: #fff; }
        .nav-link.active { color: #3b82f6; }
        
        .nav-actions { display: flex; align-items: center; gap: 16px; }
        .btn-logout { padding: 10px 18px; border-radius: 8px; background: #ef4444; color: #fff; text-decoration: none; font-size: 14px; font-weight: 600; transition: 0.2s; }
        .btn-logout:hover { background: #dc2626; }

        /* ANA İÇERİK ALANI */
        .container { max-width: 1400px; margin: 30px auto; padding: 0 20px; width: 100%; display: flex; gap: 24px; align-items: flex-start; flex-wrap: wrap; }
        
        /* SOL TARAF: KEY OLUŞTURMA FORMU */
        .sidebar-form { flex: 1; min-width: 300px; max-width: 400px; background: #111827; border: 1px solid #1f2937; border-radius: 16px; padding: 30px; position: sticky; top: 100px; }
        .sidebar-form h3 { font-size: 18px; margin-bottom: 20px; color: #f3f4f6; border-bottom: 1px solid #1f2937; padding-bottom: 12px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 12px; color: #9ca3af; margin-bottom: 6px; font-weight: 500; text-transform: uppercase; letter-spacing: 0.5px; }
        .form-control { width: 100%; padding: 14px; background: #1f2937; border: 1px solid #374151; border-radius: 10px; color: #fff; font-size: 14px; outline: none; transition: 0.2s; }
        .form-control:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.2); }
        .btn-submit { width: 100%; padding: 14px; background: #3b82f6; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.2s; margin-top: 10px; }
        .btn-submit:hover { background: #2563eb; }

        /* SAĞ TARAF: LİSTE VE TABLO */
        .main-list { flex: 2; min-width: 600px; background: #111827; border: 1px solid #1f2937; border-radius: 16px; overflow: hidden; }
        .list-header { display: flex; justify-content: space-between; align-items: center; padding: 20px 24px; border-bottom: 1px solid #1f2937; background: #0f172a; }
        .list-header h3 { font-size: 18px; color: #f3f4f6; }
        .search-input { padding: 10px 16px; border-radius: 8px; border: 1px solid #374151; background: #1f2937; color: #fff; font-size: 14px; width: 250px; outline: none; transition: 0.2s; }
        .search-input:focus { border-color: #3b82f6; }

        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 16px 24px; background: #0b0f19; color: #9ca3af; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #1f2937; font-weight: 600; }
        td { padding: 16px 24px; border-bottom: 1px solid #1f2937; vertical-align: middle; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        tr:last-child td { border-bottom: none; }

        .key-text { font-family: monospace; font-size: 14px; font-weight: 600; color: #e5e7eb; }
        .key-note { font-size: 12px; color: #6b7280; margin-top: 4px; }

        /* DURUM ETİKETLERİ */
        .status-badge { padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block; }
        .status-active { background: rgba(16, 185, 129, 0.1); color: #34d399; border: 1px solid rgba(16,185,129,0.2); }
        .status-banned { background: rgba(239, 68, 68, 0.1); color: #f87171; border: 1px solid rgba(239,68,68,0.2); }
        .status-expired { background: rgba(107, 114, 128, 0.1); color: #9ca3af; border: 1px solid rgba(107,114,128,0.2); }

        /* AKSİYON BUTONLARI (GRID YAPIDA) */
        .action-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 6px; }
        .btn-action { padding: 6px 10px; font-size: 11px; font-weight: 600; border-radius: 6px; border: 1px solid #374151; background: transparent; color: #9ca3af; cursor: pointer; transition: 0.2s; text-align: center; }
        .btn-action:hover { background: #374151; color: #fff; }
        .btn-action.red:hover { background: #ef4444; color: #fff; border-color: #ef4444; }
        .btn-action.green:hover { background: #10b981; color: #fff; border-color: #10b981; }
        .btn-action.yellow:hover { background: #f59e0b; color: #fff; border-color: #f59e0b; }

        /* MODAL */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: #111827; width: 100%; max-width: 450px; border-radius: 16px; border: 1px solid #1f2937; padding: 30px; }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { font-size: 20px; color: #fff; }
        .close-btn { background: transparent; border: none; color: #9ca3af; font-size: 24px; cursor: pointer; }
        .modal-body p { color: #9ca3af; font-size: 14px; margin-bottom: 20px; line-height: 1.5; }
        .danger-btn { width: 100%; padding: 14px; background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239,68,68,0.3); border-radius: 10px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .danger-btn:hover { background: #ef4444; color: #fff; }
    </style>
</head>
<body>

    <!-- YATAY ÜST MENÜ -->
    <nav class="navbar">
        <div class="nav-brand">
            <div class="brand-icon">D</div>
            DARK IOS
        </div>
        <div class="nav-links">
            <a href="#" class="nav-link active">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                API Keys
            </a>
            <a href="#" class="nav-link">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 18 22 12 16 6"></polyline><polyline points="8 6 2 12 8 18"></polyline></svg>
                API Settings
            </a>
            <a href="#" class="nav-link" onclick="document.getElementById('settingsModal').style.display='flex'">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                Settings
            </a>
        </div>
        <div class="nav-actions">
            <a href="admin.php?action=logout" class="btn-logout">Logout</a>
        </div>
    </nav>

    <!-- ANA İÇERİK: SOL FORM, SAĞ TABLO -->
    <div class="container">
        
        <!-- SOL: KEY OLUŞTURMA FORMU -->
        <div class="sidebar-form">
            <h3>Create New Key</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Note (Username)</label>
                    <input type="text" name="note" class="form-control" placeholder="e.g. user123">
                </div>
                <div class="form-group">
                    <label>Max Devices</label>
                    <input type="number" name="max_devices" value="1" min="1" class="form-control">
                </div>
                <div class="form-group">
                    <label>Duration</label>
                    <select name="duration_type" class="form-control">
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
                </div>
                <div class="form-group">
                    <label>Custom Key (Optional)</label>
                    <input type="text" name="custom_key" class="form-control" placeholder="Leave empty for auto-gen">
                </div>
                <button type="submit" name="create_key" class="btn-submit">+ Generate Key</button>
            </form>
        </div>

        <!-- SAĞ: TABLO VE ARAMA -->
        <div class="main-list">
            <div class="list-header">
                <h3>Existing Keys</h3>
                <input type="text" id="searchInput" placeholder="Search keys..." class="search-input" onkeyup="filterKeys()">
            </div>
            
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Key</th>
                            <th>Status</th>
                            <th>Expiry</th>
                            <th>Devices</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="keysContainer">
                        <?php foreach($keys_data as $k => $v): ?>
                            <?php $is_banned = ($v['status'] == 'banned'); ?>
                            <tr class="key-row" data-key="<?= htmlspecialchars(strtolower($k)) ?>" data-note="<?= htmlspecialchars(strtolower($v['note'])) ?>">
                                <td>
                                    <div class="key-text"><?= htmlspecialchars($k) ?></div>
                                    <?php if(!empty($v['note'])): ?>
                                        <div class="key-note">Note: <?= htmlspecialchars($v['note']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= key_status_badge($v) ?></td>
                                <td><?= key_time_left($v) ?></td>
                                <td><span style="color:#fff; font-weight:600;"><?= count($v['devices']) ?></span> / <?= $v['max_devices'] ?></td>
                                <td>
                                    <div class="action-grid">
                                        <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="reset_hwid"><button type="submit" name="action_key" class="btn-action yellow" title="Reset Devices">Reset</button></form>
                                        <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="block"><button type="submit" name="action_key" class="btn-action red"><?= $is_banned ? 'Unban' : 'Ban' ?></button></form>
                                        <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="add_time"><button type="submit" name="action_key" class="btn-action green">+1D</button></form>
                                        <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="limit_inc"><button type="submit" name="action_key" class="btn-action">+Dev</button></form>
                                        <form method="POST" onsubmit="return confirm('Delete this key?');"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="delete"><button type="submit" name="action_key" class="btn-action red">Delete</button></form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(count($keys_data) === 0): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 40px; color:#6b7280;">No keys found. Create one on the left.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div id="noResults" style="display:none; text-align:center; padding:40px; color:#6b7280; font-size: 14px;">No keys found matching your search.</div>
            </div>
        </div>
    </div>

    <!-- AYARLAR (MODAL) -->
    <div id="settingsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Settings</h3>
                <button class="close-btn" onclick="document.getElementById('settingsModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <p>Manage your application settings and data here.</p>
                
                <div style="border-top:1px solid #1f2937; padding-top:20px; margin-top:20px;">
                    <h4 style="color:#ef4444; font-size:14px; margin-bottom:12px;">Danger Zone</h4>
                    <p style="font-size:13px; margin-bottom:16px;">This action will permanently delete all keys and reset the database.</p>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to delete ALL keys?');">
                        <button type="submit" name="delete_all" class="danger-btn">Reset All Keys</button>
                    </form>
                </div>
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
