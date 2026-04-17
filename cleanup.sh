#!/bin/bash
# --------------------------------------------------
# NetraX-2.0 — Cleanup Utility
# Removes all captured data, logs, and temp files
# Created, Developed & Maintained by GarudaNetra
# --------------------------------------------------

# ── Colour definitions ────────────────────────────────────────
GREEN="\e[1;92m"
YELLOW="\e[1;93m"
RED="\e[1;91m"
RESET="\e[0m"

# ── Resolve absolute path to the project root ─────────────────
# BASH_SOURCE[0] is always the script itself, even when sourced.
# cd + pwd gives us a clean, symlink-resolved absolute path.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DATA_DIR="${SCRIPT_DIR}/data"

# ── Banner ────────────────────────────────────────────────────
printf "${YELLOW}\n[*] Starting cleanup...${RESET}\n\n"

# ── 1. Cloudflare / tunnel logs ──────────────────────────────
# Remove any .log files left behind by cloudflared, PHP server, or the tunnel wrapper.
printf "${GREEN}[+]${RESET} Removing tunnel and server logs...\n"
rm -f "${SCRIPT_DIR}/.cloudflared.log"
rm -f "${SCRIPT_DIR}/.php_server.log"
rm -f "${SCRIPT_DIR}"/*.log

# ── 2. Generated index files ─────────────────────────────────
# These are written at runtime by netraX-2.0.sh and must be wiped on cleanup.
printf "${GREEN}[+]${RESET} Removing generated index files...\n"
rm -f "${SCRIPT_DIR}/index.php"
rm -f "${SCRIPT_DIR}/index2.html"
rm -f "${SCRIPT_DIR}/index3.html"

# ── 3. Root-level stale files (legacy guard) ──────────────────
# Older versions of NetraX-2.0 wrote output files here; keep this block
# so those installs are also cleaned correctly.
printf "${GREEN}[+]${RESET} Removing any legacy root-level files...\n"
rm -f "${SCRIPT_DIR}/ip.txt"
rm -f "${SCRIPT_DIR}"/location_*.txt
rm -f "${SCRIPT_DIR}/current_location.txt" "${SCRIPT_DIR}/current_location.bak"
rm -f "${SCRIPT_DIR}"/cam*.png
rm -f "${SCRIPT_DIR}/LocationLog.log" "${SCRIPT_DIR}/LocationError.log" "${SCRIPT_DIR}/Log.log"
rm -f "${SCRIPT_DIR}/saved.locations.txt"

# ── 4. Clean the data/ directory ─────────────────────────────
printf "${GREEN}[+]${RESET} Cleaning data folder...\n"

# Safety guard — abort immediately if DATA_DIR somehow resolves outside
# the project root (e.g. a symlink attack or wrong SCRIPT_DIR).
case "${DATA_DIR}" in
    "${SCRIPT_DIR}/"*)
        : # path is inside the project — safe to continue
        ;;
    *)
        printf "${RED}[!] SAFETY ABORT: data/ resolves outside the project directory. Aborting.${RESET}\n"
        exit 1
        ;;
esac

# Check whether the data/ directory actually exists before touching it.
if [ ! -d "${DATA_DIR}" ]; then
    printf "${YELLOW}[!] data/ directory not found — nothing to clean.${RESET}\n"
else
    # Count every file at any depth (including hidden dot-files).
    # -mindepth 1 ensures we never count the data/ folder itself.
    FILE_COUNT=$(find "${DATA_DIR}" -mindepth 1 -type f | wc -l | tr -d ' ')

    if [ "${FILE_COUNT}" -eq 0 ]; then
        # Directory exists but contains no files — report and move on.
        printf "${YELLOW}[*] No files to clean.${RESET}\n"
    else
        printf "${GREEN}[+]${RESET} Found ${FILE_COUNT} file(s) — removing...\n"

        # Iterate over every regular file inside data/ (including hidden files
        # and files inside sub-directories such as otps/, submissions/, etc.).
        # -mindepth 1  → never targets data/ itself
        # -type f       → only regular files, not directories
        # -print0       → NUL-delimited output, safe for filenames with spaces
        ERRORS=0
        while IFS= read -r -d '' FILE; do
            if ! rm -f -- "${FILE}" 2>/dev/null; then
                printf "${RED}[!] Permission denied — could not remove: ${FILE}${RESET}\n"
                ERRORS=$((ERRORS + 1))
            fi
        done < <(find "${DATA_DIR}" -mindepth 1 -type f -print0)

        # Also clean up any dangling symlinks left inside data/.
        find "${DATA_DIR}" -mindepth 1 -type l -delete 2>/dev/null

        if [ "${ERRORS}" -gt 0 ]; then
            printf "${RED}[!] ${ERRORS} file(s) could not be removed due to permission issues.${RESET}\n"
        else
            printf "${GREEN}[+]${RESET} All files inside data/ removed successfully.\n"
        fi
    fi
fi

# ── Done ──────────────────────────────────────────────────────
printf "\n${YELLOW}[*] Cleanup completed successfully.${RESET}\n\n"