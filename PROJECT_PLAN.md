# PROJECT PLAN — ViralPulse

**Companion to:** `PROJECT_AUDIT.md` (read that first)
**Status:** Plan only. **No implementation code has been written.**
**Created:** 2026-08-10

---

## A. Decisions — SETTLED

All four are decided and implemented. Where a decision changed during the build, the reason is recorded.

### D1 — Frontend architecture ✅ **Blade pages + Vue islands** *(revised during Phase 3)*

The original plan said Breeze + Inertia + Vue. Two findings changed it:

1. **Breeze v2.4.2 still installs Tailwind v3.2.1** and its own `tailwind.config.js` + `vite.config.js`. Using it would have downgraded the project's working Tailwind v4 setup and pulled in Sanctum, which a same-origin session app does not need. Verified by inspecting the installed package, not assumed.
2. **Inertia renders client-side.** For a site whose entire value is SEO, shipping article text that only exists after JavaScript runs is a real risk — and the fix (Inertia SSR) needs a Node process alive 24/7 in production, which is a poor fit for this hosting.

**Shipped architecture:**
- **Admin panel:** Laravel Blade + Alpine.js. Server-rendered, no SPA.
- **Public site:** Laravel Blade, server-rendered HTML. Crawlers get the complete article with zero JavaScript.
- **Vue 3:** mounted as *islands* onto the interactive parts only (search, newsletter, likes, comments, filters). The Vue runtime is lazy-loaded — a page with no island ships **~1 KB** of JS.
- Inertia was installed, then removed. Ziggy is kept for `route()` inside islands.

### D2 — Roles ✅ **No role system. One administrator.** *(revised during Phase 3)*

The original schema had a `roles` table with admin/editor/author/user and six policy classes. That was cut on request: the site has **one admin**, everyone else is a registered reader.

- `roles` table and `role_id` removed; replaced with `users.is_admin` (boolean, **not mass assignable**).
- All six policy classes deleted. Authorization is one question — *is this an active admin?* — answered by the `admin` middleware and a single `access-admin` gate.
- Result: materially less code, and no ambiguity about who can do what.

### D3 — AI provider ✅ **Anthropic** (`claude-sonnet-5`) as the default

The architecture is provider-agnostic: `AiProviderInterface` with swappable drivers selected by `AI_PROVIDER`. OpenAI and Gemini drivers ship as alternatives. Switching later is a one-line `.env` change.

### D4 — Locale ✅ `APP_TIMEZONE=Asia/Kolkata`, default content language `en`, `hi` supported

### D5 — Publishing policy ✅ `AUTO_PUBLISH=false` by default

AI produces a **draft**; a human approves it. The `AUTO_PUBLISH=true` path is built and works, gated behind `ContentValidator`.

This matters commercially, not just editorially: Google's spam policies target *scaled content abuse* — mass auto-generated pages made primarily for ad revenue. Since the goal here is AdSense income, an unreviewed firehose is the single fastest way to lose both approval and rankings. The mitigation built into the design is to make review **fast** rather than optional: the AI produces a complete, SEO-ready draft, and approving it is one click. Volume comes from generation throughput, not from skipping the human.

---

## B. Target Architecture

```
Request
  ├── Public  → routes/web.php   → Http/Controllers/Public/* → Services → Models
  │                              → Blade (server-rendered HTML)
  │                              → Vue islands hydrate interactive parts only
  ├── Admin   → routes/admin.php → Http/Controllers/Admin/*  → Services → Models
  │                              → Blade + Alpine.js
  │              guarded by: auth → EnsureUserIsAdmin
  ├── API     → routes/api.php   → Http/Controllers/Api/*    → throttled, JSON only
  └── Console → routes/console.php → Console/Commands/* → dispatch Jobs → Services

Services layer (all business logic; controllers stay thin)
Jobs layer     (anything slow, external, or failure-prone)
Cache layer    (public read paths only; admin reads are always live)
```

**Asset entry points**
| Entry | Loads | Used by |
|---|---|---|
| `resources/js/app.js` | Island mounter (~1 KB). Vue + the island component are fetched only if the page contains one. | Public site |
| `resources/js/admin.js` | Alpine.js, Chart.js, axios | Admin panel + auth pages |
| `resources/css/app.css` | Tailwind v4 (CSS-first config, no `tailwind.config.js`) | Both |

**Rules enforced throughout**
- Controllers: request in → service call → response out. No queries, no business logic.
- Every write endpoint has a Form Request. Every resource has a Policy.
- Every external call (AI, RSS, news API) happens inside a Job with `$tries`, `$timeout`, `$backoff`, and `failed()`.
- Every model that users can soft-delete uses `SoftDeletes`; high-volume log/analytics tables do not.
- No raw AI HTML reaches the database — it passes `HtmlSanitizer` first.

---

## C. Database Schema (MariaDB 10.4)

24 migrations. Engine InnoDB, `utf8mb4_unicode_ci` throughout. `⚑` marks a table added beyond the brief's list, with justification.

### Core

**`users`** *(extended by `add_profile_fields_to_users_table`)*
`is_admin` (bool, default false, **not mass assignable**), `username` (unique, nullable), `avatar` (nullable), `bio` (text nullable), `is_active` (bool, default true), `last_login_at` (nullable), `softDeletes`
Indexes: `is_admin`, `is_active`

There is no `roles` table — see decision D2.

**`categories`** — `id`, `parent_id` (self FK, nullOnDelete, nullable), `name`, `slug` (unique), `description` (text nullable), `image` (nullable), `icon` (nullable), `color` (char 7 nullable), `sort_order` (uint, default 0), `is_active` (bool, default true), `is_featured` (bool, default false), `posts_count` (uint, default 0), `seo_title`, `seo_description`, timestamps, softDeletes
Indexes: `(is_active, sort_order)`, `parent_id`, unique `slug`

**`tags`** — `id`, `name`, `slug` (unique), `description` (nullable), `posts_count` (uint default 0), `is_trending` (bool default false), timestamps
Index: `(is_trending, posts_count)`

**`posts`** — the centrepiece
| Column | Type | Notes |
|---|---|---|
| `id` | bigIncrements | |
| `author_id` | FK→users | restrictOnDelete — never orphan a byline |
| `category_id` | FK→categories | restrictOnDelete |
| `title` | string 255 | |
| `slug` | string 255 | **unique** |
| `excerpt` | string 500 nullable | |
| `content` | longText | sanitised HTML |
| `featured_image` | string nullable | storage path |
| `featured_image_alt` | string nullable | accessibility + SEO |
| `status` | enum(draft, scheduled, published, archived) | default `draft` |
| `published_at` | timestamp nullable | |
| `scheduled_at` | timestamp nullable | |
| `source_type` | enum(manual, ai, trending, imported) | default `manual` |
| `ai_generated` | boolean | default false, disclosed in UI |
| `language` | char(5) | default `en` |
| `reading_time` | uint smallint | computed on save, avoids per-request work |
| `is_featured` / `is_trending` | boolean | default false |
| `views_count` / `likes_count` / `comments_count` | uint | denormalised counters |
| `seo_title` | string 255 nullable | |
| `seo_description` | string 500 nullable | |
| `seo_keywords` | string 500 nullable | |
| `canonical_url` | string nullable | |
| `og_image` | string nullable | |
| timestamps + softDeletes | | |

Indexes:
- `(status, published_at)` ← the single most-used query path
- `(category_id, status, published_at)`
- `(status, scheduled_at)` ← the scheduler's lookup
- `(is_featured, status)`, `(is_trending, status)`, `author_id`, `ai_generated`
- `FULLTEXT (title, excerpt, content)` — added in a **separate migration** with a `driver === mysql|mariadb` guard, so the schema stays portable

**`post_tag`** — `post_id` FK cascade, `tag_id` FK cascade, primary `(post_id, tag_id)`, index `tag_id`

**`media`** — `id`, `user_id` FK nullOnDelete, `disk` (default `public`), `path` (unique), `filename`, `original_name`, `mime_type`, `extension`, `size` (uint bigint), `width`/`height` (uint nullable), `alt_text`, `caption`, `folder` (nullable), `conversions` (json — thumbnail/webp variants; never queried, so LONGTEXT-as-JSON is fine on MariaDB), timestamps
Indexes: `user_id`, `mime_type`, `folder`

### AI & trending

**`ai_generations`** — full audit trail of every call
`id`, `user_id` FK nullOnDelete nullable, `post_id` FK nullOnDelete nullable, `trending_topic_id` FK nullOnDelete nullable, `provider`, `model`, `content_type`, `topic`, `language`, `tone`, `target_audience`, `target_length` (uint nullable), `prompt` (longText), `raw_response` (longText nullable), `parsed_output` (json nullable), `status` enum(pending, processing, completed, failed, rejected), `error_message` (text nullable), `prompt_tokens`/`completion_tokens` (uint nullable), `cost` (decimal 10,6 nullable), `duration_ms` (uint nullable), `quality_score` (uint tinyint nullable), timestamps
Indexes: `(status, created_at)`, `user_id`, `post_id`

**`trending_topics`** — `id`, `topic`, `topic_hash` (char 40, **unique** — sha1 of normalised topic, the dedupe key), `slug`, `description` (text nullable), `source` (enum: manual, rss, google_trends, news_api, social), `source_url` (nullable), `category_id` FK nullOnDelete nullable, `trend_score` (uint, default 0), `region` (char 5 nullable), `language` (char 5 default en), `raw_payload` (json nullable), `detected_at`, `status` enum(new, queued, generating, generated, scheduled, ignored, failed) default `new`, `post_id` FK nullOnDelete nullable, timestamps
Indexes: `(status, trend_score)`, `(source, detected_at)`, unique `topic_hash`

**`scheduled_posts`** — `id`, `post_id` FK cascade, `scheduled_at`, `status` enum(pending, processing, published, failed, cancelled), `attempts` (uint tinyint default 0), `last_error` (text nullable), `published_at` (nullable), timestamps
Index: `(status, scheduled_at)`

### Engagement & analytics

**`post_views`** — `id`, `post_id` FK cascade, `user_id` FK nullOnDelete nullable, `ip_hash` (char 64 — **hashed, never raw IP**, GDPR-conscious), `user_agent_hash` (char 64), `referrer` (nullable), `country` (char 2 nullable), `device` (enum: desktop, mobile, tablet, bot nullable), `viewed_at`
Indexes: `(post_id, viewed_at)`, `(ip_hash, post_id, viewed_at)` (dedupe window), `viewed_at` (pruning)
No timestamps, no soft deletes — this is the highest-volume table.

**⚑ `post_daily_stats`** — `id`, `post_id` FK cascade, `date`, `views` (uint), `unique_views` (uint), `likes` (uint), unique `(post_id, date)`
*Justification for adding this:* the dashboard's "views per day" chart cannot scan a raw `post_views` table once it reaches millions of rows. A nightly rollup job aggregates into this table, `post_views` is pruned after N days (`ANALYTICS_RETENTION_DAYS`), and the chart reads only from here. Without it the dashboard becomes the slowest page on the site within months.

**`post_likes`** — `id`, `post_id` FK cascade, `user_id` FK cascade nullable, `ip_hash` (char 64 nullable), `created_at`
Unique `(post_id, user_id)`, unique `(post_id, ip_hash)` — prevents double-liking for both guests and members.

**`comments`** — `id`, `post_id` FK cascade, `user_id` FK nullOnDelete nullable, `parent_id` self FK cascade nullable (threaded replies), `author_name`, `author_email`, `author_website` (nullable), `ip_hash`, `user_agent` (nullable), `content` (text — plain text only, escaped on render, never HTML), `status` enum(pending, approved, rejected, spam) default `pending`, `approved_at` (nullable), `approved_by` FK→users nullOnDelete nullable, timestamps, softDeletes
Indexes: `(post_id, status, created_at)`, `(status, created_at)`, `parent_id`

### Site management

**`settings`** — `id`, `group` (e.g. general, seo, adsense, social, mail, ai), `key` (**unique**), `value` (longText nullable), `type` enum(string, text, boolean, integer, json, file), `is_public` (bool — controls whether it may be shared to the frontend), `description` (nullable), timestamps
Index: `group`. Whole table is cached forever and flushed on write.

**`seo_meta`** — polymorphic, for entities that do *not* have inline SEO columns (categories, tags, static pages)
`id`, `seoable_type`, `seoable_id`, `title`, `description`, `keywords`, `canonical_url`, `og_title`, `og_description`, `og_image`, `twitter_card` (default `summary_large_image`), `robots` (default `index,follow`), `schema_markup` (json nullable), timestamps
Unique `(seoable_type, seoable_id)`

**`contact_messages`** — `id`, `name`, `email`, `subject`, `message` (text), `ip_hash`, `status` enum(new, read, replied, spam) default `new`, `read_at` nullable, timestamps, softDeletes
Index: `(status, created_at)`

**`newsletter_subscribers`** — `id`, `email` (**unique**), `name` (nullable), `token` (char 64 unique — powers one-click unsubscribe), `status` enum(pending, subscribed, unsubscribed, bounced) default `pending`, `confirmed_at`/`unsubscribed_at` nullable, `ip_hash`, `source` (nullable), timestamps
Index: `(status, created_at)`

**`activity_logs`** — `id`, `user_id` FK nullOnDelete nullable, `action`, `subject_type`/`subject_id` (nullable morph), `description` (nullable), `properties` (json nullable), `ip_hash`, `user_agent` (nullable), `created_at`
Indexes: `(subject_type, subject_id)`, `(user_id, created_at)`, `created_at`

### Deliberate schema notes
- No column duplicates another. `posts.seo_*` exists **because the brief requires it inline**; `seo_meta` covers everything else — they never overlap on the same row.
- Counter columns (`views_count`, `posts_count`, …) are denormalised on purpose and reconciled by a nightly command, so a drift bug can never silently persist.
- All `json` columns are write-and-read-whole, never filtered on — deliberate, because MariaDB 10.4 cannot index JSON paths.
- No raw IPs stored anywhere. Only salted hashes.

---

## D. Phase Breakdown

Each phase ends with: files listed, changes explained, commands run, tests green. **No phase starts until the previous one is error-free.**

---

### PHASE 1 — Audit ✅ DONE
Deliverable: `PROJECT_AUDIT.md`, `PROJECT_PLAN.md`.

---

### PHASE 2 — Baseline hardening + database layer

**2a. Hardening first (before any schema work)**
1. `phpunit.xml` → add `DB_CONNECTION=mariadb`, `DB_DATABASE=viral_plush_test` — **stops tests destroying the live DB**
2. Create the `viral_plush_test` database
3. `.env` + `.env.example`: `DB_CONNECTION=mariadb`, `FILESYSTEM_DISK=public`, `APP_TIMEZONE`, and all new key groups (§F)
4. `php artisan storage:link` (with a Windows symlink-permission check)
5. Delete `database/database.sqlite`
6. `git commit` a clean checkpoint

**2b. Migrations** — 24 files, ordered by dependency:
`roles` → `add_profile_fields_to_users` → `categories` → `tags` → `media` → `posts` → `post_tag` → `add_fulltext_to_posts` → `trending_topics` → `ai_generations` → `scheduled_posts` → `post_views` → `post_daily_stats` → `post_likes` → `comments` → `settings` → `seo_meta` → `contact_messages` → `newsletter_subscribers` → `activity_logs`

**2c. Models (18)** — `Role, User, Category, Tag, Post, Media, AiGeneration, TrendingTopic, ScheduledPost, PostView, PostDailyStat, PostLike, Comment, Setting, SeoMeta, ContactMessage, NewsletterSubscriber, ActivityLog`
With: relationships, casts, `$fillable` (never `$guarded = []`), and scopes such as `Post::published()`, `scheduled()`, `trending()`, `featured()`, `byCategory()`, `search()`.

**2d. Factories + seeders** — `RoleSeeder`, `AdminUserSeeder` (credentials from `.env`, never hardcoded), `CategorySeeder`, `TagSeeder`, `SettingSeeder`, `PostSeeder` (dev-only, guarded by `app()->environment('local')`).

**Exit gate:** `php artisan migrate:fresh --seed` succeeds; every FK/index verified with `SHOW CREATE TABLE`; a model-relationship smoke test passes.

---

### PHASE 3 — Auth + admin foundation ✅ DONE

- Vue + Alpine + Ziggy installed, Tailwind v4 preserved; Inertia installed then removed (D1)
- `EnsureUserIsAdmin` middleware aliased in `bootstrap/app.php`; `routes/admin.php` and `routes/api.php` registered
- Single `access-admin` gate, `Gate::before` short-circuit for the admin (D2)
- Auth: login, logout, register, forgot/reset password — all Blade, throttled, with an account-enumeration-safe reset response
- Profile: details, password change, account deletion (readers only)
- **Admin shell:** collapsible sidebar (16 items, hidden until their route exists), topbar with theme toggle and user menu, toast queue, mobile drawer, dark mode with no flash-of-light
- Blade components: `icon` (43 lucide paths generated from the installed package), `input`, `label`, `error`, `button`, `card`, `badge`, `checkbox`, `stat-card`, `empty-state`, `chart`, `toasts`
- Dashboard: 8 stat cards on real counts, two Chart.js charts, top categories, top posts, recent-posts table, moderation banner
- `ActivityLogger` service writing to `activity_logs`; `Fingerprint` helper so no raw IP is ever stored
- Error pages 403/404/419/429/500/503

**Exit gate:** ✅ 47 tests green, Pint clean, `npm run build` clean.

---

### PHASE 4 — Posts, categories, tags, media

- Controllers: `Admin\PostController` (+ `duplicate`, `publish`, `unpublish`, `archive`, `schedule`, `bulkAction`), `Admin\CategoryController`, `Admin\TagController`, `Admin\MediaController`
- Form Requests: `StorePostRequest`, `UpdatePostRequest`, `StoreCategoryRequest`, `UpdateCategoryRequest`, `StoreTagRequest`, `UpdateTagRequest`, `UploadMediaRequest`
- Services: `PostService` (slug uniqueness, reading time, status transitions, tag sync, counter upkeep), `MediaService` (validate → store → GD resize → WebP → thumbnails → `media` row), `SlugService`, `HtmlSanitizer`
- Blade admin views: `posts/{index,create,edit}`, `categories/*`, `tags/*`, `media/index`
- Editor pieces: Tiptap mounted as an island inside the Blade form, `SlugInput` (auto from title, manual override, live availability check), tag selector, media picker modal, SEO panel with Google/social preview, publish box (status + schedule datetime)
- Upload validation: MIME **sniffed from content**, not the extension; size cap; extension allowlist; randomised filenames; images re-encoded through GD so no payload survives

**Exit gate:** feature tests for full CRUD + authorization + upload rejection cases, all green.

---

### PHASE 5 — Public frontend + inline SEO

- `Public\{Home,Post,Category,Tag,Search,Page,Contact}Controller`
- `layouts/public.blade.php`: header (nav from active categories, search, mobile menu), footer, newsletter, scroll-to-top
- Pages (all server-rendered Blade): Home, Trending, Latest, Categories, Category detail, Post detail, Tag, Search, About, Contact, Privacy, Terms, Disclaimer
- Home sections: hero/trending, latest grid, trending rail, popular categories, featured, newsletter
- Post page: title, hero image, **real author**, date, reading time, category, sanitised body (`prose`), tags, share buttons, related posts, popular posts, newsletter CTA, AI-disclosure badge where `ai_generated`
- **Vue islands** on this phase: `SearchBox`, `NewsletterForm`, `LikeButton`, `CommentThread`, `ShareBar`, `CategoryFilter`. Article text is never inside an island.
- `SeoService` + `<x-seo.head>` component: title, description, canonical, OG, Twitter card, JSON-LD (Article, Breadcrumb, Organization, WebSite)
- Ad slot components `AdBanner / AdInArticle / AdSidebar / AdFooter` — **render nothing** unless `ADSENSE_ENABLED` and a slot ID is set. No placeholder boxes, no fake ads.
- Performance: eager loading everywhere, `loading="lazy"` + `width`/`height` on all images, responsive `srcset` from Phase 4 conversions, cached category nav

**Exit gate:** all public pages render with seeded data; zero N+1 (verified with Debugbar/`preventLazyLoading`); mobile Lighthouse ≥ 90 perf / 100 SEO.

---

### PHASE 6 — AI generation

- `config/ai.php` — providers, models, timeouts, retry, daily spend cap
- `App\Services\AI\`
  - `Contracts\AiProviderInterface`
  - `Providers\{AnthropicProvider, OpenAiProvider, GeminiProvider, FakeProvider}` — `FakeProvider` is what tests bind, so **no test ever hits a paid API**
  - `AiContentService` — orchestration
  - `PromptBuilder` + `Prompts/` templates, one per content type (news, trending, listicle, how-to, quiz, fact, story, entertainment, technology, travel, devotional, education)
  - `ResponseParser` — strict JSON schema, structured-output/tool-use enforced
  - `ContentValidator` — length, required fields, banned patterns, duplicate-title check, quality score
- Output sanitised through `HtmlSanitizer` before it is ever persisted
- `GenerateAiContentJob`: `$tries=3`, `$backoff=[30,120,300]`, `$timeout=180`, `failed()` marks the `ai_generations` row failed and notifies the admin
- Admin `AiGeneratorController`: form (topic, category, language, content type, tone, audience, length) → queued job → live status poll → preview → **one-click approve** → published or scheduled without leaving the page
- Generation history view: tokens, cost, status, retry
- Bulk review queue, so a batch of overnight drafts can be approved in one pass rather than one at a time — this is what keeps `AUTO_PUBLISH=false` practical at volume
- **API keys only from `.env`.** Never in code, never sent to the frontend, never logged.

*(When implementing this phase I will load the `claude-api` skill first so model IDs, structured-output syntax and pricing come from current reference rather than memory.)*

**Exit gate:** generation works against the real provider once, manually; the automated suite passes entirely on `FakeProvider`; a malformed/hostile AI response is rejected cleanly rather than stored.

---

### PHASE 7 — Trending, queues, scheduler

- `App\Services\Trending\`: `TrendingSourceInterface` + `RssSource`, `GoogleTrendsRssSource`, `NewsApiSource` (key-gated), `ManualSource`; `TrendingService` normalises, hashes, dedupes, scores
- **Legality:** RSS feeds and official APIs only. No HTML scraping, robots.txt respected, identifying User-Agent, per-source rate limits.
- Commands: `FetchTrendingTopics`, `GenerateTrendingContent`, `PublishScheduledPosts`, `AggregateDailyStats`, `CleanupOldData`, `RecalculateCounters`
- Jobs: `FetchTrendingTopicsJob`, `GenerateAiContentJob`, `PublishScheduledPostJob`, `ProcessPostViewJob`, `SendNewsletterJob`, `GenerateImageConversionsJob`
- Scheduler in `routes/console.php`:
  | Cadence | Task |
  |---|---|
  | every minute | `posts:publish-scheduled` (`withoutOverlapping`) |
  | every 2 hours | `trending:fetch` |
  | every 2 hours (offset) | `content:generate-trending` |
  | hourly | counter reconciliation |
  | daily 00:15 | `stats:aggregate` |
  | daily 03:00 | `data:cleanup` |
- Auto-publish pipeline honours `AUTO_PUBLISH`; even when true, a post must clear `ContentValidator` (min length, required SEO fields, quality score threshold, no duplicate title) or it stays a draft and the admin is notified

**Exit gate:** `schedule:work` + `queue:work` running locally produce a trending topic → draft post end to end; a deliberately failed job lands in `failed_jobs` with a useful log line.

---

### PHASE 8 — SEO infrastructure

- `GET /sitemap.xml` → index; `/sitemap-posts-{n}.xml` (chunked at 5 000 URLs), `/sitemap-categories.xml`, `/sitemap-tags.xml`, `/sitemap-pages.xml` — cached, invalidated on publish
- `GET /robots.txt` → dynamic; blocks `/admin`, `/login`, `?s=`; points at the sitemap; **the static `public/robots.txt` is removed** so it cannot shadow the route
- `GET /feed.xml` → RSS 2.0; `/feed/{category}.xml`
- `GET /ads.txt` → served from settings when AdSense is configured
- Duplicate-content controls: canonical on every page, `noindex` on paginated pages 2+ and empty search results, trailing-slash and www normalisation, 301 on slug change (a `post_slug_history` table gets added here if you want old URLs to keep working — I will confirm before adding it)
- Schema.org: Article, BreadcrumbList, Organization, WebSite+SearchAction, ItemList on archives

**Exit gate:** sitemap validates, rich-results test clean on a post page, feed validates.

---

### PHASE 9 — Contact, settings, activity log

**Dropped from this phase on your instruction: newsletter admin and the whole comments feature.**
The public newsletter subscribe → confirm → unsubscribe flow already shipped in Phase 5 and keeps working; what is dropped is the admin list/export screen, so subscribers are only readable in the database for now. Comments are dropped entirely — the tables exist from Phase 2 but nothing will read or write them, and no comment form appears on the site. That also removes the unmoderated-UGC risk from the AdSense review.

- Contact: Form Request, spam guards, admin inbox, mail notification
- Settings UI: grouped tabs (General, SEO, Social, AdSense, Analytics, AI, Advanced), logo/favicon/OG-image upload, cache flushed on save
- Activity log viewer with filters

**Exit gate:** contact form → inbox works; every settings tab saves and flushes cache; rate limits verified by test.

---

### PHASE 10 — Testing, performance, security, deployment

- Complete the suite: admin auth, post/category/tag CRUD, publishing + scheduling, AI generation (faked), search, trending pipeline, admin-vs-guest authorization matrix against every route
- Performance: `Model::preventLazyLoading()` in non-prod, slow-query log review, index verification with `EXPLAIN`, cache warming for trending/popular/categories, `optimize` in deploy
- Security pass: CSRF on all forms, rate limiters on login/comment/newsletter/contact/search/api, mass-assignment audit, upload hardening review, `APP_DEBUG=false` verification, security headers, no secret ever reaching the client bundle
- Deliver: `README.md` rewrite, `DEPLOYMENT.md`, `.env.example` finalised, all 20 final deliverables from brief §34

**Exit gate:** `php artisan test` fully green, `./vendor/bin/pint --test` clean, `npm run build` clean.

---

## E. File Manifest (target)

| Layer | Count | Location |
|---|---|---|
| Migrations | 22 | `database/migrations/` |
| Enums | 10 | `app/Enums/` |
| Models | 17 | `app/Models/` |
| Controllers | ~26 | `app/Http/Controllers/{Admin,Public,Api,Auth}/` |
| Form Requests | ~18 | `app/Http/Requests/` |
| Services | 12 | `app/Services/`, `app/Services/AI/`, `app/Services/Trending/` |
| Jobs | 6 | `app/Jobs/` |
| Console commands | 6 | `app/Console/Commands/` |
| Middleware | 1 | `app/Http/Middleware/` |
| Blade views | ~45 | `resources/views/{admin,public,auth,profile,errors}/` |
| Blade components | ~20 | `resources/views/components/` |
| Layouts | 4 | `resources/views/layouts/`, `resources/views/errors/layout.blade.php` |
| Vue islands | ~8 | `resources/js/Islands/` |
| Tests | ~30 | `tests/{Feature,Unit}/` |

No `app/Policies/` — see decision D2.

---

## F. `.env` Keys to Add

```dotenv
APP_TIMEZONE=Asia/Kolkata
DB_CONNECTION=mariadb          # changed from mysql
FILESYSTEM_DISK=public         # changed from local

# Admin seeding (dev only — never commit real values)
ADMIN_NAME="Site Admin"
ADMIN_EMAIL=admin@viralpulse.test
ADMIN_PASSWORD=

# AI
AI_PROVIDER=anthropic
AI_API_KEY=
AI_MODEL=
AI_MAX_TOKENS=4096
AI_TIMEOUT=120
AI_DAILY_GENERATION_LIMIT=50

# Content automation
AUTO_PUBLISH=false
AUTO_GENERATE_ENABLED=false
CONTENT_MIN_WORDS=400
CONTENT_MIN_QUALITY_SCORE=70

# Trending sources
TRENDING_RSS_FEEDS=
NEWS_API_KEY=
TRENDING_REGION=IN

# AdSense
ADSENSE_ENABLED=false
ADSENSE_CLIENT_ID=
ADSENSE_SLOT_HEADER=
ADSENSE_SLOT_ARTICLE=
ADSENSE_SLOT_SIDEBAR=
ADSENSE_SLOT_FOOTER=

# Analytics
GOOGLE_ANALYTICS_ID=
GOOGLE_SITE_VERIFICATION=

# Media
MEDIA_MAX_UPLOAD_KB=5120
MEDIA_IMAGE_DRIVER=gd
MEDIA_WEBP_ENABLED=true

# Retention
ANALYTICS_RETENTION_DAYS=90
ACTIVITY_LOG_RETENTION_DAYS=180
```

---

## G. Commands

**Setup**
```bash
composer install
npm install
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
npm run build
```

**Local development** (four terminals, or `composer run dev`)
```bash
php artisan serve
npm run dev
php artisan queue:work --tries=3
php artisan schedule:work
```

**Production**
```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan optimize          # config + route + view cache
php artisan storage:link
```
Cron: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
Queue: supervisor on Linux; NSSM or Task Scheduler if hosting on Windows.

---

## H. Honest Flags

1. **AdSense approval is not guaranteed by this build.** The platform will be technically compliant — no fake ads, no policy-violating placements, proper privacy/terms pages, disclosure of AI content. But approval depends on the *content* having genuine value. A site of unreviewed auto-generated articles is what Google's scaled-content-abuse policy exists to catch. `AUTO_PUBLISH=false` plus real editorial review is the path that actually works.
2. **No fake author data**, per your brief. Posts are attributed to real user accounts and AI-generated posts carry a visible disclosure.
3. **MariaDB 10.4 is older than the MySQL 8 you specified.** Everything above is designed for it. If you can upgrade to MySQL 8 or MariaDB 10.6+, search quality and JSON support both improve — but nothing in this plan requires it.
4. **`post_daily_stats` and possibly `post_slug_history`** are the only tables beyond your list. Both are justified above; say the word and I will drop either.

---

## I. Progress

| Phase | Status |
|---|---|
| 1 — Audit | ✅ Done |
| 2 — Database foundation | ✅ Done — 22 migrations, 17 models, 10 enums, seeders |
| 3 — Auth + admin foundation | ✅ Done — 47 tests green |
| 4 — Posts / categories / tags / media / users | ✅ Done — 94 tests green |
| 5 — Public website + SEO head | ✅ Done — 128 tests green |
| 6 — AI generation | ✅ Done — Gemini + OpenAI, quality gate, 149 tests green |
| 7 — Trending + scheduler + auto-publish | ✅ Done — feeds → scored topics → drip-published articles, 193 tests green |
| 8 — SEO infrastructure | ✅ Done — sitemaps, robots, RSS, ads.txt, 301s on rename, 222 tests green |
| 9 — Contact, settings, activity log | ✅ Done — inbox, schema-driven settings, SEO screen, log viewer, 257 tests green |
| 10 — Testing, performance, security, deployment | ✅ Done — security headers, cache driver fix, docs, 270 tests green |

**All ten phases complete.**

**Admin URL:** `/admin/login`. The public site does not link to it and there is no `/login` or `/register`.

**Admin credentials:** created by `AdminUserSeeder` from `.env`. Set `ADMIN_PASSWORD` before seeding, otherwise a strong password is generated and printed once.
