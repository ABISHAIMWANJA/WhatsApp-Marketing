# Where this stopped — resume here

Everything is deployed and running except the database schema.

## State

Working:

- Code on GitHub, `main` branch (19 MB; `vendor/` excluded and rebuilt on the server)
- Docker stack builds cleanly and all five containers start:
  `app`, `queue`, `scheduler`, `mysql`, `redis`
- Checked out on the VPS at `/opt/whatsapp-marketing`
- `.env` generated on the server with `APP_KEY` and both DB passwords (mode 0600)
- Traefik routing configured for `whatsapp.bomalogic.com` with Let's Encrypt

Blocked:

- The `configurations` table does not exist, so the app cannot boot.

## Why

This script has **no Laravel migrations** — `database/` contains only `factories`
and `seeders`. There is no installer route, no `install/` directory, and no artisan
install command. The schema ships as a **SQL dump in the vendor's package**, and
`.gitignore` excludes `*.sql`, so it never reached GitHub.

The app queries `configurations` during boot (`AppServiceProvider::boot()` →
`getAppSettings('enable_stripe')`), with no guard for a missing table. That is why
even `php artisan migrate` fails — the framework cannot boot far enough to run it.

## To finish

1. On the laptop, find the SQL dump — either in
   `C:\Users\Mwanja\Desktop\WhatsApp Marketing` or in the original vendor package
   (look in `Documentation/`, `install/`, `database/`, `files/`):

   ```powershell
   cd "C:\Users\Mwanja\Desktop\WhatsApp Marketing"
   Get-ChildItem -Recurse -File -Include *.sql,*.sql.gz -ErrorAction SilentlyContinue |
     Select-Object FullName, @{n='MB';e={[math]::Round($_.Length/1MB,2)}}
   ```

2. Check the top of the file before importing. If it contains `CREATE DATABASE` or
   `USE <name>`, it will write to the wrong database — strip those lines first:

   ```powershell
   Get-Content schema.sql -TotalCount 30
   ```

3. Copy it to the server (run on the **laptop**, with the real path):

   ```powershell
   scp "C:\path\to\schema.sql" root@84.247.187.164:/opt/whatsapp-marketing/schema.sql
   ```

4. Import and bring the stack up (run on the **VPS**):

   ```bash
   cd /opt/whatsapp-marketing
   ls -lh schema.sql
   docker compose cp schema.sql mysql:/tmp/schema.sql
   docker compose exec mysql sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" whatsapp_marketing < /tmp/schema.sql'
   docker compose exec mysql sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" -e "SHOW TABLES;" whatsapp_marketing' | head
   ./scripts/deploy.sh
   ```

5. Verify:

   ```bash
   curl -I https://whatsapp.bomalogic.com/up      # expect HTTP/2 200
   docker compose ps
   docker compose logs --tail=30 app
   ```

   Do not commit the dump — it may carry seed or customer data. Copy it directly to
   the server, and delete `schema.sql` from `/opt/whatsapp-marketing` afterwards.

## Then

- Add the integration credentials to `.env` on the server (Pusher, OpenAI, SMTP,
  payment gateway), then `docker compose up -d`. Copy them from the laptop's `.env`.
- Back up the generated secrets somewhere safe — they exist only in the server's
  `.env`, and losing `APP_KEY` makes encrypted data unrecoverable:

  ```bash
  grep -E '^(APP_KEY|DB_PASSWORD|DB_ROOT_PASSWORD)=' /opt/whatsapp-marketing/.env
  ```

- Test **PDF export** specifically. `dompdf` was bumped from a pinned `3.1.0` to
  `^3.1.6` to clear six security advisories that blocked the build. Patch-level, but
  PDF generation is the one feature that touches it.
- Pin dependency versions so rebuilds are reproducible:

  ```bash
  docker compose cp app:/var/www/html/composer.lock ./composer.lock
  git add composer.lock && git commit -m "Add composer.lock" && git push
  ```

- Set the GitHub default branch to `main` (Settings → General). It is still the
  `claude/…` branch, which holds only two files — a fresh `git clone` checks that out
  and appears almost empty.

## Loose ends

- Rotate the Dokploy API key if it has not already been rotated; earlier keys were
  pasted into a chat transcript.
- SSH uses root password auth. Move to key-based auth and set
  `PermitRootLogin prohibit-password` — this host runs six other production apps.
- This stack was deployed with plain `docker compose`, so it does **not** appear in
  the Dokploy dashboard. To manage it there instead, create a Dokploy
  *Docker Compose* application pointed at this repo, service `app`, port `80`,
  domain `whatsapp.bomalogic.com`. The compose file and Dockerfile do not change.
- A pending kernel update and reboot are outstanding on the host.
