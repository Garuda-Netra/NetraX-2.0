<?php
/* ════════════════════════════════════════════════════════════════════
   gift_handler.php  —  Gift Request Backend Handler
   Handles form submission: validates, saves, logs to terminal output.
   ════════════════════════════════════════════════════════════════════ */

session_start();

/* ── CORS / Headers ─────────────────────────────────────────── */
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');

/* ── Only accept POST ───────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

/* ════════════════════════════════════════════════════════════
   CSRF TOKEN VALIDATION
   ════════════════════════════════════════════════════════════ */
if (empty($_SESSION['gift_csrf_token'])) {
    $_SESSION['gift_csrf_token'] = bin2hex(random_bytes(32));
}

$submitted_token = trim($_POST['csrf_token'] ?? '');
if (!hash_equals($_SESSION['gift_csrf_token'], $submitted_token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid security token. Please reload the page.']);
    exit;
}

/* Regenerate token after use (one-time tokens) */
$_SESSION['gift_csrf_token'] = bin2hex(random_bytes(32));

/* ════════════════════════════════════════════════════════════
   SANITISATION HELPER
   ════════════════════════════════════════════════════════════ */
function clean(string $val): string {
    return htmlspecialchars(strip_tags(trim($val)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/* ════════════════════════════════════════════════════════════
   INPUT COLLECTION
   ════════════════════════════════════════════════════════════ */
$full_name   = clean($_POST['full_name']   ?? '');
$email       = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$phone       = clean($_POST['phone']       ?? '');
$address     = clean($_POST['address']     ?? '');
$aadhaar     = clean($_POST['aadhaar']     ?? '');
$dob         = clean($_POST['dob']         ?? '');
$message     = clean($_POST['gift_message'] ?? '');
$consent     = isset($_POST['consent']) ? true : false;

/* ════════════════════════════════════════════════════════════
   SERVER-SIDE VALIDATION
   ════════════════════════════════════════════════════════════ */
$errors = [];

if (strlen($full_name) < 2) {
    $errors[] = 'Full name is required (minimum 2 characters).';
}

if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if (strlen(trim($address)) < 10) {
    $errors[] = 'Delivery address is required (minimum 10 characters).';
}

/* Aadhaar: exactly 12 digits */
$aadhaar_clean = preg_replace('/\s+/', '', $aadhaar);
if (!preg_match('/^\d{12}$/', $aadhaar_clean)) {
    $errors[] = 'Aadhaar number must be exactly 12 digits.';
}

/* Phone: required, must be 10–15 digits */
$phone_clean = preg_replace('/[\s\-\(\)\+]/', '', $phone);
if (empty($phone_clean)) {
    $errors[] = 'Phone number is required.';
} elseif (!preg_match('/^\d{10,15}$/', $phone_clean)) {
    $errors[] = 'Phone number must contain 10–15 digits.';
}

/* Date of Birth: required, valid past date, reasonable age (0–120 years) */
if (empty($dob)) {
    $errors[] = 'Date of Birth is required.';
} else {
    $dob_ts = strtotime($dob);
    if ($dob_ts === false || $dob_ts >= time()) {
        $errors[] = 'Date of Birth must be a valid past date.';
    } elseif ($dob_ts < strtotime('-120 years')) {
        $errors[] = 'Date of Birth appears to be invalid (exceeds 120 years).';
    }
}

if (!$consent) {
    $errors[] = 'You must agree to the consent notice to proceed.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['status' => 'error', 'message' => implode(' | ', $errors)]);
    exit;
}

/* ════════════════════════════════════════════════════════════
   FILE SAVE  →  /data/form_submissions.txt
   ════════════════════════════════════════════════════════════ */
$data_dir  = dirname(__DIR__) . '/data';
$data_file = $data_dir . '/form_submissions.txt';

/* Create directory with secure permissions if it doesn't exist */
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0750, true);
}

$timestamp = date('Y-m-d H:i:s T');

$record = implode("\n", [
    '--------------------------------',
    'Date:     ' . $timestamp,
    'Name:     ' . $full_name,
    'DOB:      ' . $dob,
    'Email:    ' . $email,
    'Aadhaar:  ' . $aadhaar_clean,
    'Phone:    ' . ($phone ?: 'N/A'),
    'Address:  ' . $address,
    'Message:  ' . ($message ?: '(none)'),
    '--------------------------------',
]) . "\n\n";

/* Append-only write with exclusive lock */
$fp = fopen($data_file, 'a');
if ($fp) {
    flock($fp, LOCK_EX);
    fwrite($fp, $record);
    flock($fp, LOCK_UN);
    fclose($fp);

    /* Secure file permissions after first write */
    chmod($data_file, 0640);

    /* Signal shell script that a new form submission arrived */
    file_put_contents($data_dir . '/.flag_form', $record);
} else {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error: unable to save data.']);
    exit;
}

/* ════════════════════════════════════════════════════════════
   TERMINAL OUTPUT  (error_log → stderr / server log)
   ════════════════════════════════════════════════════════════ */

$divider = str_repeat('-', 44);

$terminal_log = "\n" . $divider . "\n"
    . "🎁  New Gift Request Received\n"
    . "Name    : " . $full_name   . "\n"
    . "DOB     : " . $dob         . "\n"
    . "Time    : " . $timestamp   . "\n"
    . $divider . "\n"
    . "Email   : " . $email       . "\n"
    . "Phone   : " . ($phone ?: 'N/A') . "\n"
    . "Aadhaar : " . $aadhaar_clean . "\n"
    . "Address : " . $address     . "\n"
    . "Message : " . ($message ?: '(none)') . "\n"
    . $divider . "\n";

error_log($terminal_log);

/* Also write directly to stderr so it always appears in the PHP server terminal
   regardless of php.ini error_log setting */
file_put_contents('php://stderr', $terminal_log);

/* ════════════════════════════════════════════════════════════
   SUCCESS RESPONSE
   ════════════════════════════════════════════════════════════ */
echo json_encode([
    'status'  => 'success',
    'message' => 'Thank you, ' . htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8') . '! Your details have been verified. We will proceed with sending your gift shortly.',
]);
exit;
