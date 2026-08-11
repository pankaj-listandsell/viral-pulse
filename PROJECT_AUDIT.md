# PROJECT AUDIT — ViralPulse (viral-plush)

**Audit date:** 2026-08-10
**Auditor:** Phase 1 — no code written, inspection only.
**Project root:** `d:\xampp\htdocs\viralpulse\viral-plush`

---

## 1. Verified Environment

| Item | Detected value | Notes |
|---|---|---|
| Laravel | **12.65.0** | Confirmed via `php artisan --version` |
| PHP | **8.2.12** (ZTS, VC++ 2019 x64) | XAMPP CLI. Meets `^8.2` requirement |
| Node | **v22.20.0** | |
| npm | **10.9.3** | |
| Database server | **MariaDB 10.4.32** | **NOT MySQL 8** — see §7 Conflicts |
| Database `viral_plush` | Exists, **0 tables** | Connection works; migrations have **not** been run |
| Git | Repo at `viral-plush/`, 1 commit (`5634dd1 Laravel 12 initial setup`), clean tree | Parent `viralpulse/` is *not* a repo |

**PHP extensions loaded:** bcmath, bz2, calendar, ctype, curl, date, dom, exif, fileinfo, filter, ftp, **gd**, gettext, hash, iconv, **intl**, json, libxml, mbstring, mysqli, mysqlnd, openssl, pcre, PDO, **pdo_mysql**, pdo_sqlite, Phar, random, readline, Reflection, session, SimpleXML, SPL, standard, tokenizer, xml, xmlreader, xmlwriter, OPcache, **zip**, zlib

**Missing / absent:** `imagick` ❌ → all image processing must target the **GD** driver.

---

## 2. Current Architecture

This is a **bare `laravel/laravel` 12 skeleton** with the "none" starter kit. There is no application code beyond framework defaults.

```
viral-plush/
├── app/
│   ├── Http/Controllers/Controller.php   (empty abstract base)
│   ├── Models/User.php                   (stock)
│   └── Providers/AppServiceProvider.php  (empty register/boot)
├── bootstrap/
│   ├── app.php        (web + console routing only; empty middleware & exception closures)
│   └── providers.php  (AppServiceProvider only)
├── config/            (app, auth, cache, database, filesystems, logging, mail, queue, services, session)
├── database/
│   ├── database.sqlite            ← leftover, unused (DB_CONNECTION=mysql)
│   ├── factories/UserFactory.php
│   ├── migrations/                ← 3 stock migrations only
│   └── seeders/DatabaseSeeder.php (creates "Test User")
├── resources/
│   ├── css/app.css     (Tailwind v4 `@import 'tailwindcss'` + @source globs, already includes `**/*.vue`)
│   ├── js/app.js       (only `import './bootstrap'`)
│   ├── js/bootstrap.js (axios setup)
│   └── views/welcome.blade.php
├── routes/
│   ├── web.php         (single closure returning `welcome` view)
│   └── console.php     (stock `inspire` command)
├── tests/              (ExampleTest ×2, TestCase)
├── public/             (index.php, favicon.ico, robots.txt — **no `storage` symlink**)
└── storage/app/{private,public}  (both empty)
```

### Existing migrations (3, all framework defaults)
| File | Tables created |
|---|---|
| `0001_01_01_000000_create_users_table.php` | `users`, `password_reset_tokens`, `sessions` |
| `0001_01_01_000001_create_cache_table.php` | `cache`, `cache_locks` |
| `0001_01_01_000002_create_jobs_table.php` | `jobs`, `job_batches`, `failed_jobs` |

### Existing routes
- `GET /` → `welcome` view (closure)
- `GET /up` → health check (from `withRouting(health:)`)
- **No** `routes/api.php`, **no** `routes/auth.php`, **no** `routes/admin.php`

### Existing models / controllers / services
- Models: `User` only (fillable: name, email, password; casts: email_verified_at, password)
- Controllers: abstract `Controller` base only
- Services / Jobs / Events / Policies / Form Requests / Console Commands: **none**

---

## 3. Existing Dependencies

### composer.json — require
- `php ^8.2`
- `laravel/framework ^12.0`
- `laravel/tinker ^2.10.1`

### composer.json — require-dev
- `fakerphp/faker ^1.23`, `laravel/pail ^1.2.2`, `laravel/pint ^1.13`, `laravel/sail ^1.41`, `mockery/mockery ^1.6`, `nunomaduro/collision ^8.6`, `phpunit/phpunit ^11.5.3`

### package.json — devDependencies (all installed in `node_modules`)
- `@tailwindcss/vite ^4.0.0`, `tailwindcss ^4.0.0` → **Tailwind CSS v4**
- `laravel-vite-plugin ^1.2.0`, `vite ^6.0.11`
- `axios ^1.7.4`, `concurrently ^9.0.1`

### Notable absences
- ❌ No authentication scaffolding (no Breeze / Jetstream / Fortify / starter kit)
- ❌ No Inertia.js
- ❌ No Vue 3
- ❌ No Ziggy
- ❌ No rich-text editor, no chart library, no icon set
- ❌ No image-manipulation library
- ❌ No HTML sanitizer

---

## 4. Configuration Review (`.env`)

| Key | Value | Assessment |
|---|---|---|
| `APP_NAME` | `"Viral Plush"` | Reused as-is; final site name will come from the DB `settings` table |
| `APP_KEY` | set | ✅ |
| `APP_ENV` / `APP_DEBUG` | `local` / `true` | ✅ for dev |
| `DB_CONNECTION` | `mysql` | ⚠️ Should be `mariadb` — see §7 |
| `DB_DATABASE` | `viral_plush` | ✅ exists, empty |
| `DB_PASSWORD` | *(empty)* | XAMPP default — acceptable locally, must change in production |
| `SESSION_DRIVER` | `database` | ✅ (migration present) |
| `QUEUE_CONNECTION` | `database` | ✅ (migration present) — correct for XAMPP, no Redis needed |
| `CACHE_STORE` | `database` | ✅ works; Redis is an optional prod upgrade |
| `FILESYSTEM_DISK` | `local` | ⚠️ Media library needs `public` |
| `MAIL_MAILER` | `log` | ✅ for dev; real SMTP needed for newsletter/contact |
| `AI_*`, `ADSENSE_*`, `ANALYTICS_*`, `SITE_*` | **absent** | Must be added |
| `APP_TIMEZONE` | not set (defaults UTC) | Decide site timezone before scheduling logic is built |

`config/database.php` uses `utf8mb4` / `utf8mb4_unicode_ci` — **MariaDB-safe** (Laravel does not force `utf8mb4_0900_ai_ci` here). A dedicated `mariadb` connection block is already present and unused.

---

## 5. Files to REUSE (do not replace)

| File | Reason |
|---|---|
| `composer.json` / `composer.lock` | Add deps only; never regenerate |
| `package.json` | Add deps only |
| `config/*.php` | All stock and correct. Only `filesystems.php` may need an extra disk; new files `config/ai.php`, `config/site.php` will be **added**, not edited |
| `database/migrations/0001_01_01_*` | Framework tables — keep untouched |
| `resources/css/app.css` | Tailwind v4 entry already globs `*.vue` — reuse, extend theme only |
| `.editorconfig`, `.gitattributes`, `.gitignore` | Keep |
| `app/Models/User.php` | **Extend** (add role relation, scopes) — do not rewrite |
| `phpunit.xml` | Edit env block only |

## 6. Files to MODIFY

| File | Change | Phase |
|---|---|---|
| `.env` / `.env.example` | Add AI, AdSense, Analytics, site, auto-publish keys; switch `DB_CONNECTION`, `FILESYSTEM_DISK` | 2 |
| `bootstrap/app.php` | Register `routes/admin.php` + `routes/api.php`, alias `admin` middleware, trusted proxies, exception rendering for Inertia | 3 |
| `bootstrap/providers.php` | Register new service providers | 3 |
| `app/Providers/AppServiceProvider.php` | Model::shouldBeStrict in non-prod, URL::forceScheme in prod, Inertia shared data, rate limiters | 3 |
| `vite.config.js` | Add `@vitejs/plugin-vue`, alias `@` → `resources/js` | 3 |
| `resources/js/app.js` | Inertia + Vue app bootstrap | 3 |
| `routes/web.php` | Replace the placeholder closure with real public routes | 5 |
| `app/Models/User.php` | Add `role_id`, relations, `isAdmin()` | 3 |
| `database/seeders/DatabaseSeeder.php` | Call new seeders | 2 |
| `phpunit.xml` | Point at a **separate test database** | 2 |
| `public/robots.txt` | Delete/neutralise — replaced by a dynamic route | 8 |

## 7. Potential Conflicts & Risks ⚠️

These are the things most likely to break the build if ignored.

1. **MariaDB 10.4, not MySQL 8.**
   - `json` columns are LONGTEXT aliases — Eloquent `array`/`json` casts work, but **JSON-path indexing and MySQL 8 JSON functions must not be relied on**. Anything queried/filtered gets a real column or a pivot table.
   - No functional indexes, no `CHECK` constraint enforcement in older modes, `DESC` in index definitions is ignored.
   - InnoDB `FULLTEXT` **is** supported (MariaDB 10.0+) → search plan is viable, but there is no ngram parser and `ft_min_token_size` defaults to **4** on MariaDB, so short queries need a `LIKE` fallback.
   - **Action:** set `DB_CONNECTION=mariadb` so Laravel uses its MariaDB schema grammar.

2. **`phpunit.xml` has the sqlite lines commented out.** With `RefreshDatabase`, `php artisan test` would **migrate:fresh the live `viral_plush` database and destroy all content**. This is the single highest-risk item in the repo. Must be fixed in Phase 2 *before* any test is written. sqlite `:memory:` is *not* a valid fix here because `MATCH … AGAINST` does not exist in sqlite — a dedicated `viral_plush_test` MariaDB database is required.

3. **No `imagick`.** Image resizing/WebP conversion must use the **GD** driver. GD handles JPEG/PNG/WebP/GIF here; it cannot do AVIF or SVG rasterisation. Media library will be specified against GD only.

4. **`public/storage` symlink missing.** `php artisan storage:link` is mandatory before the media library works. On Windows this needs a shell with symlink privileges (Developer Mode on, or an elevated terminal) — otherwise Laravel falls back to copying the directory, which silently breaks new uploads.

5. **Adding a starter kit rewrites stock files.** Any Breeze/Inertia install will overwrite `resources/js/app.js`, `vite.config.js`, `resources/css/app.css`, `routes/web.php` and `tests/`. All of those are currently **stock with zero custom code**, so the overwrite is safe *today* — but it must happen in Phase 3 before any custom frontend exists, never after.

6. **`routes/api.php` does not exist and Laravel 12 does not load it by default.** Running `php artisan install:api` pulls in **Sanctum** and a `personal_access_tokens` migration. Since the admin panel is session-based Inertia, Sanctum is unnecessary weight — register `api.php` manually in `bootstrap/app.php` instead.

7. **`database/database.sqlite` is a leftover** from `post-create-project-cmd`. Harmless but should be removed to prevent confusion about which DB is authoritative.

8. **Windows/XAMPP has no cron and no supervisor.** `schedule:run` and `queue:work` need Task Scheduler or NSSM locally; production Linux deployment docs must cover both separately.

9. **Tailwind v4, not v3.** Configuration is CSS-first (`@theme` in `app.css`); there is no `tailwind.config.js` and one should not be created. Any copy-pasted v3 config or plugin (`@tailwindcss/typography` etc.) must be installed the v4 way (`@plugin` directive).

10. **AdSense policy risk (non-technical, but material).** Google's spam policies target *scaled content abuse* — mass-published, low-value auto-generated content. A site that auto-publishes unreviewed AI articles is at real risk of AdSense rejection or Search penalties. The architecture must therefore default to `AUTO_PUBLISH=false` (AI generates a **draft**, a human reviews and publishes) and must support genuine editorial value-add. This is a design constraint, not a nice-to-have.

11. **Author attribution.** Posts link to a real `users.id` author. No invented bylines will be generated; AI-generated posts are flagged `ai_generated = true` and the UI will disclose it.

---

## 8. New Dependencies Required

### Composer (production)
| Package | Purpose | Justification |
|---|---|---|
| `inertiajs/inertia-laravel` | Inertia server adapter | Required by the stack spec |
| `tightenco/ziggy` | `route()` helper inside Vue | Avoids hardcoding URLs in 100+ places |
| `intervention/image` (v3, GD driver) | Resize / WebP / thumbnails | No imagick; needed for media library + performance |
| `mews/purifier` | HTML sanitisation | Mandatory: AI-generated HTML must never be trusted (spec §33) |

### Composer (dev)
| Package | Purpose |
|---|---|
| `laravel/breeze` *(candidate)* | Inertia+Vue auth scaffold — **decision point, see plan §D1** |
| `barryvdh/laravel-debugbar` *(optional)* | N+1 / query profiling during Phase 10 |

### npm
| Package | Purpose |
|---|---|
| `vue`, `@vitejs/plugin-vue`, `@inertiajs/vue3` | Core frontend stack |
| `ziggy-js` | Client half of Ziggy |
| `@tiptap/vue-3`, `@tiptap/starter-kit`, `@tiptap/extension-link`, `@tiptap/extension-image` | Rich text editor for the post editor |
| `chart.js`, `vue-chartjs` | Dashboard charts (~60 KB, tree-shakeable) |
| `lucide-vue-next` | Icon set (tree-shakeable SVG components) |
| `@tailwindcss/typography` | `prose` classes for article body rendering |
| `@vueuse/core` *(optional)* | Debounce/clipboard/dark-mode composables |

### Deliberately NOT installed
| Rejected | Why |
|---|---|
| `spatie/laravel-permission` | Overkill for 3 roles; a `roles` table + `role_id` + Gates/Policies is simpler and matches the spec's own table list |
| `spatie/laravel-sitemap` | It is a *crawler*; we generate sitemaps from the DB, which is faster and exact |
| `laravel/sanctum` | Session-based Inertia admin needs no token auth |
| `laravel/scout` + Meilisearch | Deferred. MariaDB FULLTEXT is sufficient at this scale; `SearchService` is designed so Scout is a one-class swap later |
| `spatie/laravel-sluggable` | ~15 lines in `PostService` replaces it |
| `league/csv` | Newsletter export is a streamed response, ~20 lines |

---

## 9. Recommended Implementation Order

Rationale for ordering differences from the brief:

- A **Phase 2a "baseline hardening"** step is inserted before any schema work: fix the destructive `phpunit.xml`, switch to the `mariadb` driver, create the test DB, run `storage:link`. These are 15 minutes of work that prevent hours of damage.
- **Auth scaffolding (Phase 3) must precede all frontend work** because installing it overwrites stock frontend files.
- **SEO (Phase 8)** is pulled partly into Phase 5, because meta tags/canonical/JSON-LD belong in the page layouts as they are written, not bolted on afterwards.

| Phase | Scope | Gate to pass |
|---|---|---|
| 1 | Audit *(this document)* | ✅ complete |
| 2 | Baseline hardening → migrations → models → factories → seeders | `migrate:fresh --seed` green, `test` isolated |
| 3 | Inertia+Vue+Ziggy install, roles, admin middleware, admin shell/layout, dashboard skeleton | Login → admin dashboard renders |
| 4 | Posts, categories, tags, media CRUD + Form Requests + Policies + services | Full CRUD passes feature tests |
| 5 | Public frontend + layouts + per-page SEO head | Home/post/category/search render, Lighthouse ≥ 90 |
| 6 | AI provider abstraction, generator UI, `ai_generations` logging | Generate → sanitise → draft, with a fake provider in tests |
| 7 | Trending sources, jobs, scheduler, auto-publish pipeline | `schedule:work` + `queue:work` produce a reviewed draft |
| 8 | sitemap.xml, robots.txt, feed.xml, schema.org, ads.txt | Validators clean |
| 9 | Newsletter, comments + moderation, contact, activity logs, settings UI | Rate-limited, moderated, tested |
| 10 | Test suite completion, indexes/N+1, caching, security pass, deployment docs | `php artisan test` fully green |

---

## 10. Audit Conclusion

The project is a **clean, unmodified Laravel 12.65 skeleton on a working MariaDB connection with zero migrations run**. There is no legacy code to preserve and no risk of destroying existing work — which means the build can proceed greenfield.

Three items must be resolved **before Phase 2 code is written**:

1. `phpunit.xml` currently points tests at the live database (**data-loss risk**).
2. `DB_CONNECTION` should become `mariadb`, and the schema must be designed for MariaDB 10.4, not MySQL 8.
3. The auth-scaffolding approach must be chosen (plan §D1) since it overwrites frontend entry files.

Full phase breakdown, schema, and file manifest: see **`PROJECT_PLAN.md`**.
