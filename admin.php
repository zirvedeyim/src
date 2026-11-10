<?php
session_start();

$admin_user = "zirvedeyim0";
$admin_pass = "wear5555.2013a";

$json_file = "keys.json";
$backup_file = "keys_backup.json";
$config_file = "config.json";

if (!file_exists($json_file) && file_exists($backup_file)) { copy($backup_file, $json_file); }
if (!file_exists($json_file)) { file_put_contents($json_file, json_encode([])); }
if (!file_exists($config_file)) { file_put_contents($config_file, json_encode(["global_stop" => false])); }

$keys_data = json_decode(file_get_contents($json_file), true);
if (!is_array($keys_data)) $keys_data = [];
$config = json_decode(file_get_contents($config_file), true);

$global_stop = isset($config['global_stop']) ? $config['global_stop'] : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['toggle_global_stop'])) {
        $global_stop = !$global_stop;
        file_put_contents($config_file, json_encode(["global_stop" => $global_stop]));
        if ($global_stop) {
            foreach ($keys_data as $k => $v) { if ($v['status'] !== 'banned') { $keys_data[$k]['status'] = 'global_banned'; } }
        } else {
            foreach ($keys_data as $k => $v) { if ($v['status'] === 'global_banned') { $keys_data[$k]['status'] = 'active'; } }
        }
        file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        header("Location: admin.php"); exit;
    }

    if (isset($_POST['login'])) {
        if ($_POST['username'] === $admin_user && $_POST['password'] === $admin_pass) {
            $_SESSION['logged_in'] = true; header("Location: admin.php"); exit;
        } else { $login_error = "Invalid username or password!"; }
    }

    function getDurationAndPrefix($type) {
        $now = time();
        $prefix = "DARKIOS-";
        $expire = $now + 86400;

        if (strpos($type, 'TEST_') === 0) {
            $prefix = "TESTERKEY-";
            $type = str_replace('TEST_', '', $type);
        }

        if ($type == '1H') { $expire = $now + 3600; $prefix .= "1H-"; }
        elseif ($type == '6H') { $expire = $now + (6 * 3600); $prefix .= "6H-"; }
        elseif ($type == '12H') { $expire = $now + (12 * 3600); $prefix .= "12H-"; }
        elseif ($type == '1D') { $expire = $now + 86400; $prefix .= "1D-"; }
        elseif ($type == '7D') { $expire = $now + (7 * 86400); $prefix .= "7D-"; }
        elseif ($type == '30D') { $expire = $now + (30 * 86400); $prefix .= "30D-"; }
        elseif ($type == 'LIFETIME') { $expire = 0; $prefix .= "LIFETIME-"; }

        return ["expire" => $expire, "prefix" => $prefix];
    }

    if (isset($_POST['create_key'])) {
        $duration_type = $_POST['duration_type'];
        $note = htmlspecialchars($_POST['note']);
        $custom_key = trim($_POST['custom_key']);
        $max_devices = max(1, intval($_POST['max_devices']));
        $durInfo = getDurationAndPrefix($duration_type);

        $random_part = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
        $key = !empty($custom_key) ? $custom_key : $durInfo['prefix'] . $random_part;
        
        $keys_data[$key] = [
            "note" => $note, "expire" => $durInfo['expire'], "max_devices" => $max_devices,
            "devices" => [], "status" => $global_stop ? "global_banned" : "active"
        ];
        file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        header("Location: admin.php"); exit;
    }

    if (isset($_POST['bulk_create'])) {
        $amount = max(1, intval($_POST['bulk_amount']));
        $duration_type = $_POST['bulk_duration'];
        $max_devices = max(1, intval($_POST['bulk_devices']));
        $durInfo = getDurationAndPrefix($duration_type);
        
        $generated_keys = [];
        for ($i = 0; $i < $amount; $i++) {
            $random_part = strtoupper(substr(bin2hex(random_bytes(6)), 0, 10));
            $key = $durInfo['prefix'] . $random_part;
            
            $keys_data[$key] = [
                "note" => "Bulk", "expire" => $durInfo['expire'], "max_devices" => $max_devices,
                "devices" => [], "status" => $global_stop ? "global_banned" : "active"
            ];
            $generated_keys[] = $key;
        }
        file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        
        $_SESSION['bulk_generated'] = $generated_keys;
        header("Location: admin.php?page=bulk"); exit;
    }

    // KEY DÜZENLEME (EDIT) İŞLEMİ
    if (isset($_POST['edit_key_name'])) {
        $old_key = $_POST['old_key'];
        $new_key = trim($_POST['new_key']);
        
        if (!empty($new_key) && $old_key !== $new_key && isset($keys_data[$old_key])) {
            // Yeni isim zaten var mı kontrol et
            if (!isset($keys_data[$new_key])) {
                // Keyin tüm verilerini kopyala, yeni isimle kaydet ve eskisini sil
                $keys_data[$new_key] = $keys_data[$old_key];
                unset($keys_data[$old_key]);
                
                file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
                file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
            }
        }
        header("Location: admin.php"); exit;
    }

    if (isset($_POST['action_key'])) {
        $k = $_POST['key_id'];
        $act = $_POST['sub_action'];
        if (isset($keys_data[$k])) {
            if ($act == 'delete') { unset($keys_data[$k]); }
            elseif ($act == 'block') { $keys_data[$k]['status'] = ($keys_data[$k]['status'] == 'banned') ? ($global_stop ? 'global_banned' : 'active') : 'banned'; }
            elseif ($act == 'reset_hwid') { $keys_data[$k]['devices'] = []; }
            elseif ($act == 'add_time') { 
                $add_time = intval($_POST['add_time_value']);
                $time_unit = $_POST['add_time_unit'];
                if ($add_time > 0) {
                    $seconds = ($time_unit == 'days') ? $add_time * 86400 : $add_time * 3600;
                    if ($keys_data[$k]['expire'] > 0) { $keys_data[$k]['expire'] += $seconds; }
                }
            }
            elseif ($act == 'add_device') {
                $add_amount = max(1, intval($_POST['device_amount']));
                $keys_data[$k]['max_devices'] += $add_amount;
            }
            file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
            file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        }
        header("Location: admin.php"); exit;
    }
    
    if (isset($_POST['delete_all'])) {
        $keys_data = [];
        file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
        header("Location: admin.php"); exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] == 'logout') {
    unset($_SESSION['logged_in']); header("Location: admin.php"); exit;
}

// --- LOGIN EKRANI ---
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Login — DARK IOS</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@600;700&display=swap" rel="stylesheet">
        <style>
            * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
            body { background-color: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; color: #0f172a; font-weight: 600;}
            .login-card { background: #ffffff; padding: 40px; border-radius: 16px; width: 100%; max-width: 400px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; }
            .login-card h2 { text-align: center; font-size: 24px; font-weight: 700; margin-bottom: 30px; color: #0f172a; }
            .input-group { margin-bottom: 20px; }
            .input-group label { display: block; font-size: 13px; font-weight: 700; color: #64748b; margin-bottom: 8px; }
            .input-group input { width: 100%; padding: 12px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; color: #0f172a; transition: 0.2s; outline: none; font-weight: 600;}
            .input-group input:focus { border-color: #6366f1; background: #fff; box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
            .btn-submit { width: 100%; padding: 12px; background: #6366f1; color: #fff; border: none; border-radius: 8px; font-size: 15px; font-weight: 700; cursor: pointer; transition: 0.2s; }
            .btn-submit:hover { background: #4f46e5; }
            .error { color: #dc2626; font-size: 13px; text-align: center; margin-bottom: 20px; background: #fef2f2; padding: 12px; border-radius: 8px; border: 1px solid #fecaca; font-weight: 600;}
        </style>
    </head>
    <body>
        <div class="login-card">
            <h2>DARK IOS</h2>
            <?php if(isset($login_error)) echo "<div class='error'>$login_error</div>"; ?>
            <form method="POST">
                <div class="input-group">
                    <label>Username</label>
                    <input type="text" name="username" placeholder="Enter username" required autofocus>
                </div>
                <div class="input-group">
                    <label>Password</label>
                    <input type="text" name="password" placeholder="Enter password" required>
                </div>
                <button type="submit" name="login" class="btn-submit">Sign In</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

$total_keys_count = count($keys_data);
$total_online_devices = 0;
foreach ($keys_data as $k => $v) { if (isset($v['devices']) && is_array($v['devices'])) { $total_online_devices += count($v['devices']); } }

function key_status_badge($v) {
    if ($v['status'] == 'banned') return '<span class="badge badge-red">Banned</span>';
    if ($v['status'] == 'global_banned') return '<span class="badge badge-orange">Stopped</span>';
    if ($v['expire'] > 0 && $v['expire'] < time()) return '<span class="badge badge-gray">Expired</span>';
    return '<span class="badge badge-green">Active</span>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard — DARK IOS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f8fafc; --sidebar: #ffffff; --card: #ffffff; --border: #e2e8f0;
            --text: #0f172a; --text-muted: #64748b; --primary: #6366f1; --primary-hover: #4f46e5;
            --danger: #ef4444; --success: #10b981; --warning: #f59e0b;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Inter', sans-serif; }
        body { background: var(--bg); color: var(--text); display: flex; height: 100vh; overflow: hidden; font-weight: 500;}

        /* SIDEBAR */
        .sidebar { width: 260px; background: var(--sidebar); border-right: 1px solid var(--border); display: flex; flex-direction: column; }
        .logo-area { padding: 24px; display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 20px; border-bottom: 1px solid var(--border); }
        .logo-icon { background: var(--primary); color: #fff; width: 45px; height: 45px; border-radius: 50%; display: flex; justify-content: center; align-items: center; overflow: hidden; position: relative; border: 2px solid #e2e8f0;}
        .logo-icon img { width: 100%; height: 100%; object-fit: cover; }
        
        .nav-section { padding: 20px 16px; flex: 1; overflow-y: auto;}
        .nav-title { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-muted); font-weight: 700; margin-bottom: 12px; padding-left: 12px; }
        .nav-item { display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: 8px; color: var(--text-muted); font-weight: 600; font-size: 14px; cursor: pointer; text-decoration: none; margin-bottom: 4px; transition: 0.2s; }
        .nav-item:hover, .nav-item.active { background: #f1f5f9; color: var(--primary); }
        .nav-item svg { width: 18px; height: 18px; }

        .sidebar-bottom { padding: 20px; border-top: 1px solid var(--border); }
        .user-profile { display: flex; align-items: center; gap: 12px; margin-top: 15px; cursor: pointer; padding: 8px; border-radius: 8px; transition: 0.2s; background: #f8fafc; border: 1px solid var(--border);}
        .user-profile:hover { background: #f1f5f9; }
        .user-avatar { width: 42px; height: 42px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; justify-content: center; align-items: center; font-weight: 700; overflow: hidden; position: relative; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1);}
        .user-avatar img { width: 100%; height: 100%; object-fit: cover; }

        /* MAIN CONTENT */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow-y: auto; position: relative; }
        #dynamic-bg { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-size: cover; background-position: center; z-index: 0; opacity: 0.15; pointer-events: none; }
        
        .topbar { height: 72px; background: #fff; border-bottom: 1px solid var(--border); display: flex; justify-content: space-between; align-items: center; padding: 0 30px; position: relative; z-index: 1;}
        .breadcrumb { font-size: 14px; color: var(--text-muted); font-weight: 600; }
        .breadcrumb span { color: var(--text); }
        
        .content-area { padding: 30px; max-width: 1200px; margin: 0 auto; width: 100%; display: none; position: relative; z-index: 1;}
        .content-area.active { display: block; animation: fadeIn 0.3s ease-out; }

        .card { background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 24px; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
        .card-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .card-title { font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 15px; }
        
        .stats-text { font-size: 14px; font-weight: 600; color: var(--primary); display: flex; gap: 15px;}
        
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-bottom: 16px; }
        .form-group label { display: block; font-size: 13px; font-weight: 600; color: var(--text-muted); margin-bottom: 6px; }
        .form-control { width: 100%; padding: 10px 14px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; transition: 0.2s; font-weight: 600; color: var(--text);}
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(99,102,241,0.1); }
        
        .btn { padding: 10px 16px; border: none; border-radius: 8px; font-size: 14px; font-weight: 600; cursor: pointer; transition: 0.2s; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; }
        .btn-primary { background: var(--primary); color: #fff; }
        .btn-primary:hover { background: var(--primary-hover); }
        .btn-danger { background: var(--danger); color: #fff; }
        .btn-danger:hover { background: #dc2626; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { background: #f1f5f9; }
        .btn-success { background: var(--success); color: #fff; }

        /* TABLE */
        .table-responsive { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 12px 16px; border-bottom: 1px solid var(--border); font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: 700; background: #f8fafc; }
        td { padding: 16px; border-bottom: 1px solid var(--border); font-size: 14px; font-weight: 500; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: #f8fafc; }

        .key-text { font-family: monospace; font-weight: 700; background: #f1f5f9; padding: 4px 8px; border-radius: 4px; font-size: 13px; border: 1px solid #e2e8f0; display: inline-block; margin-bottom: 4px;}
        .icon-btn { background: none; border: none; cursor: pointer; padding: 4px; border-radius: 4px; color: var(--text-muted); transition: 0.2s; display: inline-flex; align-items: center; justify-content: center; vertical-align: middle; }
        .icon-btn:hover { background: #e2e8f0; color: var(--primary); }
        
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 12px; font-weight: 700; }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-gray { background: #f1f5f9; color: #475569; }
        .badge-orange { background: #ffedd5; color: #9a3412; }

        .action-group { display: flex; gap: 6px; flex-wrap: wrap; align-items: center;}
        .action-btn { padding: 6px 10px; font-size: 12px; border-radius: 6px; border: 1px solid var(--border); background: #fff; cursor: pointer; transition: 0.2s; font-weight: 600; color: var(--text-muted); }
        .action-btn:hover { background: #f1f5f9; color: var(--text); }
        .action-btn.red { color: var(--danger); border-color: #fca5a5; }
        .action-btn.red:hover { background: #fee2e2; }
        .action-btn.info { color: var(--primary); border-color: #c7d2fe; }
        .action-btn.info:hover { background: #e0e7ff; }
        
        .inline-form { display: flex; gap: 4px; align-items: center; }
        .inline-input { width: 50px; padding: 6px; font-size: 12px; font-weight: 600; border: 1px solid var(--border); border-radius: 6px; text-align: center; outline: none; }
        .inline-select { padding: 6px; font-size: 12px; font-weight: 600; border: 1px solid var(--border); border-radius: 6px; outline: none; }

        .stop-box { background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
        .stop-title { font-size: 16px; font-weight: 700; color: var(--danger); margin-bottom: 4px; display: flex; align-items: center; gap: 8px;}
        .stop-desc { font-size: 13px; color: #7f1d1d; font-weight: 600;}

        .device-key-block { border: 1px solid var(--border); border-radius: 12px; margin-bottom: 16px; overflow: hidden; background: #fff; }
        .device-key-header { padding: 16px 20px; background: #f8fafc; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: 0.2s; font-weight: 700; }
        .device-key-header:hover { background: #f1f5f9; }
        .device-key-title { font-family: monospace; color: var(--primary); font-size: 15px; display: flex; align-items: center; gap: 10px;}
        .device-count-badge { background: var(--primary); color: #fff; padding: 2px 8px; border-radius: 12px; font-size: 12px; font-family: 'Inter', sans-serif;}
        .device-list-content { display: none; padding: 20px; border-top: 1px solid var(--border); background: #fff; }
        
        .device-item { padding: 16px; border: 1px solid var(--border); border-radius: 8px; margin-bottom: 12px; background: #f8fafc; }
        .device-item:last-child { margin-bottom: 0; }
        .device-item-title { font-weight: 700; color: var(--text); margin-bottom: 12px; font-size: 14px; display: flex; justify-content: space-between;}
        .device-row { display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px; }
        .device-row span:first-child { color: var(--text-muted); font-weight: 600;}
        .device-row span:last-child { font-weight: 700; color: var(--text); }

        .bulk-result-box { background: #f8fafc; border: 1px solid var(--border); border-radius: 12px; padding: 20px; margin-top: 20px; position: relative;}
        .bulk-textarea { width: 100%; height: 300px; padding: 15px; font-family: monospace; font-size: 14px; font-weight: 600; border: 1px solid var(--border); border-radius: 8px; resize: vertical; outline: none; background: #fff; color: var(--text); line-height: 1.5;}

        .search-bar { padding: 8px 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 13px; outline: none; width: 250px; font-weight: 600;}

        /* MODAL */
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.5); backdrop-filter: blur(4px); z-index: 1000; justify-content: center; align-items: center; }
        .modal-content { background: #fff; padding: 30px; border-radius: 16px; width: 100%; max-width: 400px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .modal-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .modal-title { font-size: 18px; font-weight: 700; }
        .modal-close { background: none; border: none; font-size: 24px; cursor: pointer; color: var(--text-muted); }

        /* PROFILE MODAL */
        .profile-modal-body { display: flex; flex-direction: column; gap: 15px; }
        .profile-pic-large { width: 100px; height: 100px; border-radius: 50%; background: var(--primary); margin: 0 auto 10px auto; display: flex; justify-content: center; align-items: center; font-size: 36px; color: white; overflow: hidden; cursor: pointer; position: relative;}
        .profile-pic-large img { width: 100%; height: 100%; object-fit: cover; }
        .profile-pic-large:hover::after { content: 'Upload'; position: absolute; bottom: 0; left: 0; width: 100%; background: rgba(0,0,0,0.6); color: #fff; font-size: 12px; text-align: center; padding: 4px 0; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    </style>
</head>
<body>

    <aside class="sidebar">
        <div class="logo-area">
            <div class="logo-icon" id="topLogo">
                <span id="topLogoText">D</span>
                <img id="topLogoImg" src="" style="display:none;">
            </div>
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
            
            <div class="nav-title" style="margin-top:20px;">Tools</div>
            <a href="#" class="nav-item" onclick="switchTab('bulkTab', this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
                BULK KEY
            </a>
            <a href="#" class="nav-item" onclick="switchTab('settingsTab', this)">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                Settings
            </a>
        </div>

        <div class="sidebar-bottom">
            <a href="admin.php?action=logout" class="btn btn-outline" style="width:100%; justify-content:center;">Logout</a>
            
            <!-- PROFIL TIKLAMA -->
            <div class="user-profile" onclick="openProfileModal()">
                <div class="user-avatar" id="profileAvatar">
                    <span id="profileText">O</span>
                    <img id="profileImg" src="" style="display:none;">
                </div>
                <div>
                    <div style="font-size:14px; font-weight:700;" id="displayNickname">OWNER</div>
                    <div style="font-size:12px; color:var(--text-muted); font-weight:600;">Manager</div>
                </div>
            </div>
        </div>
    </aside>

    <main class="main-content">
        <div id="dynamic-bg"></div>
        <header class="topbar">
            <div class="breadcrumb">Settings / <span>API Key & SDKs</span></div>
        </header>

        <!-- SEKME 1: TÜM KEYLER -->
        <div id="keysTab" class="content-area active">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Create New API Key</div>
                </div>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>User Note</label>
                            <input type="text" name="note" class="form-control" placeholder="e.g. User123">
                        </div>
                        <div class="form-group">
                            <label>Custom Key (Optional)</label>
                            <input type="text" name="custom_key" class="form-control" placeholder="e.g. dark, 31...">
                        </div>
                        <div class="form-group">
                            <label>Max Devices</label>
                            <input type="number" name="max_devices" value="1" min="1" class="form-control">
                        </div>
                        <div class="form-group">
                            <label>Duration</label>
                            <select name="duration_type" class="form-control">
                                <option value="1H">1 Hour</option>
                                <option value="6H">6 Hours</option>
                                <option value="12H">12 Hours</option>
                                <option value="1D">1 Day</option>
                                <option value="7D">7 Days</option>
                                <option value="30D">30 Days</option>
                                <option value="LIFETIME">Lifetime</option>
                                <option disabled>──────────</option>
                                <option value="TEST_1H">1 Hour (Tester)</option>
                                <option value="TEST_6H">6 Hours (Tester)</option>
                                <option value="TEST_12H">12 Hours (Tester)</option>
                                <option value="TEST_1D">1 Day (Tester)</option>
                                <option value="TEST_7D">7 Days (Tester)</option>
                                <option value="TEST_30D">30 Days (Tester)</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="create_key" class="btn btn-primary">Create Key</button>
                </form>
            </div>

            <div class="card">
                <div class="card-header">
                    <div class="card-title">
                        Active Keys
                        <div class="stats-text">
                            <span>Online: <?= $total_online_devices ?></span>
                            <span>Total: <?= $total_keys_count ?></span>
                        </div>
                    </div>
                    <input type="text" id="searchInput" placeholder="Search keys..." class="search-bar" onkeyup="filterKeys()">
                </div>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>License Key</th>
                                <th>Status</th>
                                <th>Expires In</th>
                                <th>Devices</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="keysContainer">
                            <?php foreach($keys_data as $k => $v): ?>
                                <?php if($v['status'] == 'banned') continue; ?>
                                <tr class="key-row" data-key="<?= htmlspecialchars(strtolower($k)) ?>" data-note="<?= htmlspecialchars(strtolower($v['note'])) ?>">
                                    <td>
                                        <div style="display:flex; align-items:center; gap:5px;">
                                            <div class="key-text"><?= htmlspecialchars($k) ?></div>
                                            <!-- KOPYALA BUTONU -->
                                            <button type="button" class="icon-btn" onclick="copyToClipboard('<?= htmlspecialchars($k) ?>')" title="Copy Key">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                            </button>
                                            <!-- DÜZENLE BUTONU (KALEM) -->
                                            <button type="button" class="icon-btn" onclick="openEditModal('<?= htmlspecialchars($k) ?>')" title="Edit Key">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"></path><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"></path></svg>
                                            </button>
                                        </div>
                                        <?php if(!empty($v['note'])): ?><div style="font-size:12px; color:var(--text-muted); margin-top:4px; font-weight:600;"><?= htmlspecialchars($v['note']) ?></div><?php endif; ?>
                                    </td>
                                    <td><?= key_status_badge($v) ?></td>
                                    <td>
                                        <!-- CANLI SAYAC -->
                                        <span class="countdown" data-expire="<?= $v['expire'] ?>" style="font-weight:700; color:<?= ($v['expire'] > 0 && $v['expire'] < time()) ? '#ef4444' : '#f59e0b' ?>;">
                                            <?= $v['expire'] == 0 ? 'Lifetime' : 'Loading...' ?>
                                        </span>
                                    </td>
                                    <td><span style="font-weight:700;"><?= count($v['devices']) ?></span> / <?= $v['max_devices'] ?></td>
                                    <td>
                                        <div class="action-group">
                                            <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="reset_hwid"><button type="submit" name="action_key" class="action-btn">Reset HWID</button></form>
                                            <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="block"><button type="submit" name="action_key" class="action-btn red">Ban</button></form>
                                            
                                            <form method="POST" class="inline-form">
                                                <input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>">
                                                <input type="hidden" name="sub_action" value="add_time">
                                                <input type="number" name="add_time_value" class="inline-input" value="1" min="1">
                                                <select name="add_time_unit" class="inline-select">
                                                    <option value="days">D</option>
                                                    <option value="hours">H</option>
                                                </select>
                                                <button type="submit" name="action_key" class="action-btn" style="color:var(--success); border-color:var(--success);">+Time</button>
                                            </form>

                                            <form method="POST" class="inline-form">
                                                <input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>">
                                                <input type="hidden" name="sub_action" value="add_device">
                                                <input type="number" name="device_amount" class="inline-input" value="1" min="1">
                                                <button type="submit" name="action_key" class="action-btn info">+Dev</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="noResults" style="display:none; text-align:center; padding:20px; color:var(--text-muted); font-weight:600;">No keys found.</div>
                </div>
            </div>
        </div>

        <!-- SEKME 2: BANNED KEYLER -->
        <div id="bannedTab" class="content-area">
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

            <div class="card">
                <div class="card-header">
                    <div class="card-title">Banned Keys</div>
                    <input type="text" id="searchBanned" placeholder="Search banned..." class="search-bar" onkeyup="filterBanned()">
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
                        <tbody id="bannedContainer">
                            <?php foreach($keys_data as $k => $v): ?>
                                <?php if($v['status'] != 'banned') continue; ?>
                                <tr class="banned-row" data-key="<?= htmlspecialchars(strtolower($k)) ?>">
                                    <td><div class="key-text"><?= htmlspecialchars($k) ?></div></td>
                                    <td><?= key_status_badge($v) ?></td>
                                    <td>
                                        <span class="countdown" data-expire="<?= $v['expire'] ?>" style="font-weight:700; color:<?= ($v['expire'] > 0 && $v['expire'] < time()) ? '#ef4444' : '#f59e0b' ?>;">
                                            <?= $v['expire'] == 0 ? 'Lifetime' : 'Loading...' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <form method="POST"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="block"><button type="submit" name="action_key" class="action-btn" style="color:var(--success); border-color:var(--success);">Unban</button></form>
                                        <form method="POST" onsubmit="return confirm('Delete permanently?');"><input type="hidden" name="key_id" value="<?= htmlspecialchars($k) ?>"><input type="hidden" name="sub_action" value="delete"><button type="submit" name="action_key" class="action-btn red">Delete</button></form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div id="noBanned" style="display:none; text-align:center; padding:20px; color:var(--text-muted); font-weight:600;">No banned keys found.</div>
                </div>
            </div>
        </div>

        <!-- SEKME 3: CİHAZ BİLGİLERİ -->
        <div id="infoTab" class="content-area">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Devices</div>
                </div>
                <div>
                    <?php 
                    $hasAnyDevice = false;
                    foreach($keys_data as $k => $v): 
                        if (!empty($v['devices'])):
                            $hasAnyDevice = true;
                            $deviceCount = count($v['devices']);
                    ?>
                            <div class="device-key-block">
                                <div class="device-key-header" onclick="toggleDeviceList(this)">
                                    <div class="device-key-title">
                                        <?= htmlspecialchars($k) ?>
                                        <span class="device-count-badge"><?= $deviceCount ?> Device(s)</span>
                                    </div>
                                    <svg class="chevron" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 12 15 18 9"></polyline></svg>
                                </div>
                                
                                <div class="device-list-content">
                                    <?php foreach($v['devices'] as $index => $dev): 
                                        $devId = (is_array($dev)) ? ($dev['id'] ?? 'Unknown') : $dev;
                                        $devModel = (is_array($dev)) ? ($dev['model'] ?? "Unknown Device") : "Unknown Device"; 
                                        $devIP = (is_array($dev)) ? ($dev['ip'] ?? "Unknown IP") : "Unknown IP";
                                        $devDate = (is_array($dev)) ? ($dev['date'] ?? "N/A") : "N/A";
                                        $devBattery = (is_array($dev)) ? ($dev['battery'] ?? "Unknown") : "Unknown";
                                    ?>
                                        <div class="device-item">
                                            <div class="device-item-title">
                                                <span># <?= $index + 1 ?> — <?= htmlspecialchars($devModel) ?></span>
                                                <span style="color:var(--success); font-weight:700;"><?= htmlspecialchars($devBattery) ?></span>
                                            </div>
                                            <div class="device-row"><span>HWID</span> <span><?= htmlspecialchars($devId) ?></span></div>
                                            <div class="device-row"><span>IP</span> <span><?= htmlspecialchars($devIP) ?></span></div>
                                            <div class="device-row"><span>Last Login</span> <span><?= htmlspecialchars($devDate) ?></span></div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                    <?php 
                        endif;
                    endforeach; 
                    if (!$hasAnyDevice) echo '<div style="color:var(--text-muted); text-align: center; padding: 40px; font-weight:600;">No devices found.</div>';
                    ?>
                </div>
            </div>
        </div>

        <!-- SEKME 4: BULK KEY -->
        <div id="bulkTab" class="content-area">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Bulk Key Generator</div>
                </div>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>How many keys?</label>
                            <input type="number" name="bulk_amount" class="form-control" value="10" min="1" max="1000">
                        </div>
                        <div class="form-group">
                            <label>Duration</label>
                            <select name="bulk_duration" class="form-control">
                                <option value="1H">1 Hour</option>
                                <option value="6H">6 Hours</option>
                                <option value="12H">12 Hours</option>
                                <option value="1D">1 Day</option>
                                <option value="7D">7 Days</option>
                                <option value="30D">30 Days</option>
                                <option value="LIFETIME">Lifetime</option>
                                <option disabled>──────────</option>
                                <option value="TEST_1H">1 Hour (Tester)</option>
                                <option value="TEST_1D">1 Day (Tester)</option>
                                <option value="TEST_7D">7 Days (Tester)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Max Devices</label>
                            <input type="number" name="bulk_devices" class="form-control" value="1" min="1">
                        </div>
                    </div>
                    <button type="submit" name="bulk_create" class="btn btn-primary">Generate Keys</button>
                </form>

                <?php if (isset($_SESSION['bulk_generated']) && !empty($_SESSION['bulk_generated'])): ?>
                    <div class="bulk-result-box">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px;">
                            <h3 style="font-size:16px; font-weight:700;">Generated Keys (<?= count($_SESSION['bulk_generated']) ?>)</h3>
                            <button type="button" class="btn btn-outline" onclick="copyBulkKeys()">Copy All</button>
                        </div>
                        <textarea id="bulkTextArea" class="bulk-textarea" readonly><?php 
                            foreach($_SESSION['bulk_generated'] as $k) {
                                echo $k . "\n";
                            }
                        ?></textarea>
                    </div>
                    <?php unset($_SESSION['bulk_generated']); ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- SEKME 5: SETTINGS -->
        <div id="settingsTab" class="content-area">
            <div class="card">
                <div class="card-header">
                    <div class="card-title">Settings</div>
                </div>
                <p style="color:var(--text-muted); margin-bottom:20px; font-size:14px; font-weight:600;">Manage your API settings and background.</p>
                
                <div style="background:#f8fafc; border:1px solid var(--border); border-radius:8px; padding:16px; margin-bottom:16px;">
                    <div style="font-weight:700; margin-bottom:4px;">Background Image</div>
                    <p style="font-size:13px; color:var(--text-muted); margin-bottom:10px;">Upload an image to change the dashboard background.</p>
                    <input type="file" id="bgUpload" accept="image/*" class="form-control" onchange="uploadBackground(this)">
                </div>

                <div style="background:#f8fafc; border:1px solid var(--border); border-radius:8px; padding:16px; margin-bottom:16px;">
                    <div style="font-weight:700; margin-bottom:4px;">API Endpoint</div>
                    <div style="font-family:monospace; color:var(--primary); font-weight:600;">https://a-g9eo.onrender.com/api.php</div>
                </div>

                <div style="border-top:1px solid var(--border); padding-top:20px; margin-top:20px;">
                    <h4 style="color:var(--danger); margin-bottom:12px; font-weight:700;">Danger Zone</h4>
                    <form method="POST" onsubmit="return confirm('Delete ALL keys?');">
                        <button type="submit" name="delete_all" class="btn btn-danger">Delete All Keys</button>
                    </form>
                </div>
            </div>
        </div>

    </main>

    <!-- EDIT KEY MODAL -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Edit Key Name</div>
                <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="old_key" id="editOldKey">
                <div class="form-group">
                    <label>New Key Name</label>
                    <input type="text" name="new_key" id="editNewKey" class="form-control" required>
                </div>
                <button type="submit" name="edit_key_name" class="btn btn-primary" style="width:100%;">Save Changes</button>
            </form>
        </div>
    </div>

    <!-- PROFILE MODAL -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-title">Owner Profile</div>
                <button class="modal-close" onclick="closeModal('profileModal')">&times;</button>
            </div>
            <div class="profile-modal-body">
                <div class="profile-pic-large" onclick="document.getElementById('avatarInput').click()">
                    <span id="modalProfileText">O</span>
                    <img id="modalProfileImg" src="" style="display:none;">
                </div>
                <div class="form-group">
                    <label>Nickname</label>
                    <input type="text" id="inputNickname" class="form-control" placeholder="Enter your nickname">
                </div>
                <div class="form-group">
                    <label>Bio</label>
                    <textarea id="inputBio" class="form-control" rows="3" placeholder="Write something about yourself..."></textarea>
                </div>
                <button type="button" class="btn btn-primary" onclick="saveProfile()">Save Profile</button>
            </div>
        </div>
    </div>

    <!-- GIZLI DOSYA SECME INPUTLARI -->
    <input type="file" id="avatarInput" accept="image/*" style="display:none;" onchange="uploadAvatar(this)">

    <script>
        // --- TAB VE ARAMA FONKSİYONLARI ---
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

        function filterBanned() {
            var input = document.getElementById('searchBanned');
            var filter = input.value.toLowerCase();
            var rows = document.querySelectorAll('.banned-row');
            var visibleCount = 0;
            rows.forEach(function(row) {
                var keyName = row.getAttribute('data-key');
                if (keyName.includes(filter)) {
                    row.style.display = ''; visibleCount++;
                } else { row.style.display = 'none'; }
            });
            document.getElementById('noBanned').style.display = (visibleCount === 0 && rows.length > 0) ? 'block' : 'none';
        }

        function toggleDeviceList(headerElement) {
            var content = headerElement.nextElementSibling;
            var chevron = headerElement.querySelector('.chevron');
            if (content.style.display === "block") {
                content.style.display = "none"; chevron.style.transform = "rotate(0deg)";
            } else {
                content.style.display = "block"; chevron.style.transform = "rotate(180deg)";
            }
        }

        function copyBulkKeys() {
            var copyText = document.getElementById("bulkTextArea");
            copyText.select(); copyText.setSelectionRange(0, 99999);
            navigator.clipboard.writeText(copyText.value).then(function() { alert("All keys copied!"); });
        }

        // --- KEY KOPYALAMA VE DÜZENLEME ---
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                alert("Key copied: " + text);
            });
        }

        function openEditModal(keyName) {
            document.getElementById('editOldKey').value = keyName;
            document.getElementById('editNewKey').value = keyName;
            document.getElementById('editModal').style.display = 'flex';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // --- SAYAÇ MANTIĞI (Countdown) ---
        function updateCountdowns() {
            var countdowns = document.querySelectorAll('.countdown');
            var now = Math.floor(Date.now() / 1000);

            countdowns.forEach(function(el) {
                var expire = parseInt(el.getAttribute('data-expire'));
                if (expire === 0) {
                    el.innerText = "Lifetime";
                    el.style.color = "#8b5cf6";
                    return;
                }

                var diff = expire - now;
                if (diff <= 0) {
                    el.innerText = "Expired";
                    el.style.color = "#ef4444";
                    return;
                }

                var days = Math.floor(diff / 86400);
                var hours = Math.floor((diff % 86400) / 3600);
                var minutes = Math.floor((diff % 3600) / 60);
                var seconds = diff % 60;

                var str = "";
                if (days > 0) str += days + "d ";
                if (hours > 0 || days > 0) str += hours + "h ";
                str += minutes + "m " + seconds + "s";

                el.innerText = str;
            });
        }
        
        // Her saniye sayacı güncelle
        setInterval(updateCountdowns, 1000);
        document.addEventListener("DOMContentLoaded", updateCountdowns);

        // --- PROFIL VE ARKA PLAN MANTIĞI ---
        function openProfileModal() {
            document.getElementById('profileModal').style.display = 'flex';
            document.getElementById('inputNickname').value = localStorage.getItem('ownerNickname') || 'OWNER';
            document.getElementById('inputBio').value = localStorage.getItem('ownerBio') || '';
        }

        function saveProfile() {
            var nick = document.getElementById('inputNickname').value;
            var bio = document.getElementById('inputBio').value;
            
            localStorage.setItem('ownerNickname', nick);
            localStorage.setItem('ownerBio', bio);
            
            document.getElementById('displayNickname').innerText = nick;
            closeModal('profileModal');
            alert("Profile saved!");
        }

        // Avatar Yükleme ve Kırpma
        function uploadAvatar(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = new Image();
                    img.onload = function() {
                        var canvas = document.createElement('canvas');
                        var size = Math.min(img.width, img.height);
                        canvas.width = size; canvas.height = size;
                        var ctx = canvas.getContext('2d');
                        var startX = (img.width - size) / 2;
                        var startY = (img.height - size) / 2;
                        ctx.drawImage(img, startX, startY, size, size, 0, 0, size, size);
                        
                        var croppedImageData = canvas.toDataURL("image/jpeg");
                        localStorage.setItem('ownerAvatar', croppedImageData);
                        applyAvatar(croppedImageData);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function applyAvatar(imgData) {
            // Alt profil
            document.getElementById('profileImg').src = imgData;
            document.getElementById('profileImg').style.display = 'block';
            document.getElementById('profileText').style.display = 'none';
            // Modal içi
            document.getElementById('modalProfileImg').src = imgData;
            document.getElementById('modalProfileImg').style.display = 'block';
            document.getElementById('modalProfileText').style.display = 'none';
            // Sol üst logo
            document.getElementById('topLogoImg').src = imgData;
            document.getElementById('topLogoImg').style.display = 'block';
            document.getElementById('topLogoText').style.display = 'none';
        }

        // Arka Plan Yükleme (Kırpma yok, direkt kaplama)
        function uploadBackground(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var bgData = e.target.result;
                    localStorage.setItem('dashboardBg', bgData);
                    applyBackground(bgData);
                    alert("Background updated!");
                };
                reader.readAsDataURL(input.files[0]);
            }
        }

        function applyBackground(bgData) {
            document.getElementById('dynamic-bg').style.backgroundImage = "url('" + bgData + "')";
        }

        // Sayfa yüklendiğinde hafızadaki ayarları uygula
        window.onload = function() {
            var savedAvatar = localStorage.getItem('ownerAvatar');
            if (savedAvatar) applyAvatar(savedAvatar);
            
            var savedBg = localStorage.getItem('dashboardBg');
            if (savedBg) applyBackground(savedBg);

            var savedNick = localStorage.getItem('ownerNickname');
            if (savedNick) document.getElementById('displayNickname').innerText = savedNick;
        };
    </script>
</body>
</html>
