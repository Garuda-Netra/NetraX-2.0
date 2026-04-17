# NetraX ver-2.0
### *Penetration Testing & Social Engineering Framework*

**⚠️ Legal Notice:** Use only for authorized penetration testing with written consent. Unauthorized access to computer systems is illegal.

---

## What is NetraX?

NetraX is a penetration testing toolkit that creates convincing decoy pages to collect visitor data and test security awareness. It includes fake gift portals, video conference pages, and account reporting forms.

### What It Collects
- **Device info** — Real IP address, browser, OS, device type
- **Camera access** — Front camera snapshots (if user grants permission)
- **Location data** — GPS coordinates with Google Maps link (if user grants permission)
- **Form data** — Names, emails, phones, credentials
- **OTP codes** — From social media verification templates

All data is saved in the `data/` folder with timestamps.

---

## Key Features

✅ Real IP detection (Cloudflare & IPv6 aware)  
✅ Device fingerprinting (Desktop, Tablet, Mobile)  
✅ Camera capture with permission  
✅ GPS location tracking  
✅ Form credential collection  
✅ CSRF token protection  
✅ Free tunnel support (Cloudflare Quick Tunnel or Ngrok)  
✅ Works on Linux, Termux, macOS, Windows  
✅ Auto-cleanup command  

---

## Templates

| Template | Purpose |
|----------|---------|
| Gift Claim Portal | Festival greeting with gift claim form |
| YouTube Live Stream | Fake live stream player |
| Virtual Meeting Room | Conference entry page |
| Social Media Report Center | Account verification with credential & OTP capture |

The Social Media Report template is the most advanced — it looks like a real reporting portal and collects credentials for platforms like Facebook, Instagram, WhatsApp, and Telegram.

---

## Setup & Installation

### Requirements
- PHP
- wget
- bash

### Linux / Kali / Ubuntu / Parrot

```bash
# 1. Install dependencies
sudo apt update
sudo apt install php wget -y

# 2. Clone the repo
git clone https://github.com/Garuda-Netra/NetraX-2.0
cd NetraX-2.0

# 3. Run it
chmod +x netraX-2.0.sh
bash netraX-2.0.sh
```

### Termux (Android)

```bash
# 1. Update and install
pkg update -y
pkg install php wget git -y

# 2. Clone and run
git clone https://github.com/Garuda-Netra/NetraX-2.0
cd NetraX-2.0
bash netraX-2.0.sh
```

### macOS

```bash
# 1. Install with Homebrew
brew install php wget

# 2. Clone and run
git clone https://github.com/Garuda-Netra/NetraX-2.0
cd NetraX-2.0
bash netraX-2.0.sh
```

---

## How It Works

1. **Start the tool** — Runs a local PHP server
2. **Get a public link** — Uses Cloudflare Quick Tunnel or Ngrok to create a shareable URL
3. **Send the link** — Share with your test target
4. **Data collection** — All interactions are logged in the `data/` folder

The collected data is timestamped and organized by visitor.

---

## Clean Up

To delete all captured data:

```bash
bash cleanup.sh
```

This removes everything in the `data/` folder.
The menu will guide you through setup:
1. Choose a tunnel (Cloudflare Quick Tunnel or Ngrok)
2. Select a template (1-4)
3. Enter template-specific details if needed

You'll get a live public URL to share with test targets within seconds.

---

## Data Collection

All captured data is saved in the `data/` folder:
- **Visitor logs** — IP addresses, browsers, device info
- **GPS location files** — If user grants permission
- **Camera captures** — If user grants permission  
- **Form submissions** — Names, emails, credentials, OTP codes
- **WebRTC IP data** — Internal and VPN IP detection

---

## Legal Warning

⚠️ **Important**: This tool is for authorized penetration testing only. You must have:
- Written permission from the account/organization owner
- A formal testing agreement in place
- Clear scope defining what you can test

Unauthorized use is illegal and may result in prosecution.

*"सत्यमेव जयते" — Truth alone triumphs.*
