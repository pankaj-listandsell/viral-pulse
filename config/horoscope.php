<?php

/*
|--------------------------------------------------------------------------
| Horoscope URL map
|--------------------------------------------------------------------------
|
| Every horoscope path on the public site is declared here, per locale, and
| routes/web.php registers them from this table. Keeping them in one place is
| what makes the sitemap, the hreflang tags and the language switcher agree
| with the router - three things that silently disagreeing is the usual way an
| international site loses its index.
|
| The English paths are the ones already indexed and must not be edited.
|
*/

return [

    'paths' => [

        'en' => [
            'hub' => 'horoscope',
            // A sign gets its own page rather than an anchor on the hub: it is
            // the only way "aries horoscope today" can rank on something built
            // to answer exactly that, instead of on a page answering twelve
            // questions at once.
            'sign' => 'horoscope/{sign}',
            'compatibility' => 'zodiac-compatibility',
        ],

        'hi' => [
            // The money keyword sits in the parent segment, so every sign page
            // inherits it in the path as well as in its own slug.
            'hub' => 'hindi/aaj-ka-rashifal',
            'sign' => 'hindi/aaj-ka-rashifal/{sign}',
            'compatibility' => 'hindi/rashi-milan',
        ],

    ],

    /*
    | The sign slug a reader sees, per locale.
    |
    | A Hindi reader searches "mesh rashifal", never "aries", so the Hindi URL
    | says mesh. Internally every sign is still keyed by its English slug -
    | that key is what the database, the compatibility matrix and the cache all
    | agree on, and translating it at the edge keeps one canonical identity.
    */
    'slugs' => [

        'en' => [
            'aries' => 'aries',
            'taurus' => 'taurus',
            'gemini' => 'gemini',
            'cancer' => 'cancer',
            'leo' => 'leo',
            'virgo' => 'virgo',
            'libra' => 'libra',
            'scorpio' => 'scorpio',
            'sagittarius' => 'sagittarius',
            'capricorn' => 'capricorn',
            'aquarius' => 'aquarius',
            'pisces' => 'pisces',
        ],

        'hi' => [
            'aries' => 'mesh',
            'taurus' => 'vrishabh',
            'gemini' => 'mithun',
            'cancer' => 'kark',
            'leo' => 'singh',
            'virgo' => 'kanya',
            'libra' => 'tula',
            'scorpio' => 'vrishchik',
            'sagittarius' => 'dhanu',
            'capricorn' => 'makar',
            'aquarius' => 'kumbh',
            'pisces' => 'meen',
        ],

    ],

    /*
    | How long an AI-written reading may be missing before the page gives up
    | waiting and renders the static pool instead. Generation runs overnight;
    | if it has not landed by the time a reader arrives, the page must still be
    | complete rather than empty.
    */
    'ai' => [
        'enabled' => env('HOROSCOPE_AI_READINGS', true),
        'locales' => ['en', 'hi'],
        'periods' => ['daily', 'weekly', 'monthly'],
    ],

];
