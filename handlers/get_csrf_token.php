<?php
/* ════════════════════════════════════════════════════════════════════
   get_csrf_token.php  —  Issues a per-session CSRF token
   Called by the gift form JS before form submission.
   ════════════════════════════════════════════════════════════════════ */
session_start();
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');

if (empty($_SESSION['gift_csrf_token'])) {
    $_SESSION['gift_csrf_token'] = bin2hex(random_bytes(32));
}

echo json_encode(['token' => $_SESSION['gift_csrf_token']]);
