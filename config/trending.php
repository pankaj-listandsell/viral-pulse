<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Sources
    |--------------------------------------------------------------------------
    |
    | Published feeds and official APIs only. Nothing here scrapes HTML: it
    | breaks on every redesign, it is against most sites' terms, and it gets the
    | server's IP blocked - which would take the whole pipeline down.
    |
    | {region} and {language} are substituted from the values below.
    |
    */

    'sources' => [

        'google_trends' => [
            'enabled' => env('TRENDING_GOOGLE_TRENDS', true),
            'source' => 'google_trends',
            'url' => 'https://trends.google.com/trending/rss?geo={region}',
            // Trends is a ranked list of what people are actually searching for,
            // which is a better signal of demand than a newsroom's front page.
            'weight' => 30,
        ],

        'google_news' => [
            'enabled' => env('TRENDING_GOOGLE_NEWS', true),
            'source' => 'rss',
            'url' => 'https://news.google.com/rss?hl={language}-{region}&gl={region}&ceid={region}:{language}',
            'weight' => 18,
        ],

        'news_api' => [
            // Only used when a key is present; the free tier is enough here.
            'enabled' => true,
            'source' => 'news_api',
            'url' => 'https://newsapi.org/v2/top-headlines',
            'key' => env('NEWS_API_KEY'),
            'weight' => 15,
        ],

    ],

    /*
    | Extra feeds, comma separated in TRENDING_RSS_FEEDS. Any RSS or Atom URL
    | works; each entry may be "url" or "url|category-slug".
    */

    'custom_feeds' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('TRENDING_RSS_FEEDS'))
    ))),

    'custom_feed_weight' => 12,

    /*
    |--------------------------------------------------------------------------
    | Fetching
    |--------------------------------------------------------------------------
    */

    'region' => env('TRENDING_REGION', 'IN'),
    'language' => env('TRENDING_LANGUAGE', 'en'),
    'timeout' => (int) env('TRENDING_TIMEOUT', 20),
    'per_source_limit' => (int) env('TRENDING_PER_SOURCE_LIMIT', 25),
    'user_agent' => 'ViralPulseBot/1.0 (+'.env('APP_URL', 'http://localhost').')',

    /*
    |--------------------------------------------------------------------------
    | Scoring
    |--------------------------------------------------------------------------
    |
    | A 0-100 estimate of how much traffic a topic is worth chasing. It is a
    | heuristic, not a measurement - it only has to rank topics against each
    | other well enough to pick the next few worth writing about.
    |
    */

    'scoring' => [
        // Freshness decays to zero over this many hours.
        'freshness_hours' => 24,
        'freshness_weight' => 35,

        // Corroboration: the same topic showing up from several feeds is the
        // strongest signal available here that it is genuinely trending.
        'corroboration_weight' => 20,

        // Google Trends publishes an approximate search volume; when present it
        // is the only real demand number in the whole pipeline.
        'volume_weight' => 15,

        // Headline shape that historically earns clicks without being clickbait.
        'headline_weight' => 10,

        // Whether the topic maps onto a category we actually publish in.
        'category_weight' => 10,
    ],

    /*
    | Words that make a headline more likely to be searched for. Deliberately
    | mild: "shocking" and "you won't believe" are what AdSense reviewers treat
    | as clickbait, so they are not rewarded here.
    */

    'headline_signals' => [
        'how', 'why', 'what', 'when', 'guide', 'explained', 'vs', 'best',
        'top', 'list', 'review', 'result', 'results', 'live', 'update',
        'date', 'time', 'price', 'launch', 'winner', 'score', 'full',
    ],

    /*
    |--------------------------------------------------------------------------
    | Blocklist
    |--------------------------------------------------------------------------
    |
    | Topics matching these are stored but never auto-generated. Two reasons:
    | AdSense demonetises shocking content and sensitive events, and an AI
    | writing unverified copy about a death, a crime or a disaster is how a site
    | ends up publishing something defamatory. The admin can still generate one
    | by hand if it genuinely belongs on the site.
    |
    */

    'blocklist' => [
        'death', 'dead', 'died', 'dies', 'killed', 'murder', 'suicide',
        'rape', 'assault', 'shooting', 'shot dead', 'terror', 'bomb',
        'blast', 'crash', 'accident', 'earthquake', 'flood disaster',
        'obituary', 'funeral', 'arrested', 'jailed', 'scam', 'fraud',
        'lawsuit', 'porn', 'nude', 'leaked video', 'mms',
    ],

    /*
    |--------------------------------------------------------------------------
    | Automation
    |--------------------------------------------------------------------------
    |
    | Off by default. Turning it on spends money on every scheduled run, so it
    | is an explicit decision rather than something that starts by accident.
    |
    */

    'automation' => [
        'enabled' => env('AUTO_GENERATE_ENABLED', false),

        // Articles started per scheduled run.
        'per_run' => (int) env('TRENDING_GENERATE_PER_RUN', 2),

        // A topic below this score is not worth an API call.
        'min_score' => (int) env('TRENDING_MIN_SCORE', 45),

        // Stale topics are already covered by everyone else; writing about them
        // ranks nowhere.
        'max_age_hours' => (int) env('TRENDING_MAX_AGE_HOURS', 36),

        'target_words' => (int) env('TRENDING_TARGET_WORDS', 900),
        'content_type' => 'news',
        'tone' => 'informative',
    ],

    /*
    |--------------------------------------------------------------------------
    | Publishing window
    |--------------------------------------------------------------------------
    |
    | Generated posts are dripped out rather than dumped. Ten articles appearing
    | in the same minute is the pattern Google's scaled-content-abuse policy
    | looks for, and a steady stream also gives each post its own slot in the
    | feed instead of burying the others.
    |
    | Times are in the app timezone.
    |
    */

    'publishing' => [
        // Exact times of day, set from Settings -> Publishing. When empty the
        // window and gap below are used to space posts out instead.
        // 'immediate' writes the article when its time arrives and publishes it
        // as soon as it is ready. 'scheduled' writes ahead and publishes exactly
        // on the minute, which survives a failed generation.
        'mode' => env('PUBLISH_MODE', 'scheduled'),

        'slots' => env('PUBLISH_SLOTS'),

        // How far ahead an article may be written for. Trending content goes
        // stale fast, so writing tomorrow's posts today produces articles that
        // are wrong by the time anyone reads them. 0 removes the limit.
        'max_lookahead_hours' => (int) env('PUBLISH_LOOKAHEAD_HOURS', 3),

        'window_start' => env('PUBLISH_WINDOW_START', '07:00'),
        'window_end' => env('PUBLISH_WINDOW_END', '22:00'),

        // Minimum gap between two automatically published posts.
        'gap_minutes' => (int) env('PUBLISH_GAP_MINUTES', 90),

        // Hard ceiling per day, counting both scheduled and already published.
        'max_per_day' => (int) env('PUBLISH_MAX_PER_DAY', 8),

        // Never schedule closer than this to now, so a slot is not missed while
        // the article is still being written.
        'lead_minutes' => (int) env('PUBLISH_LEAD_MINUTES', 15),
    ],

    /*
    |--------------------------------------------------------------------------
    | Category routing
    |--------------------------------------------------------------------------
    |
    | First matching keyword wins, so order matters. Anything unmatched falls
    | back to the category named below.
    |
    */

    'fallback_category' => 'trending',

    'category_keywords' => [
        'sports' => [
            'cricket', 'ipl', 'odi', 't20', 'test match', 'football', 'fifa',
            'world cup', 'olympic', 'kabaddi', 'hockey', 'tennis', 'badminton',
            'wrestling', 'match', 'innings', 'wicket', 'goal', 'tournament',
            'league', 'champion', 'score',
        ],
        'technology' => [
            'ai', 'artificial intelligence', 'chatgpt', 'gemini', 'openai',
            'iphone', 'android', 'samsung', 'smartphone', 'laptop', 'app',
            'software', 'startup', 'tech', 'google', 'microsoft', 'apple',
            'launch', 'update', 'feature', '5g', 'chip', 'processor', 'ev',
        ],
        'entertainment' => [
            'movie', 'film', 'bollywood', 'hollywood', 'trailer', 'teaser',
            'box office', 'netflix', 'prime video', 'web series', 'song',
            'album', 'actor', 'actress', 'celebrity', 'award', 'ott', 'ott release',
        ],
        'business' => [
            'stock', 'sensex', 'nifty', 'ipo', 'market', 'rupee', 'gdp',
            'inflation', 'rbi', 'bank', 'gst', 'tax', 'budget', 'economy',
            'investment', 'crypto', 'bitcoin', 'gold rate', 'salary',
        ],
        'health' => [
            'health', 'covid', 'vaccine', 'diet', 'fitness', 'yoga', 'mental health',
            'symptom', 'doctor', 'hospital', 'nutrition', 'sleep', 'weight loss',
        ],
        'education' => [
            'exam', 'result', 'admit card', 'neet', 'jee', 'upsc', 'board exam',
            'university', 'college', 'admission', 'scholarship', 'recruitment',
            'vacancy', 'syllabus', 'answer key', 'cutoff',
        ],
        'travel' => [
            'travel', 'tourism', 'flight', 'airport', 'railway', 'train',
            'visa', 'passport', 'hotel', 'destination', 'trip', 'holiday',
        ],
        'devotional' => [
            'temple', 'puja', 'festival', 'diwali', 'holi', 'navratri', 'eid',
            'christmas', 'ganesh', 'shiva', 'krishna', 'ram', 'mantra', 'vrat',
            'muhurat', 'panchang', 'horoscope', 'rashifal',
        ],
        'lifestyle' => [
            'fashion', 'recipe', 'food', 'home', 'garden', 'relationship',
            'parenting', 'beauty', 'skincare', 'wedding', 'shopping',
        ],
        'news' => [
            'government', 'minister', 'parliament', 'election', 'policy',
            'political', 'politics', 'supreme court', 'high court', 'protest',
            'weather', 'monsoon', 'announcement', 'scheme', 'bill', 'session',
        ],
    ],

];
