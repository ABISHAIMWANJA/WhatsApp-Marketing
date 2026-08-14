# Deployment Guide

Two parts: getting the project onto GitHub from your Windows laptop, then onto your VPS.

---

## Part 1 — Push from your laptop to GitHub

Your project folder is `C:\Users\Mwanja\Desktop\WhatsApp Marketing` (446 MB).

Almost all of that size is `vendor/` and `node_modules/`, which must **not** go into
git — they are rebuilt on the server from `composer.json` and `package.json`. The
`.gitignore` in this repo already excludes them. After ignoring them, your repo should
be well under 20 MB.

### Step 1 — Open PowerShell in the project folder

```powershell
cd "C:\Users\Mwanja\Desktop\WhatsApp Marketing"
```

### Step 2 — Check what is actually taking up the space

```powershell
Get-ChildItem -Directory | ForEach-Object {
  $size = (Get-ChildItem $_.FullName -Recurse -File -ErrorAction SilentlyContinue |
           Measure-Object -Property Length -Sum).Sum / 1MB
  "{0,10:N1} MB  {1}" -f $size, $_.Name
} | Sort-Object -Descending
```

Expect `vendor` and `node_modules` to dominate. If something else is large (a database
dump, a folder of videos or images), add it to `.gitignore` before committing.

### Step 3 — Initialise git and pull in the .gitignore

```powershell
git init
git branch -M main
git remote add origin https://github.com/ABISHAIMWANJA/WhatsApp-Marketing.git
git fetch origin claude/whatsapp-marketing-deploy-re00qf
git checkout origin/claude/whatsapp-marketing-deploy-re00qf -- .gitignore DEPLOY.md
```

> Getting the `.gitignore` in place **before** the first `git add` matters. If you stage
> `vendor/` first, it stays in git history even after you delete it, and the repo stays
> huge forever.

### Step 4 — Verify nothing huge is staged

```powershell
git add .
git status --short | Measure-Object -Line
```

If that count is in the tens of thousands, `vendor/` or `node_modules/` slipped through.
Fix it before committing:

```powershell
git rm -r --cached vendor node_modules
```

Confirm the largest staged files are sane:

```powershell
git ls-files -s | ForEach-Object {
  $f = ($_ -split "`t")[1]
  [PSCustomObject]@{ MB = [math]::Round((Get-Item $f).Length/1MB, 2); File = $f }
} | Sort-Object MB -Descending | Select-Object -First 15
```

Nothing should exceed 50 MB. GitHub hard-rejects anything over 100 MB.

### Step 5 — Confirm no secrets are going up

```powershell
git status --short | Select-String "\.env"
```

This must return **nothing**. Your `.env` holds database passwords and WhatsApp API
tokens — it stays on your machine. Commit a `.env.example` with the keys but blank
values instead.

### Step 6 — Commit and push

```powershell
git commit -m "Initial commit: WhatsApp Marketing application"
git push -u origin main
```

If prompted for a password, GitHub needs a **Personal Access Token**, not your account
password. Create one at <https://github.com/settings/tokens> with `repo` scope and paste
it as the password.

---

## Part 2 — Deploy to your VPS

Assumes Ubuntu 22.04/24.04 with root or sudo access.

### Step 1 — Install the stack

This is **Laravel 12** and requires **PHP 8.2 or newer**. PHP 8.3 is used below.

```bash
sudo apt update && sudo apt upgrade -y
sudo apt install -y nginx mysql-server redis-server git unzip curl \
  php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-xml php8.3-curl \
  php8.3-zip php8.3-bcmath php8.3-gd php8.3-intl php8.3-exif

# Composer
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
```

Why these extensions: `gd` + `exif` for `intervention/image` (media processing),
`zip` for `box/spout` and `php_xlsxwriter` (contact import/export), `intl` for
`libphonenumber` (phone number validation), `bcmath` for the payment gateways.

**No Node.js needed.** This project has no `package.json` — front-end assets are
pre-built and committed under `public/dist`. There is nothing to compile.

### Step 2 — Clone the project

```bash
sudo mkdir -p /var/www
cd /var/www
sudo git clone https://github.com/ABISHAIMWANJA/WhatsApp-Marketing.git whatsapp-marketing
cd whatsapp-marketing
```

For a private repo, use a deploy key or a PAT in the clone URL.

### Step 3 — Install dependencies

This project ships without a `composer.lock`, so the **first** deploy uses `update`:

```bash
composer update --no-dev --optimize-autoloader
```

This rebuilds the 300 MB `vendor/` directory you did not push, and writes a
`composer.lock` recording the exact versions it resolved.

Once the app is confirmed working, commit that lock file so future deploys are
reproducible:

```bash
git add composer.lock
git commit -m "Add composer.lock from first deploy"
git push origin main
```

From then on, every deploy uses `install`, which installs those pinned versions
rather than re-resolving:

```bash
composer install --no-dev --optimize-autoloader
```

Two of the dependencies are not plain Packagist installs, so watch for errors here:

- `unn/gettext-blade` is pulled from a GitHub VCS repository declared in
  `composer.json`. If the server cannot reach GitHub, or the package is private,
  this step fails.
- `livelyworks/laraware` is version-constrained as `*`, meaning composer takes the
  newest release available.

If `composer install` reports a missing lock file, see the note below.

> **On `composer.lock`:** if the file exists on your laptop, commit it — it pins every
> dependency to the exact version you tested against, and `composer install` uses it.
> Without it you must run `composer update` on the server instead, which resolves fresh
> versions and can pull in changes you have not tested. Check with `Test-Path composer.lock`
> on Windows; if it is there, `git add composer.lock` and push.

### Step 4 — Configure the environment

```bash
cp .env.example .env
nano .env
php artisan key:generate
```

Set at minimum:

```ini
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
FORCE_HTTPS=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=whatsapp_marketing
DB_USERNAME=wa_user
DB_PASSWORD=<strong-password>

QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=database
```

`APP_DEBUG=false` is not optional in production — leaving it true exposes your database
credentials and full stack traces on any error page.

`QUEUE_CONNECTION` matters just as much. Laravel's default is `sync`, which runs every
job inside the web request — a bulk campaign to a few thousand contacts would hang the
browser and hit PHP's execution timeout. Set it to `redis` (or `database`) and run the
worker from Step 10.

Copy your real credentials across from the `.env` on your laptop — Pusher (live chat),
OpenAI (AI replies), your mail SMTP details, and whichever payment gateway you use
(Stripe / Paystack / Razorpay / YooKassa). Every supported key is listed in
`.env.example`.

### Step 5 — Create the database

```bash
sudo mysql -e "CREATE DATABASE whatsapp_marketing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'wa_user'@'localhost' IDENTIFIED BY '<strong-password>';"
sudo mysql -e "GRANT ALL PRIVILEGES ON whatsapp_marketing.* TO 'wa_user'@'localhost'; FLUSH PRIVILEGES;"

php artisan migrate --force
```

### Step 6 — Set permissions

```bash
php artisan storage:link
sudo chown -R www-data:www-data /var/www/whatsapp-marketing
sudo chmod -R 775 /var/www/whatsapp-marketing/storage /var/www/whatsapp-marketing/bootstrap/cache
```

`storage:link` creates the `public/storage` symlink. It is gitignored (a symlink is
machine-specific), so it must be recreated on every server — without it, uploaded media
and campaign attachments return 404.

### Step 7 — Configure Nginx

```bash
sudo nano /etc/nginx/sites-available/whatsapp-marketing
```

```nginx
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    root /var/www/whatsapp-marketing/public;

    index index.php;
    charset utf-8;
    client_max_body_size 20M;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/whatsapp-marketing /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t && sudo systemctl reload nginx
```

The `root` must point at `public/`, not the project root — otherwise `.env` becomes
downloadable over the web.

### Step 8 — Enable HTTPS

```bash
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com
```

### Step 9 — Cache config and set up the scheduler

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo crontab -u www-data -e
```

Add:

```
* * * * * cd /var/www/whatsapp-marketing && php artisan schedule:run >> /dev/null 2>&1
```

### Step 10 — Queue worker (needed for bulk message sending)

```bash
sudo nano /etc/systemd/system/whatsapp-worker.service
```

```ini
[Unit]
Description=WhatsApp Marketing Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
RestartSec=5
ExecStart=/usr/bin/php /var/www/whatsapp-marketing/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now whatsapp-worker
sudo systemctl status whatsapp-worker
```

### Step 11 — Firewall

```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw enable
```

---

## Redeploying after changes

```bash
cd /var/www/whatsapp-marketing
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
sudo systemctl restart whatsapp-worker
sudo systemctl reload php8.3-fpm
```

---

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| `file is XXX MB; exceeds GitHub's limit` on push | A large file got committed. `git rm --cached <file>`, add it to `.gitignore`, then `git commit --amend`. If it is buried in history, use `git filter-repo`. |
| 500 error, blank page | `tail -f storage/logs/laravel.log`. Usually permissions on `storage/` or a missing `APP_KEY`. |
| `.env` visible in browser | Nginx `root` is pointing at the project root instead of `public/`. |
| `Permission denied` writing logs | Re-run the `chown`/`chmod` from Step 6. |
| Queued messages never send | Worker is down: `sudo systemctl status whatsapp-worker`. |
| Push asks for a password repeatedly | Use a Personal Access Token, not your GitHub password. |

---

## Security checklist before going live

- [ ] `.env` is **not** in the repo (`git log --all --full-history -- .env` returns nothing)
- [ ] `APP_DEBUG=false` and `APP_ENV=production`
- [ ] Nginx `root` ends in `/public`
- [ ] HTTPS enabled via Certbot
- [ ] Database user has a strong, unique password
- [ ] Firewall active; MySQL not exposed publicly
- [ ] WhatsApp session/auth files are gitignored — they grant full account access
- [ ] Automated database backups configured
