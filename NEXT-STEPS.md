# Deployment notes

The app is live at https://whatsapp.bomalogic.com

## How it is deployed

Managed by **Dokploy** on the VPS (84.247.187.164), under project `Whatsapp`,
service `whatsapp-marketing`.

| | |
|---|---|
| composeId | `DuiogBys2ZSAuKUAdLyVi` |
| environmentId | `tnVSrbv6hqgbAyueJJ1SA` |
| container prefix | `compose-bypass-haptic-hard-drive-8xv8wj` |
| source | Git → `https://github.com/ABISHAIMWANJA/WhatsApp-Marketing.git`, branch `main` |
| compose path | `./docker-compose.yml` |

Services: `app` (nginx + PHP-FPM), `queue`, `scheduler`, `mysql`, `redis`. No host
ports are published, so the other applications on this host are unaffected.

**Routing is Dokploy's**, not the compose file's — a domain record
(`whatsapp.bomalogic.com` → service `app`, port 80, Let's Encrypt) injects the
Traefik labels at deploy time. Do not add Traefik labels back into
`docker-compose.yml`: two routers matching the same Host rule is undefined
behaviour.

**To deploy:** push to `main`, then hit **Deploy** in the Dokploy UI. Environment
variables live in Dokploy's Environment tab, not in a `.env` on disk.

`/opt/whatsapp-marketing` is a leftover checkout from the original manual deploy.
It still holds `database.sql` and a `.env`. Keep it stopped — never run
`docker compose up` there, or a second stack will contend for the domain.

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
D=$(docker ps -qf "name=compose-bypass.*mysql")
docker exec -i $D sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" whatsapp_marketing' < database.sql
docker exec -i $D sh -c 'mysql -u root -p"$MYSQL_ROOT_PASSWORD" whatsapp_marketing' < database/schema/laravel-sessions.sql
```

The app containers crash-loop until the tables exist, then recover on their own
within a minute. Expect 48 tables: the vendor's 47 plus `sessions`.

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

- Log in and change the admin password. `admin.txt` from the vendor package holds
  the default, identical for every copy of this script sold.
- Add the integration credentials in Dokploy's **Environment** tab (Pusher, OpenAI,
  SMTP, payment gateway), then redeploy. Until then: no live chat, no AI replies,
  no outgoing email.
- Make the GitHub repository **private**. A commercial licensed script is currently
  world-readable. Dokploy's Git source will then need a deploy key.
- Rotate `APP_KEY` — it was exposed in a chat transcript. Free to change while
  nothing is encrypted with it yet; after real users exist it invalidates sessions
  and encrypted data.
- Back up the secrets somewhere safe. They now live in Dokploy's Environment tab:

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

## Driving Dokploy from the shell

The UI covers everyday use. These are here because the API is undocumented and
the field names took some digging.

Authenticate once per session (keeps the key out of shell history):

```bash
read -rsp "Dokploy API key: " DOKPLOY_KEY; echo; export DOKPLOY_KEY
```

The API is tRPC at `http://localhost:3000/api/trpc/<router>.<procedure>`, POST with
a `{"json":{...}}` body and an `x-api-key` header. To discover a procedure's
schema, POST an empty payload — the Zod error names every required field:

```bash
curl -s -X POST -H "x-api-key: $DOKPLOY_KEY" -H "Content-Type: application/json" \
  -d '{"json":{}}' "http://localhost:3000/api/trpc/domain.create" | head -c 1000
```

Deploy:

```bash
curl -s -X POST -H "x-api-key: $DOKPLOY_KEY" -H "Content-Type: application/json" \
  -d '{"json":{"composeId":"DuiogBys2ZSAuKUAdLyVi"}}' \
  "http://localhost:3000/api/trpc/compose.deploy"
```

Useful procedures: `project.all`, `compose.create`, `compose.update`,
`compose.deploy`, `domain.create`.
