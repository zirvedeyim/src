<?php
session_start();

$admin_user = "zirvedeyim0";
$admin_pass = "wear5555.2013a";

$json_file = "keys.json";
$backup_file = "keys_backup.json";
$config_file = "config.json"; // Global Stop/Ban durumu için

if (!file_exists($json_file) && file_exists($backup_file)) { copy($backup_file, $json_file); }
if (!file_exists($json_file)) { file_put_contents($json_file, json_encode([])); }
if (!file_exists($config_file)) { file_put_contents($config_file, json_encode(["global_stop" => false])); }

$keys_data = json_decode(file_get_contents($json_file), true);
if (!is_array($keys_data)) $keys_data = [];
$config = json_decode(file_get_contents($config_file), true);

// Global Stop kontrolü
$global_stop = isset($config['global_stop']) ? $config['global_stop'] : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // GLOBAL STOP TOGGLE
    if (isset($_POST['toggle_global_stop'])) {
        $global_stop = !$global_stop;
        file_put_contents($config_file, json_encode(["global_stop" => $global_stop]));
        
        if ($global_stop) {
            foreach ($keys_data as $k => $v) {
                if ($v['status'] !== 'banned') {
                    $keys_data[$k]['status'] = 'global_banned';
                }
            }
        } else {
            foreach ($keys_data as $k => $v) {
                if ($v['status'] === 'global_banned') {
                    $keys_data[$k]['status'] = 'active';
                }
            }
        }
        file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        header("Location: admin.php");
        exit;
    }

    if (isset($_POST['login'])) {
        if ($_POST['username'] === $admin_user && $_POST['password'] === $admin_pass) {
            $_SESSION['logged_in'] = true; header("Location: admin.php"); exit;
        } else { $login_error = "Invalid username or password!"; }
    }

    if (isset($_POST['create_key'])) {
        $duration_type = $_POST['duration_type'];
        $note = htmlspecialchars($_POST['note']);
        $custom_key = trim($_POST['custom_key']);
        $max_devices = max(1, intval($_POST['max_devices']));

        $now = time();
        $expire_time = $now + 86400;
        $prefix = "DARKIOS-1D-";

        if ($duration_type == '7D') { $expire_time = $now + (7 * 86400); $prefix = "DARKIOS-7D-"; }
        elseif ($duration_type == '30D') { $expire_time = $now + (30 * 86400); $prefix = "DARKIOS-30D-"; }
        elseif ($duration_type == 'LIFETIME') { $expire_time = 0; $prefix = "DARKIOS-LIFETIME-"; }

        $random_part = strtoupper(substr(bin2hex(random_bytes(8)), 0, 15));
        $key = !empty($custom_key) ? $custom_key : $prefix . $random_part;
        
        $keys_data[$key] = [
            "note" => $note, "expire" => $expire_time, "max_devices" => $max_devices,
            "devices" => [], "status" => $global_stop ? "global_banned" : "active"
        ];
        file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        header("Location: admin.php"); exit;
    }

    if (isset($_POST['action_key'])) {
        $k = $_POST['key_id'];
        $act = $_POST['sub_action'];
        if (isset($keys_data[$k])) {
            if ($act == 'delete') { unset($keys_data[$k]); }
            elseif ($act == 'block') { 
                $keys_data[$k]['status'] = ($keys_data[$k]['status'] == 'banned') ? ($global_stop ? 'global_banned' : 'active') : 'banned'; 
            }
            elseif ($act == 'reset_hwid') { $keys_data[$k]['devices'] = []; }
            elseif ($act == 'add_time') { if ($keys_data[$k]['expire'] > 0) $keys_data[$k]['expire'] += 86400; }
            file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
            file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        }
        header("Location: admin.php"); exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    unset($_SESSION['logged_in']); header("Location: admin.php"); exit;
}

function key_status_badge($v) {
    if ($v['status'] == 'banned') return '<span class="badge badge-red">Manually Banned</span>';
    if ($v['status'] == 'global_banned') return '<span class="badge badge-orange">System Stopped</span>';
    if ($v['expire'] > 0 && $v['expire'] < time()) return '<span class="badge badge-gray">Expired</span>';
    return '<span class="badge badge-green">Active</span>';
}

function key_time_left($v) {
    if ($v['expire'] == 0) return '<span style="color:#8b5cf6; font-weight:600;">Lifetime</span>';
    $diff = $v['expire'] - time();
    if ($diff <= 0) return '<span style="color:#ef4444; font-weight:600;">Expired</span>';
    return '<span style="color:#64748b; font-weight:500;">' . ceil($diff / 86400) . ' days</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>DARK IOS Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8fafc; --sidebar: #ffffff; --card: #ffffff; --border: #e2e8f0;
            --text: #0f172a; --text-muted: #64748b; --primary: #6366f1; --primary-hover: #4f46e5;
            --danger: #ef4444; --success: #10b981; --warning: #f59e0b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; height: 100vh; overflow: hidden; }

        /* SOL MENÜ (Sidebar) */
        .sidebar { width: 260px; background: var(--sidebar); border-right: 1px solid var(--border); display: flex; flex-direction: column; }
        .logo-area { padding: 24px; display: flex; align-items: center; gap: 10px; font-weight: 700; font-size: 20px; border-bottom: 1px solid var(--border); }
        .logo-icon { background: var(--primary); color: #fff; width: 32px; height: 32px; border-radius: 8px; display: flex; justify-content: center; align-items: center; }
        
        .nav-section { padding: 20px 16px; flex: 1; overflow-y: auto;}
        .nav-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 600; margin-bottom: 12px; padding-left: 12px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; color: var(--text-muted); font-weight: 500; font-size: 14px; cursor: pointer; text-decoration: none; margin-bottom: 4px; transition: 0.2s; }
        .nav-item:hover, .nav-item.active { background: #f1f5f9; color: var(--primary); }
        .nav-item svg { width: 18px; height: 18px; }

        .sidebar-bottom { padding: 20px; border-top: 1px solid var(--border); }
        .user-profile { display: flex; align-items: center; gap: 12px; margin-top: 15px; }
        .user-avatar { width: 36px; height: 36px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; justify-content: center; align-items: center; font-weight: bold; }

        /* ANA İÇERİK */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; }
        .topbar { height: 72px; background: #fff; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; padding: 0 30px; }
        .breadcrumb { font-size: 14px; color: var(--text-muted); font-weight: 500; }
        .breadcrumb span { color: var(--text); }
        
        .content-area { padding: 30px; max-width: 1200px; margin: 0 auto; width: 100%; display: none; }
        .content-area.active { display: block; animation: fadeIn 0.3s ease-out; }

        /* KARTLAR VE FORMLAR */
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-title { font-size: 18px; font-weight: 600; }
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 500; color: var(--text-muted); margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; transition: 0.2s; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        
        .btn { padding: 10px 16px; border: none; border-radius: 8px; font-size: 14px; font-weight: 500; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: #dc2626; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { background: #f1f5f9; }
        .btn-success { background: var(--success); color: #fff; }

        /* TABLO */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 16px; border-bottom: 1px solid var(--border); font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: 600; background: #f8fafc; }
        td { padding: 16px; border-bottom: 1px solid var(--border); font-size: 14px; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        .key-text { font-family: monospace; font-weight: 600; background: #f1f5f9; padding: 4px 8px; border-radius: 4px; font-size: 13px; border: 1px solid #e2e8f0; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-gray { background: #f1f5f9; color: #475569; }
        .badge-orange { background: #ffedd5; color: #9a3412; }

        /* AKSİYON BUTONLARI */
        .action-group { display: flex; gap: 6px; flex-wrap: wrap; }
        .action-btn { padding: 6px 10px; font-size: 12px; border-radius: 6px; border: 1px solid var(--border); background: #fff; cursor: pointer; transition: 0.2s; font-weight: 500; color: var(--text-muted); }
        .action-btn:hover { background: #f1f5f9; color: var(--text); }
        .action-btn.red { color: var(--danger); border-color: #fca5a5; }
        .action-btn.red:hover { background: #fee2e2; }
        .action-btn.info { color: var(--primary); border-color: #c7d2fe; }
        .action-btn.info:hover { background: #e0e7ff; }

        /* GLOBAL STOP KUTUSU (Sadece Banned Keys içinde) */
        .stop-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .stop-title { font-size: 16px; font-weight: 600; color: var(--danger); margin-bottom: 4px; display: flex; align-items: center; gap: 8px;}
        .stop-desc { font-size: 13px; color: #7f1d1d; }
        
        /* CİHAZ LİSTESİ (Info Sekmesi) */
        .device-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 16px; }
        .device-card { background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 20px; }
        .device-header { font-weight: 700; color: var(--primary); margin-bottom: 12px; font-size: 16px; display: flex; align-items: center; gap: 8px;}
        .device-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed var(--border); }
        .device-row:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0;}
        .device-row span:first-child { color: var(--text-muted); }
        .device-row span:last-child { font-weight: 600; color: var(--text); }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <!-- SOL MENÜ -->
    <aside class="sidebar">
        <div class="logo-area">
            <div class="logo-icon">D</div>
            DARK IOS
        </div>
        
        <div class="nav-section">
            <div class="nav-title">Dashboard</div>
            <a href="#" class="nav-item active" onclick="switchTab('keysTab', this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                All Keys
            </a>
            <a href="#" class="nav-item" onclick="switchTab('bannedTab', this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                Banned Keys
            </a>
            <a href="#" class="nav-item" onclick="switchTab('infoTab', this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                Device Info
            </a>
            <a href="#" class="nav-item" onclick="switchTab('settingsTab', this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                Settings
            </a>
        </div>

        <div class="sidebar-bottom">
            <a href="admin.php?action=logout" class="btn btn-outline" style="width:100%; justify-content:center;">Logout</a>
            <div class="user-profile">
                <div class="user-avatar">A</div>
                <div>
                    <div style="font-size:14px; font-weight:600;">Admin</div>
                    <div style="font-size:12px; color:var(--text-muted);">Manager</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- ANA İÇERİK -->
    <main class="main-content">
        <header class="topbar">
            <div class="breadcrumb">Settings / <span>API Key & SDKs</span></div>
        </header>

        <!-- SEKME 1: TÜM KEYLER (ALL KEYS) -->
        <div id="keysTab" class="content-area active">
            <!-- YENİ KEY OLUŞTUR -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Create New API Key</div>
                </div>
                <form method="POST">
                    <div class="form-grid">
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
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Custom Key (Optional)</label>
                            <input type="text" name="custom_key" class="form-control" placeholder="Leave blank for auto">
                        </div>
                    </div>
                    <button type="submit" name="create_key" class="btn btn-primary">+ Create New Key</button>
                </form>
            </div>

            <!-- TABLO -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Active Keys</div>
                    <input type="text" id="searchInput" placeholder="Search keys..." class="form-control" style="width: 250px;" onkeyup="filterKeys()">
                </div>
                <div class="table-responsive">
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
                                <?php if($v['status'] == 'banned') continue; ?>
                                <tr class="key-row" data-key="<?= htmlspecialchars(strtolower($k)) ?>" data-note="<?= htmlspecialchars(strtolower($v['note'])) ?>">
                                    <td>
                                        <div class="key-text"><?= htmlspecialchars($k) ?></div>
                                        <?php if(!empty($v['note'])): ?><div style="font-size:12px; color:var(--text-muted); margin-top:4px;"><?= htmlspecialchars($v['note']) ?></div><?php endif; ?>
                                    </td>
                                    <td><?= key_status_badge($v) ?></td>
                                    <td><?= key_time_left($v) ?></td>
                                    <td><span style="font-weight:600;"><?= count($v['devices']) ?></span> / <?= $v['max_devices'] ?></td>
                                    <td>
                                        <div class="action-group">
                                            <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="reset_hwid"><button type="submit" name="action_key" class="action-btn">Reset HWID</button></form>
                                            <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="block"><button type="submit" name="action_key" class="action-btn red">Ban</button></form>
                                            <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="add_time"><button type="submit" name="action_key" class="action-btn">+1D</button></form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="noResults" style="display:none; text-align:center; padding:20px; color:var(--text-muted);">No keys found.</div>
                </div>
            </div>
        </div>

        <!-- SEKME 2: BANNED KEYLER -->
        <div id="bannedTab" class="content-area">
            <!-- GLOBAL STOP BUTONU SADECE BURADA -->
            <div class="stop-box">
                <div>
                    <div class="stop-title">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line></svg>
                        Global System Stop
                    </div>
                    <div class="stop-desc">
                        <?= $global_stop ? "System is STOPPED. All keys are temporarily banned." : "System is ACTIVE. Keys are working normally." ?>
                    </div>
                </div>
                <form method="POST">
                    <button type="submit" name="toggle_global_stop" class="btn <?= $global_stop ? 'btn-success' : 'btn-danger' ?>">
                        <?= $global_stop ? "Resume System" : "STOP All Keys" ?>
                    </button>
                </form>
            </div>

            <!-- BANLI KEYLERİN LİSTESİ -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Manually Banned Keys</div>
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>License Key</th>
                                <th>Status</th>
                                <th>Duration</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($keys_data as $k => $v): ?>
                                <?php if($v['status'] != 'banned') continue; ?>
                                <tr>
                                    <td><div class="key-text"><?= htmlspecialchars($k) ?></div></td>
                                    <td><?= key_status_badge($v) ?></td>
                                    <td><?= key_time_left($v) ?></td>
                                    <td>
                                        <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="block"><button type="submit" name="action_key" class="action-btn" style="color:var(--success); border-color:var(--success);">Unban</button></form>
                                        <form method="POST" onsubmit="return confirm('Delete permanently?');"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="delete"><button type="submit" name="action_key" class="action-btn red">Delete</button></form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- SEKME 3: CİHAZ BİLGİLERİ (INFO) -->
        <div id="infoTab" class="content-area">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">All Registered Devices</div>
                </div>
                <div class="device-grid">
                    <?php 
                    $hasDevices = false;
                    foreach($keys_data as $k => $v): 
                        if (!empty($v['devices'])):
                            foreach($v['devices'] as $dev):
                                $hasDevices = true;
                                $devId = (is_array($dev)) ? ($dev['id'] ?? 'Unknown') : $dev;
                                $devModel = (is_array($dev)) ? ($dev['model'] ?? "Unknown Device") : "Unknown Device"; 
                                $devIP = (is_array($dev)) ? ($dev['ip'] ?? "Unknown IP") : "Unknown IP";
                                $devDate = (is_array($dev)) ? ($dev['date'] ?? "N/A") : "N/A";
                                $devBattery = (is_array($dev)) ? ($dev['battery'] ?? "Unknown") : "Unknown";
                    ?>
                                <div class="device-card">
                                    <div class="device-header">📱 <?= htmlspecialchars($devModel) ?></div>
                                    <div class="device-row"><span>Linked Key</span> <span style="font-family:monospace; color:var(--primary);"><?= htmlspecialchars($k) ?></span></div>
                                    <div class="device-row"><span>HWID</span> <span><?= htmlspecialchars($devId) ?></span></div>
                                    <div class="device-row"><span>IP Address</span> <span><?= htmlspecialchars($devIP) ?></span></div>
                                    <div class="device-row"><span>Battery</span> <span style="color:var(--success);"><?= htmlspecialchars($devBattery) ?></span></div>
                                    <div class="device-row"><span>Last Login</span> <span><?= htmlspecialchars($devDate) ?></span></div>
                                </div>
                    <?php 
                            endforeach;
                        endif;
                    endforeach; 
                    if (!$hasDevices) echo '<div style="color:var(--text-muted); grid-column: 1/-1; text-align: center; padding: 40px;">No devices registered yet.</div>';
                    ?>
                </div>
            </div>
        </div>

        <!-- SEKME 4: SETTINGS -->
        <div id="settingsTab" class="content-area">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Settings</div>
                </div>
                <p style="color:var(--text-muted); margin-bottom:20px; font-size:14px;">Manage your API settings and security.</p>
                
                <div style="background:#f8fafc; border:1px solid var(--border); border-radius:8px; padding:16px; margin-bottom:16px;">
                    <div style="font-weight:600; margin-bottom:4px;">API Endpoint</div>
                    <div style="font-family:monospace; color:var(--primary);">https://a-g9eo.onrender.com/api.php</div>
                </div>

                <div style="border-top:1px solid var(--border); padding-top:20px; margin-top:20px;">
                    <h4 style="color:var(--danger); margin-bottom:12px;">Danger Zone</h4>
                    <form method="POST" onsubmit="return confirm('Delete ALL keys?');">
                        <button type="submit" name="delete_all" class="btn btn-danger">Delete All Keys</button>
                    </form>
                </div>
            </div>
        </div>

    </main>

    <script>
        function switchTab(tabId, element) {
            document.querySelectorAll('.content-area').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.nav-item').forEach(el => el.classList.remove('active'));
            document.getElementById(tabId).classList.add('active');
            element.classList.add('active');
        }

        function filterKeys() {
            var input = document.getElementById('searchInput');
            var filter = input.value.toLowerCase();
            var rows = document.querySelectorAll('.key-row');
            var visibleCount = 0;
            rows.forEach(function(row) {
                var keyName = row.getAttribute('data-key');
                var noteName = row.getAttribute('data-note');
                if (keyName.includes(filter) || noteName.includes(filter)) {
                    row.style.display = ''; visibleCount++;
                } else { row.style.display = 'none'; }
            });
            document.getElementById('noResults').style.display = (visibleCount === 0 && rows.length > 0) ? 'block' : 'none';
        }
    </script>
</body>
</html>
