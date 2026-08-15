# Deployment notes

The app is live at https://whatsapp.bomalogic.com

## How it is deployed

Plain `docker compose` from `/opt/whatsapp-marketing` on the VPS (84.247.187.164),
routed by the Dokploy-managed Traefik. It publishes no host ports, so the other
applications on that host are unaffected. Because it was not created through
Dokploy, it does **not** appear in the Dokploy UI — see "Moving into Dokploy".

Services: `app` (nginx + PHP-FPM), `queue`, `scheduler`, `mysql`, `redis`.

## Database

This script ships **no Laravel migrations** — `database/` contains only `factories`
and `seeders`. The schema comes from `database.sql` in the vendor package
(`Launch Your WhatsApp Marketing SaaS Business…zip` → `Upload_Code.zip`), which
creates 47 tables. It carries no `CREATE DATABASE`/`USE`, so it imports directly.

Laravel's own `sessions` table is **not** in that dump and must be added
separately from `database/schema/laravel-sessions.sql`. `jobs` and `failed_jobs`
are included, so the database queue driver needs nothing extra.

Rebuilding the database from scratch:

```bash
cd /opt/whatsapp-marketing
docker compose exec -T mysql sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" whatsapp_marketing' < database.sql
docker compose exec -T mysql sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" whatsapp_marketing' < database/schema/laravel-sessions.sql
docker compose restart app
```

## Two failure modes worth remembering

The app queries `configurations` during boot (`AppServiceProvider::boot()` →
`getAppSettings('enable_stripe')`) with no guard for a missing table. On an empty
database *every* artisan command fails, including `migrate` — the framework cannot
boot far enough to run it.

With `SESSION_DRIVER=database` and no `sessions` table, the stack looks healthy
while every page 500s: `/up` never starts a session, so the health check and
`docker compose ps` both report success. Trust the browser over the health check.

## Old notes — finding the schema

### Locating the vendor dump (already done)

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

## Also required: Laravel's sessions table

The vendor's `database.sql` creates their 47 application tables but not
Laravel's own `sessions` table, which normally comes from framework
migrations this app does not ship. With `SESSION_DRIVER=database` the
result is deceptive: `/up` returns 200 and the containers report healthy,
while every real page returns 500 from `DatabaseSessionHandler->read()`.

Import it once, after the vendor dump:

```bash
docker compose exec mysql sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" whatsapp_marketing' \
  < database/schema/laravel-sessions.sql
docker compose restart app
```

`jobs` and `failed_jobs` *are* in the vendor dump, so `QUEUE_CONNECTION=database`
needs nothing extra.

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
- A pending kernel update and reboot are outstanding on the host.

## Moving into Dokploy

To manage this from the Dokploy UI alongside the other apps. The compose file and
Dockerfile do not change — only who runs them. This recreates the containers and
the MySQL volume, so the database must be re-imported afterwards.

1. Copy the current secrets out of `/opt/whatsapp-marketing/.env` — reuse the same
   `APP_KEY`, or every existing session and encrypted value becomes unreadable.
2. Stop the manual stack so it does not contend for the domain:
   `cd /opt/whatsapp-marketing && docker compose down` (volumes are kept).
3. Dokploy → Create → **Docker Compose**; source GitHub, repo
   `ABISHAIMWANJA/WhatsApp-Marketing`, branch `main`, compose path
   `docker-compose.yml`.
4. Domain `whatsapp.bomalogic.com`, service `app`, container port `80`, HTTPS with
   Let's Encrypt. Map no host ports.
5. Paste the environment, then Deploy.
6. Re-import `database.sql` and `database/schema/laravel-sessions.sql` into the new
   MySQL container (see "Database" above).

The manual checkout at `/opt/whatsapp-marketing` can stay as a fallback; leave it
stopped so the two stacks never both claim the domain.
