# Deploying ViralPulse

Written for a single Linux VPS with nginx, PHP-FPM 8.2 and MySQL/MariaDB. The
same steps work on shared hosting that gives you SSH and a cron entry; the only
part you cannot do without SSH is the queue worker, and there is a fallback for
that at the bottom.

---

## 1. Server requirements

```
PHP 8.2+   with gd, mbstring, pdo_mysql, fileinfo, openssl, curl, zip
MySQL 8    or MariaDB 10.4+   (InnoDB — the search uses FULLTEXT)
nginx      or Apache with mod_rewrite
Composer 2
Node 20+   on the build machine only
```

`imagick` is not required. The media pipeline runs on GD.

---

## 2. First deploy

```bash
git clone <your-repo> /var/www/viralpulse
cd /var/www/viralpulse

composer install --no-dev --optimize-autoloader
npm ci && npm run build          # or build locally and upload public/build

cp .env.example .env
php artisan key:generate
```

Edit `.env` — the values that matter most are in section 3 — then:

```bash
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link
```

`db:seed` creates the categories, the settings rows and the admin account.
Set `ADMIN_PASSWORD` **before** seeding, or the seeder generates one and prints
it exactly once.

### Permissions

```bash
chown -R www-data:www-data storage bootstrap/cache public/storage
chmod -R 775 storage bootstrap/cache
```

### Document root

Point the web server at **`public/`**, never at the project root. Serving the
project root exposes `.env`.

---

## 3. Production `.env`

```dotenv
APP_ENV=production
APP_DEBUG=false                  # a stack trace leaks paths, queries and env values
APP_URL=https://yourdomain.com   # drives canonical URLs, the sitemap and every absolute link

# Not "database". Every cache read is a query with that driver: it measured at
# ~29 extra queries on the home page. Redis if you have it, file if you do not.
CACHE_STORE=redis
QUEUE_CONNECTION=database
SESSION_DRIVER=database

MAIL_MAILER=smtp                 # "log" writes mail to a file and sends nothing

LOG_LEVEL=warning                # "debug" fills the disk and records more than you need
SESSION_SECURE_COOKIE=true       # once the site is on https, the cookie should never leave it

GEMINI_API_KEY=...               # or OPENAI_API_KEY
```

### Post pictures (optional)

Which sections may carry a photograph or an AI drawing is an editorial rule and
lives in `config/site.php` under `media.strategy`. News never gets either: a
stock photo of a courtroom next to a report about one reads as evidence of that
hearing, and an AI picture of an event depicts something that did not happen.

These keys only decide whether those strategies are *available*. Without them
every post falls back to the branded card, which is drawn locally and cannot
fail:

```dotenv
PEXELS_API_KEY=                  # free, from pexels.com/api - real licensed photos
GEMINI_IMAGE_MODEL=imagen-4.0-generate-001
```

### One setting that is not in `.env`

Set **Settings → Site → contact email** before launch. Ad networks and most
privacy laws expect a reachable address on the legal pages, and the contact
form falls back to the first admin account's email without it.

`APP_URL` is not cosmetic. The canonical middleware, the sitemap and every
`route()` call read it, so a wrong value produces a sitemap full of URLs
pointing at the wrong host.

Leave the automation switches off until the site has content you are happy
with:

```dotenv
AUTO_GENERATE_ENABLED=false
AUTO_PUBLISH=false
```

---

## 4. Cron and the queue worker

**One cron entry** drives every scheduled task:

```cron
* * * * * cd /var/www/viralpulse && php artisan schedule:run >> /dev/null 2>&1
```

**A queue worker must also be running.** Without it, AI generation jobs sit in
the queue forever and nothing is ever written. With systemd:

```ini
# /etc/systemd/system/viralpulse-worker.service
[Unit]
Description=ViralPulse queue worker
After=network.target

[Service]
User=www-data
Restart=always
RestartSec=5
# --max-time recycles the process hourly so a slow memory leak cannot accumulate.
ExecStart=/usr/bin/php /var/www/viralpulse/artisan queue:work --sleep=3 --tries=3 --max-time=3600

[Install]
WantedBy=multi-user.target
```

```bash
systemctl enable --now viralpulse-worker
```

Supervisor works equally well if that is what you already run.

### What the schedule does

Times are read in `APP_TIMEZONE` (Asia/Kolkata), not the server's clock, so the
server may sit in UTC as long as its clock is correct.

| When | Task |
|---|---|
| every minute | `posts:publish-scheduled` |
| hourly | `trending:fetch` |
| hourly at :20 | `content:generate-trending` |
| hourly at :50 | `content:reconcile-counters` |
| 00:15 | `stats:aggregate` |
| 03:00 | `data:cleanup` |
| 05:00 | `content:generate-daily-horoscope` |
| daily | queue table pruning |

`php artisan schedule:list` prints this table from the code itself, which is
the version to trust.

---

## 5. Every subsequent deploy

```bash
cd /var/www/viralpulse
php artisan down --render="errors::503"

git pull
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

php artisan queue:restart     # workers keep running old code until told otherwise
php artisan cache:warm        # so the first visitor does not rebuild everything

php artisan up
```

`queue:restart` is easy to forget and the failure is confusing: the site runs
new code while the workers keep running the old.

`config:cache` means `env()` returns null everywhere outside `config/`. All of
this project's env reads go through config files, so that is safe — but keep it
that way in anything you add.

---

## 6. After the first deploy

1. **Search Console.** Add the property, paste the verification token into
   Settings → SEO, then submit `https://yourdomain.com/sitemap.xml`.
2. **Check `robots.txt`.** Outside production it is a blanket `Disallow: /`.
   Confirm the live one is not, or nothing will ever be indexed.
3. **Publish some real articles.** Ten or twenty pieces you would put your name
   to, before applying to AdSense.
4. **Then apply.** Once approved, put the publisher id into Settings → AdSense;
   `ads.txt` builds itself from it.

---

## 7. Backups

```bash
# Database
mysqldump -u USER -p viral_plush | gzip > viral_plush-$(date +%F).sql.gz

# Uploads — these are not in git and cannot be regenerated
tar czf storage-$(date +%F).tar.gz storage/app/public
```

`.env` holds the app key and your API keys. Back it up somewhere private, and
never into the repository. **Losing `APP_KEY` makes every encrypted value and
every existing session unreadable**, and it is the salt behind the stored IP
hashes.

---

## 8. Troubleshooting

**AI generations stay on "Pending"** — the queue worker is not running.
`php artisan queue:work` and watch what happens.

**Scheduled posts never go live** — cron is not firing.
`php artisan schedule:list` shows what should run; run
`php artisan schedule:run` by hand to check it works at all.

**Nothing is being indexed** — fetch `/robots.txt`. If it says `Disallow: /`
then `APP_ENV` is not `production`, or Settings → SEO has the "block all
crawlers" switch on.

**Pages are slow** — check `CACHE_STORE`. With the `database` driver every
cache read costs a query.

**Styles are missing** — `public/build` was not deployed, or a killed
`npm run dev` left a stale `public/hot` file behind. Delete it.

**A 500 with no detail** — that is `APP_DEBUG=false` doing its job. The real
error is in `storage/logs/laravel.log`.

---

## 9. If you have no SSH access

Shared hosting without a shell can still run this, with one compromise: replace
the systemd worker with a cron entry that processes the queue in short bursts.

```cron
* * * * * cd /path/to/app && php artisan queue:work --stop-when-empty --max-time=55
* * * * * cd /path/to/app && php artisan schedule:run
```

It is less responsive than a persistent worker and it restarts PHP every
minute, but it works. Run the deploy commands from your host's task runner or a
one-off script instead.
