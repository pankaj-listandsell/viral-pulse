# ViralPulse

An AI-assisted publishing platform. It pulls trending topics from public feeds,
writes articles about the ones worth writing about, drips them out on a
schedule, and serves them as server-rendered HTML built to be indexed and
monetised.

Laravel 12 · PHP 8.2 · MySQL/MariaDB · Blade + Vue islands · Tailwind v4

---

## What it does

**Trending → article → published, on a schedule.**

1. `trending:fetch` reads Google Trends, Google News and any RSS feeds you
   configure, deduplicates them and scores each topic 0–100.
2. `content:generate-trending` takes the highest scorers and asks Gemini or
   OpenAI to write them, then runs the result through a quality gate.
3. Anything that passes is scheduled inside your publishing window rather than
   posted all at once.
4. `posts:publish-scheduled` puts each one live at its appointed minute and
   rebuilds the sitemap and feeds.

Every step is off by default. Turning automation on is an explicit decision
because each run spends money.

---

## Requirements

| | |
|---|---|
| PHP | 8.2+ with `gd`, `mbstring`, `pdo_mysql`, `fileinfo`, `openssl` |
| Database | MySQL 8 or MariaDB 10.4+ (InnoDB, for `FULLTEXT` search) |
| Node | 20+ (build only — not needed at runtime) |
| Composer | 2.x |

An AI provider key is optional. Without one the site works fine; the generator
simply says no provider is configured.

---

## Getting started

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# Create the database, then:
php artisan migrate --seed

npm run build
php artisan serve
```

`--seed` creates the categories, the settings rows and one admin account. Set
`ADMIN_PASSWORD` in `.env` before seeding, or leave it empty and the seeder
generates a strong password and prints it once.

Sign in at **`/admin`**. The public site does not link to it, and there is no
`/login` or `/register` at the root.

### Development

```bash
npm run dev              # Vite with hot reload
php artisan queue:work   # required for AI generation
php artisan schedule:work # required for the automation pipeline
```

---

## Architecture

**Blade renders, Vue enhances.** Every public page is server-rendered HTML, so
a crawler receives the whole article without running any JavaScript. Vue is
mounted only onto the handful of components that need to be interactive — the
public JS entry point is about 1 KB and the Vue runtime loads lazily, only on
pages that actually use an island.

```
app/
  Console/Commands/     7 commands: the whole automation pipeline
  Http/
    Controllers/Admin/  the admin panel
    Controllers/Public/ the site, plus sitemap/robots/feed/ads.txt
    Middleware/         admin gate, canonical URLs, security headers
  Jobs/                 queued AI generation
  Models/               18 models
  Services/
    AI/                 provider abstraction, prompts, quality gate
    Trending/           feed parsing, scoring, category routing
    *.php               posts, media, SEO, sitemaps, feeds, settings
  Support/              settings schema, IP fingerprinting
resources/
  js/Islands/           8 Vue components
  views/admin/          Blade admin panel
  views/public/         Blade public site
```

### Decisions worth knowing about

**One admin, no roles.** Authorization is a single question — is this an active
admin? — expressed as one gate. Per-model policies would add ceremony without
saying anything more.

**API keys live in `.env`, never in the database.** A key in the settings table
would appear in every database dump, every backup, and on the settings screen
itself. Only the *choice* of provider is stored.

**Raw IP addresses are never stored.** Views, likes, contact messages and
activity logs all keep a salted HMAC instead, so submissions can be matched
against each other but no address can be read back.

**AI output is untrusted input.** Every generated article goes through
HTMLPurifier with a strict allowlist before it is stored, no matter how well
the prompt behaved.

**Auto-publish requires two separate yeses.** The setting must be on *and* the
article must clear the quality gate. A setting alone never puts unreviewed text
on the site.

---

## Commands

| Command | What it does |
|---|---|
| `trending:fetch` | Pull, deduplicate and score topics from every enabled source |
| `content:generate-trending` | Write the top topics (needs `AUTO_GENERATE_ENABLED`) |
| `posts:publish-scheduled` | Publish anything due, every minute |
| `content:reconcile-counters` | Recalculate denormalised counts from source |
| `stats:aggregate` | Roll yesterday's views into `post_daily_stats` |
| `data:cleanup` | Prune raw analytics and old activity logs |
| `cache:warm` | Rebuild feed, sitemap and RSS caches after a deploy |

All of them are wired into `routes/console.php`; see
[DEPLOYMENT.md](DEPLOYMENT.md) for the single cron entry that drives them.

---

## Testing

```bash
php artisan test           # or ./vendor/bin/phpunit
./vendor/bin/pint          # formatting
npm run build              # asset build
```

Tests run against a **separate database** (`viral_plush_test`, configured in
`phpunit.xml`) because `RefreshDatabase` drops every table. There is a test
that asserts this, so the suite fails loudly rather than destroying your data
if that config is ever changed.

No test reaches a paid API: the AI suite binds a fake provider and calls
`Http::preventStrayRequests()`.

---

## A word about AdSense

This build is technically compliant — real ad slots, proper privacy, terms and
disclaimer pages, and a visible disclosure on AI-written articles. Approval
still depends on the content having genuine value to a reader.

A site of unreviewed auto-generated articles is precisely what Google's
scaled-content-abuse policy exists to catch. `AUTO_PUBLISH=false` plus real
editorial review is the path that actually works. The defaults reflect that.
