# Deployment Guide

Two parts: getting the project onto GitHub from your Windows laptop, then onto your VPS.

---

## Part 1 — Push from your laptop to GitHub

Your project folder is `C:\Users\Mwanja\Desktop\WhatsApp Marketing` (446 MB).

300 MB of that is `vendor/`, which must **not** go into git — it is rebuilt on the
server from `composer.json`. The `.gitignore` in this repo already excludes it. Once
ignored, the repo comes to roughly 19 MB.

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

Expect `vendor` to dominate. If something else is large (a database dump, a folder of
videos or images), add it to `.gitignore` before committing.

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

If that count is in the tens of thousands, `vendor/` slipped through. Fix it before
committing:

```powershell
git rm -r --cached vendor
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

## Part 2 — Deploy to the VPS via Dokploy

### ⚠️ Read this first

This VPS is **not** a bare server. It already runs Dokploy with **Traefik bound to
ports 80 and 443**, fronting several live applications (Chatwoot, n8n, Documenso,
Botpress, bomalogic-app, bomasheet).

**Do not `apt install nginx` and do not run `certbot` on this host.** Either would
contend with Traefik for port 80 and can take down every site on the box. All routing
and TLS is Traefik's job.

The app therefore deploys as a Docker Compose stack that publishes **no host ports**.
Traefik reaches it over the `dokploy-network` bridge.

### What gets deployed

| Service | Role | Host port |
|---|---|---|
| `app` | nginx + PHP-FPM (supervisor), serves the site | none — Traefik routes to it |
| `queue` | `queue:work`, sends campaigns in the background | none |
| `scheduler` | `schedule:work`, replaces the crontab | none |
| `mysql` | MySQL 8, capped at a 256 MB buffer pool | none |
| `redis` | Cache only, 128 MB cap | none |

### Step 1 — Check the server has room

The stack adds roughly 700 MB–1 GB of resident memory. Your last login reported
**55% memory in use and 72% swap**, which is already tight.

```bash
free -h
df -h /
docker stats --no-stream --format "table {{.Name}}\t{{.MemUsage}}"
```

If free memory is under ~1.5 GB, reduce usage before deploying — stop an unused
Dokploy app, or lower `--innodb-buffer-pool-size` in `docker-compose.yml`. Running
MySQL on a swapping host causes slow queries and eventual OOM kills.

There is also a **pending kernel restart** on that server. Reboot at a quiet moment
before adding another workload:

```bash
sudo reboot
```

### Step 2 — Generate the APP_KEY

Laravel encrypts sessions and stored credentials with this key. Generate it **once**
and keep it forever — changing it logs out every user and makes already-encrypted data
unreadable.

```bash
docker run --rm php:8.3-cli-alpine php -r \
  'echo "base64:".base64_encode(random_bytes(32))."\n";'
```

Copy the output. The container refuses to boot without it, deliberately — a
runtime-generated key would differ per container and corrupt sessions.

### Step 3 — Create the application in Dokploy

Open your Dokploy dashboard, then:

1. **Create Application** → type **Docker Compose**
2. **Source**: GitHub → `ABISHAIMWANJA/WhatsApp-Marketing`, branch `main`
   (authorise the GitHub provider first if you have not already)
3. **Compose path**: `docker-compose.yml`
4. **Domain**: `whatsapp.bomalogic.com`
   - Service: `app`, container port `80`
   - Enable **HTTPS** and select **Let's Encrypt**
5. Do **not** map any host ports.

### Step 4 — Set the environment

In the application's **Environment** tab, paste the contents of `.env.example` and fill
in the real values. At minimum:

```ini
APP_NAME="WhatsApp Marketing"
APP_ENV=production
APP_KEY=base64:<the key from Step 2>
APP_DEBUG=false
APP_URL=https://whatsapp.bomalogic.com
FORCE_HTTPS=true

DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=whatsapp_marketing
DB_USERNAME=wa_user
DB_PASSWORD=<strong password>
DB_ROOT_PASSWORD=<different strong password>

REDIS_HOST=redis
CACHE_STORE=redis
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

`DB_HOST` is `mysql`, **not** `127.0.0.1` — each service is its own container, and
localhost inside the app container is the app container.

Then copy your integration credentials across from the `.env` on your laptop: Pusher,
OpenAI, SMTP, and whichever payment gateway you use. `.env.example` lists every key the
app reads.

> If any of those keys has previously been committed to a repo or shared over chat,
> rotate it at the provider now rather than after go-live.

### Step 5 — Deploy

Hit **Deploy**. The first build takes 5–10 minutes: it compiles the PHP extensions and
resolves all 26 Composer packages.

Watch the logs for:

- `[entrypoint] database is reachable`
- `[entrypoint] running migrations`
- `[entrypoint] ready — starting: supervisord`

The build resolves dependencies with `composer update` because this project ships no
lock file. See Step 7 to make subsequent builds reproducible.

### Step 6 — Verify

```bash
curl -I https://whatsapp.bomalogic.com/up
```

Expect `HTTP/2 200`. Then in a browser, confirm the login page loads over HTTPS with a
valid certificate.

If Traefik returns 404, the domain is not bound to the `app` service — recheck Step 3.
If it returns 502, the container is unhealthy; check its logs.

Confirm the background services are alive:

```bash
docker compose ps
docker compose logs --tail=50 queue scheduler
```

Then send a small test campaign — two or three contacts — and watch the queue log to
confirm jobs are picked up. A campaign that stays "pending" means the worker is not
running or `QUEUE_CONNECTION` is still `sync`.

### Step 7 — Pin the dependency versions

Once the app is confirmed working, capture the resolved versions so future builds are
reproducible:

```bash
CID=$(docker compose ps -q app)
docker cp "$CID":/var/www/html/composer.lock ./composer.lock
```

Commit that file from your laptop (or directly on the server if the repo is checked out
there) and push. Subsequent builds then use `composer install` automatically.

### Step 8 — Back up the database

Nothing on this stack is backed up by default. The volume survives container restarts
but not a volume deletion or a disk failure.

```bash
mkdir -p /root/backups
cat > /root/backup-whatsapp.sh <<'SH'
#!/bin/bash
set -e
CID=$(docker ps -qf "name=mysql" -f "label=com.docker.compose.project=whatsapp-marketing" | head -1)
STAMP=$(date +%F-%H%M)
docker exec "$CID" mysqldump -u root -p"$DB_ROOT_PASSWORD" --single-transaction \
  whatsapp_marketing | gzip > "/root/backups/wa-$STAMP.sql.gz"
find /root/backups -name 'wa-*.sql.gz' -mtime +14 -delete
SH
chmod +x /root/backup-whatsapp.sh
```

Adjust the container filter to match the project name Dokploy assigns, then schedule it:

```bash
crontab -e
# 0 3 * * * DB_ROOT_PASSWORD='<root password>' /root/backup-whatsapp.sh
```

Copy the dumps off the server periodically — a backup on the same disk as the database
protects against mistakes, not hardware failure.

---
## Redeploying after changes

Push to `main`, then hit **Deploy** in Dokploy (or enable auto-deploy on push, which
Dokploy drives from a GitHub webhook).

The `app` container runs migrations on boot and rebuilds the config, route, and view
caches, so there is nothing to run by hand.

One thing to be aware of: `opcache.validate_timestamps=0` means PHP never re-reads
changed source files. Editing files inside a running container has no effect. Every
change requires a rebuild — which is exactly what Dokploy does.

---

## Troubleshooting

| Symptom | Cause / fix |
|---|---|
| `FATAL: APP_KEY is not set` | Set `APP_KEY` in the Dokploy environment (Step 2). |
| `ERROR: database unreachable after 120s` | MySQL failed to start. `docker compose logs mysql` — usually a bad `DB_ROOT_PASSWORD` or a full disk. |
| Traefik returns **404** | The domain is not bound to the `app` service, or DNS has not propagated. `dig +short whatsapp.bomalogic.com` should return `84.247.187.164`. |
| Traefik returns **502** | The app container is unhealthy. `docker compose logs app`. |
| Site loads but CSS/JS 404 | `APP_URL` is wrong, or `FORCE_HTTPS` is false while Traefik serves https. |
| Redirect loop | `FORCE_HTTPS=true` plus a Traefik http→https redirect. Confirm `TrustProxies` is active. |
| Login page loads, session never persists | `SESSION_SECURE_COOKIE=true` served over http, or `SESSION_DOMAIN` set to the wrong host. |
| Campaigns stay "pending" | Queue worker down (`docker compose logs queue`) or `QUEUE_CONNECTION=sync`. |
| Uploaded media 404s | `storage:link` did not run. Check the entrypoint log. |
| Certificate not issued | Let's Encrypt needs port 80 reachable. Confirm the firewall allows it and DNS resolves. |
| Out-of-memory / container killed | The host is swapping. Lower `--innodb-buffer-pool-size`, or free memory elsewhere. |

Useful commands:

```bash
docker compose ps
docker compose logs -f app
docker compose logs --tail=100 queue
docker compose exec app php artisan about
docker compose exec app php artisan queue:failed
```

---

## Security checklist before going live

- [ ] `.env` is **not** in the repo — `git log --all --full-history -- .env` returns nothing
- [ ] `APP_DEBUG=false` and `APP_ENV=production`
- [ ] `APP_KEY` set once and recorded somewhere safe (a password manager, not the repo)
- [ ] Distinct strong passwords for `DB_PASSWORD` and `DB_ROOT_PASSWORD`
- [ ] No host ports published by this stack — `docker compose ps` shows no `0.0.0.0:` mappings
- [ ] HTTPS serving a valid Let's Encrypt certificate
- [ ] Any credential previously committed or shared has been rotated at the provider
- [ ] Database backups scheduled **and** a restore tested at least once
- [ ] SSH hardened: key-based auth, root password login disabled
- [ ] The pending kernel update applied and the host rebooted

> On SSH: you logged in as `root` with a password. Move to key-based authentication and
> set `PermitRootLogin prohibit-password` in `/etc/ssh/sshd_config`. This box hosts
> several production applications on a public IP, and password auth on root is the most
> commonly brute-forced entry point there is.
