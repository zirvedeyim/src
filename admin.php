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

// YENİ GİRİŞ EKRANI (Aydınlık Tema)
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Login — DARK IOS</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
            body { background-color: #f3f4f6; display: flex; justify-content: center; align-items: center; height: 100vh; color: #1f2937; }
            .login-card { background: #fff; padding: 40px; border-radius: 20px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05), 0 8px 10px -6px rgba(0,0,0,0.01); border: 1px solid #e5e7eb; }
            .login-card h2 { text-align: center; font-size: 24px; font-weight: 700; margin-bottom: 30px; color: #111827; }
            .input-group { margin-bottom: 20px; }
            .input-group label { display: block; font-size: 13px; font-weight: 600; color: #4b5563; margin-bottom: 8px; }
            .input-group input { width: 100%; padding: 14px; background: #f9fafb; border: 1px solid #d1d5db; border-radius: 10px; font-size: 15px; color: #111827; transition: 0.2s; outline: none; }
            .input-group input:focus { border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
            .btn-submit { width: 100%; padding: 14px; background: #111827; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.2s; }
            .btn-submit:hover { background: #374151; }
            .error { color: #dc2626; font-size: 13px; text-align: center; margin-bottom: 20px; background: #fef2f2; padding: 12px; border-radius: 8px; border: 1px solid #fecaca; }
        </style>
    </head>
    <body>
        <div class="login-card">
            <h2>Admin Login</h2>
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
                <button type="submit" name="login" class="btn-submit">Sign In</button>
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
    if ($v['status'] == 'banned') return '<span class="badge badge-red">Banned</span>';
    if ($v['expire'] > 0 && $v['expire'] < time()) return '<span class="badge badge-gray">Expired</span>';
    return '<span class="badge badge-green">Active</span>';
}

function key_time_left($v) {
    if ($v['expire'] == 0) return '<span style="color:#8b5cf6; font-weight:600;">Lifetime</span>';
    $diff = $v['expire'] - time();
    if ($diff <= 0) return '<span style="color:#ef4444; font-weight:600;">Expired</span>';
    $days = ceil($diff / 86400);
    return '<span style="color:#6b7280;">' . $days . ' days</span>';
}
?>

<!-- ANA PANEL: BENTO KUTU TASARIMI (LIGHT MODE) -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — DARK IOS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Plus Jakarta Sans', sans-serif; }
        body { background-color: #f8fafc; color: #0f172a; padding-bottom: 50px; }
        
        /* ÜST BAR (Yatay) */
        .topbar { background: #ffffff; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; padding: 0 40px; height: 72px; position: sticky; top: 0; z-index: 50; }
        .brand { font-size: 20px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px; }
        .brand-icon { background: #0f172a; color: #fff; width: 32px; height: 32px; border-radius: 8px; display: flex; justify-content: center; align-items: center; }
        
        .top-actions { display: flex; gap: 12px; align-items: center; }
        .btn-outline { background: #fff; border: 1px solid #cbd5e1; padding: 10px 18px; border-radius: 8px; font-size: 14px; font-weight: 600; color: #475569; cursor: pointer; text-decoration: none; transition: 0.2s; }
        .btn-outline:hover { background: #f1f5f9; color: #0f172a; }
        .btn-danger-outline { color: #ef4444; border-color: #fca5a5; }
        .btn-danger-outline:hover { background: #fef2f2; }

        /* ANA GRID YAPISI */
        .main-container { max-width: 1300px; margin: 40px auto; padding: 0 20px; display: grid; grid-template-columns: 350px 1fr; gap: 24px; align-items: start; }
        @media (max-width: 900px) { .main-container { grid-template-columns: 1fr; } }

        /* KART TASARIMI */
        .card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 28px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
        .card h2 { font-size: 18px; font-weight: 700; margin-bottom: 24px; color: #0f172a; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; }

        /* FORM ELEMANLARI */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: #475569; margin-bottom: 8px; }
        .form-control { width: 100%; padding: 12px 16px; border: 1px solid #cbd5e1; border-radius: 10px; font-size: 14px; color: #0f172a; background: #f8fafc; transition: 0.2s; outline: none; }
        .form-control:focus { border-color: #3b82f6; background: #fff; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
        .btn-primary { width: 100%; padding: 14px; background: #3b82f6; color: #fff; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: 0.2s; }
        .btn-primary:hover { background: #2563eb; transform: translateY(-1px); }

        /* TABLO TASARIMI */
        .table-wrapper { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th { text-align: left; padding: 16px; color: #64748b; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; font-weight: 700; }
        td { padding: 16px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        .key-text { font-family: 'Courier New', Courier, monospace; font-weight: 700; color: #0f172a; font-size: 14px; }
        .key-note { font-size: 12px; color: #64748b; margin-top: 4px; }

        /* ETİKETLER (Badges) */
        .badge { padding: 6px 10px; border-radius: 6px; font-size: 12px; font-weight: 600; display: inline-block; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-gray { background: #f3f4f6; color: #4b5563; }

        /* AKSİYON BUTONLARI */
        .action-buttons { display: flex; gap: 6px; flex-wrap: wrap; }
        .action-buttons form { display: inline; }
        .action-btn { padding: 6px 10px; font-size: 12px; font-weight: 600; border-radius: 6px; border: 1px solid #e2e8f0; background: #fff; color: #475569; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; }
        .action-btn:hover { background: #f1f5f9; color: #0f172a; }
        .action-btn.red:hover { background: #fef2f2; color: #ef4444; border-color: #fca5a5; }
        .action-btn.green:hover { background: #f0fdf4; color: #22c55e; border-color: #bbf7d0; }
        .action-btn.yellow:hover { background: #fffbeb; color: #f59e0b; border-color: #fde68a; }

        /* ARAMA ÇUBUĞU */
        .search-container { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .search-input { padding: 10px 16px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; width: 250px; outline: none; transition: 0.2s; }
        .search-input:focus { border-color: #3b82f6; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }

        /* MODAL */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); z-index: 100; justify-content: center; align-items: center; }
        .modal-content { background: #fff; padding: 30px; border-radius: 16px; width: 100%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-header h3 { font-size: 18px; font-weight: 700; }
        .modal-close { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; }
        .modal-body p { color: #64748b; font-size: 14px; margin-bottom: 20px; line-height: 1.5; }
        .btn-danger { width: 100%; padding: 12px; background: #ef4444; color: #fff; border: none; border-radius: 8px; font-weight: 600; cursor: pointer; }
    </style>
</head>
<body>

    <!-- ÜST BAR -->
    <div class="topbar">
        <div class="brand">
            <div class="brand-icon">D</div>
            DARK IOS
        </div>
        <div class="top-actions">
            <button onclick="document.getElementById('settingsModal').style.display='flex'" class="btn-outline">Settings</button>
            <a href="admin.php?action=logout" class="btn-outline btn-danger-outline">Logout</a>
        </div>
    </div>

    <!-- ANA İÇERİK -->
    <div class="main-container">
        
        <!-- SOL: KEY OLUŞTURMA -->
        <div class="card">
            <h2>Generate New Key</h2>
            <form method="POST">
                <div class="form-group">
                    <label>User Note (Optional)</label>
                    <input type="text" name="note" class="form-control" placeholder="e.g. User123">
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
                    <input type="text" name="custom_key" class="form-control" placeholder="Leave blank for auto">
                </div>
                <button type="submit" name="create_key" class="btn-primary">Create Key</button>
            </form>
        </div>

        <!-- SAĞ: KEY LİSTESİ -->
        <div class="card">
            <div class="search-container">
                <h2>Key Database</h2>
                <input type="text" id="searchInput" placeholder="Search keys or notes..." class="search-input" onkeyup="filterKeys()">
            </div>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>License Key</th>
                            <th>Status</th>
                            <th>Duration</th>
                            <th>HWID</th>
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
                                        <div class="key-note"><?= htmlspecialchars($v['note']) ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><?= key_status_badge($v) ?></td>
                                <td><?= key_time_left($v) ?></td>
                                <td><span style="font-weight:600; color:#0f172a;"><?= count($v['devices']) ?></span> / <?= $v['max_devices'] ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="reset_hwid"><button type="submit" name="action_key" class="action-btn yellow">Reset</button></form>
                                        <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="block"><button type="submit" name="action_key" class="action-btn <?= $is_banned ? 'green' : 'red' ?>"><?= $is_banned ? 'Unban' : 'Ban' ?></button></form>
                                        <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="add_time"><button type="submit" name="action_key" class="action-btn green">+1D</button></form>
                                        <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="limit_inc"><button type="submit" name="action_key" class="action-btn">+Dev</button></form>
                                        <form method="POST" onsubmit="return confirm('Are you sure you want to delete this key?');"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="delete"><button type="submit" name="action_key" class="action-btn red">Delete</button></form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(count($keys_data) === 0): ?>
                            <tr><td colspan="5" style="text-align:center; padding: 40px; color:#94a3b8;">No keys found in the database.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <div id="noResults" style="display:none; text-align:center; padding:40px; color:#94a3b8;">No keys match your search.</div>
            </div>
        </div>
    </div>

    <!-- SETTINGS MODAL -->
    <div id="settingsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Settings</h3>
                <button class="modal-close" onclick="document.getElementById('settingsModal').style.display='none'">&times;</button>
            </div>
            <div class="modal-body">
                <p>Manage your application settings. Be careful with actions in the danger zone.</p>
                
                <div style="border-top:1px solid #e2e8f0; padding-top:20px; margin-top:20px;">
                    <h4 style="color:#ef4444; font-size:14px; margin-bottom:12px;">Danger Zone</h4>
                    <p style="font-size:13px; margin-bottom:16px;">This action will permanently delete all keys from the database. This cannot be undone.</p>
                    <form method="POST" onsubmit="return confirm('Are you absolutely sure you want to delete ALL keys?');">
                        <button type="submit" name="delete_all" class="btn-danger">Delete All Keys</button>
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
