<?php

/*
|--------------------------------------------------------------------------
| Public site locales
|--------------------------------------------------------------------------
|
| English is served from the site root and Hindi from a /hindi prefix. The
| root is deliberately left un-prefixed: every English URL that already ranks
| keeps the exact path it was indexed under, so adding Hindi costs nothing in
| existing traffic.
|
| `hreflang` is regional on purpose. The audience is India for both languages,
| and en-IN tells Google which English variant this is rather than letting it
| guess between en-US and en-GB.
|
*/

return [

    'default' => 'en',

    'supported' => [

        'en' => [
            'code' => 'en',
            'name' => 'English',
            // Rendered in the language switcher, so it is written the way a
            // speaker of that language would recognise it.
            'native' => 'English',
            'hreflang' => 'en-IN',
            'prefix' => null,
            // Outfit and Instrument Sans carry no Devanagari glyphs, so a
            // locale that needs them names its own face here and the layout
            // loads it for that locale only.
            'font' => null,
            'font_stack' => null,
        ],

        'hi' => [
            'code' => 'hi',
            'name' => 'Hindi',
            'native' => 'हिन्दी',
            'hreflang' => 'hi-IN',
            'prefix' => 'hindi',
            'font' => 'noto-sans-devanagari:400,500,600,700',
            // The bare family name. The template adds the CSS quotes, because
            // a quote coming through Blade's escaping arrives as &#039; and
            // silently breaks the declaration it was meant to protect.
            'font_stack' => 'Noto Sans Devanagari',
        ],

    ],

];
