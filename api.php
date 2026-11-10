<?php
header('Content-Type: application/json');

$json_file = "keys.json";
$backup_file = "keys_backup.json";

if (!file_exists($json_file)) {
    file_put_contents($json_file, json_encode([]));
}

$keys_data = json_decode(file_get_contents($json_file), true);
if (!is_array($keys_data)) $keys_data = [];

$input = json_decode(file_get_contents('php://input'), true);
$key = isset($input['key']) ? trim($input['key']) : '';
$device_id = isset($input['device_id']) ? trim($input['device_id']) : '';
$device_model = isset($input['device_model']) ? trim($input['device_model']) : 'Unknown Device';
$battery = isset($input['battery']) ? trim($input['battery']) : 'Unknown';

// IP Adresi Al
if (!empty($_SERVER['HTTP_CLIENT_IP'])) { $ip_address = $_SERVER['HTTP_CLIENT_IP']; } 
elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ip_address = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]; } 
else { $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP'; }

if (empty($key) || empty($device_id)) {
    echo json_encode(["status" => "error", "message" => "Key ve ya Device ID daxil edilmeyib."]); exit;
}

if (!isset($keys_data[$key])) {
    echo json_encode(["status" => "error", "message" => "Key movcud deyil."]); exit;
}

$key_info = $keys_data[$key];

if ($key_info['status'] == 'banned') {
    echo json_encode(["status" => "crash", "message" => "Key bloklandi!"]); exit;
}

// SÜRE KONTROLÜ (Eğer süre başlamışsa ve geçmişse)
if ($key_info['expire'] > 0 && $key_info['expire'] < time()) {
    echo json_encode(["status" => "crash", "message" => "Keyin vaxti bitib!"]); exit;
}

$devices = $key_info['devices'];
$device_exists = false;

foreach ($devices as $dev) {
    $d_id = is_array($dev) ? ($dev['id'] ?? '') : $dev;
    if ($d_id === $device_id) {
        $device_exists = true;
        break;
    }
}

// EĞER SÜRE HENÜZ BAŞLAMADIYSA (expire == 0 ise) VE LIFETIME DEĞİLSE
if ($key_info['expire'] == 0 && isset($key_info['duration']) && $key_info['duration'] != -1) {
    $keys_data[$key]['expire'] = time() + $key_info['duration'];
    $key_info['expire'] = $keys_data[$key]['expire'];
}

if (count($devices) == 0) {
    // İlk cihaz kaydı
    $keys_data[$key]['devices'][] = [
        'id' => $device_id, 'model' => $device_model, 'ip' => $ip_address, 'battery' => $battery, 'date' => date('Y-m-d H:i:s')
    ];
    file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
    file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
    
    echo json_encode(["status" => "success", "message" => "Giris ugurludur. Cihaz qeyde alindi."]); exit;
} 
elseif (!$device_exists) {
    // Yeni cihaz ekleme (Limit kontrolü)
    if (count($devices) >= $key_info['max_devices']) {
        echo json_encode(["status" => "crash", "message" => "Bu key basqa cihazda aktivdir! Limit dolub."]); exit;
    }
    
    $keys_data[$key]['devices'][] = [
        'id' => $device_id, 'model' => $device_model, 'ip' => $ip_address, 'battery' => $battery, 'date' => date('Y-m-d H:i:s')
    ];
    file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));
    file_put_contents($backup_file, json_encode($keys_data, JSON_PRETTY_PRINT));
    
    echo json_encode(["status" => "success", "message" => "Yeni cihaz qeyde alindi."]); exit;
}

// Kayıtlı cihaz tekrar giriş yaparsa pil bilgisini güncelle
foreach ($keys_data[$key]['devices'] as &$dev) {
    if (is_array($dev) && ($dev['id'] ?? '') === $device_id) {
        $dev['battery'] = $battery;
    }
}
unset($dev);
file_put_contents($json_file, json_encode($keys_data, JSON_PRETTY_PRINT));

echo json_encode(["status" => "success", "message" => "Giris ugurludur."]);
exit;
?>
