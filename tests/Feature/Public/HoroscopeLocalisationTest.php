<?php

namespace Tests\Feature\Public;

use App\Services\LocaleService;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The bilingual horoscope section.
 *
 * Most of what is asserted here is invisible on the page and only matters to a
 * crawler - reciprocal hreflang, self-referencing canonicals, one URL per sign
 * per language. That is exactly why it is worth a test: nobody notices these
 * breaking until the traffic has already gone.
 */
class HoroscopeLocalisationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(SettingSeeder::class);
    }

    public function test_every_horoscope_url_answers_in_both_languages(): void
    {
        foreach ([
            '/horoscope',
            '/horoscope/aries',
            '/horoscope/pisces',
            '/zodiac-compatibility',
            '/hindi/aaj-ka-rashifal',
            '/hindi/aaj-ka-rashifal/mesh',
            '/hindi/aaj-ka-rashifal/meen',
            '/hindi/rashi-milan',
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_the_page_declares_the_language_its_url_promised(): void
    {
        $this->get('/horoscope/aries')->assertSee('<html lang="en"', false);
        $this->get('/hindi/aaj-ka-rashifal/mesh')->assertSee('<html lang="hi"', false);
    }

    public function test_the_hindi_page_is_actually_written_in_hindi(): void
    {
        $response = $this->get('/hindi/aaj-ka-rashifal/mesh')->assertOk();

        // The sign's own name, its element and its ruling planet, all in
        // Devanagari - if any of these came back in English the lang file is
        // not being reached.
        $response->assertSee('मेष', false);
        $response->assertSee('अग्नि', false);
        $response->assertSee('मंगल', false);

        // And the query the page exists to answer is in the title.
        $response->assertSee('मेष राशिफल आज', false);

        // Verify Rashi letters are displayed
        $response->assertSee('राशि अक्षर', false);
        $response->assertSee('चू, चे, चो, ला, ली, लू, ले, लो, अ', false);
    }

    public function test_the_english_page_shows_rashi_letters(): void
    {
        $this->get('/horoscope/aries')
            ->assertOk()
            ->assertSee('Name letters', false)
            ->assertSee('A, L, E', false);
    }

    public function test_the_hindi_compatibility_page_is_actually_written_in_hindi(): void
    {
        $response = $this->get('/hindi/rashi-milan')->assertOk();

        // Check that the table headers, legend, and details are in Hindi Devanagari
        $response->assertSee('तत्व', false);
        $response->assertSee('स्वभाव', false);
        $response->assertSee('स्वामी ग्रह', false);
        $response->assertSee('तारीख़ें', false);
        $response->assertSee('राशि अनुकूलता चार्ट', false);
        $response->assertSee('प्रेम और रोमांस', false);
        $response->assertSee('दोस्ती', false);
        $response->assertSee('संवाद', false);
        $response->assertSee('विश्वास', false);
        $response->assertSee('साझा मूल्य', false);
    }

    public function test_each_language_names_the_other_in_reciprocal_hreflang(): void
    {
        $en = url('/horoscope/aries');
        $hi = url('/hindi/aaj-ka-rashifal/mesh');

        foreach ([$en, $hi] as $url) {
            $response = $this->get($url)->assertOk();

            $response->assertSee('<link rel="alternate" hreflang="en-IN" href="'.$en.'">', false);
            $response->assertSee('<link rel="alternate" hreflang="hi-IN" href="'.$hi.'">', false);
            // English is what a reader whose language the site does not publish
            // should land on.
            $response->assertSee('<link rel="alternate" hreflang="x-default" href="'.$en.'">', false);
        }
    }

    public function test_every_page_canonicalises_to_itself(): void
    {
        foreach ([
            '/horoscope',
            '/horoscope/leo',
            '/hindi/aaj-ka-rashifal',
            '/hindi/aaj-ka-rashifal/singh',
        ] as $path) {
            $this->get($path)
                ->assertOk()
                ->assertSee('<link rel="canonical" href="'.url($path).'">', false);
        }
    }

    public function test_the_other_languages_slug_redirects_rather_than_404ing(): void
    {
        // A Hindi slug on an English path. The path already declared English,
        // so the reader is moved to the English slug rather than switched into
        // a language they did not ask for.
        $this->get('/horoscope/mesh')->assertRedirect(url('/horoscope/aries'));
        $this->get('/hindi/aaj-ka-rashifal/aries')->assertRedirect(url('/hindi/aaj-ka-rashifal/mesh'));

        // Permanent, so only one of the two is ever indexed.
        $this->get('/horoscope/mesh')->assertStatus(301);
    }

    public function test_something_that_is_not_a_sign_is_a_404(): void
    {
        $this->get('/horoscope/ophiuchus')->assertNotFound();
        $this->get('/hindi/aaj-ka-rashifal/ophiuchus')->assertNotFound();
    }

    public function test_a_hindi_page_keeps_its_reader_in_hindi(): void
    {
        $html = $this->get('/hindi/aaj-ka-rashifal')->assertOk()->getContent();

        // The header, the wheel, the sign cards and the footer all link onward.
        // If any of them still called route('horoscope') the reader would be
        // dropped back into English on their first click.
        $this->assertStringContainsString(url('/hindi/rashi-milan'), $html);
        $this->assertStringContainsString(url('/hindi/aaj-ka-rashifal/mesh'), $html);

        /*
         * Anything a reader can click through to the English hub must be a
         * deliberate language switch - the one in the hero and the one in the
         * footer both are, and both say so with hreflang.
         *
         * The count is not pinned, because adding a second, better-placed
         * switcher is an improvement rather than a regression. What must never
         * appear is an ordinary navigation link that quietly leaves Hindi.
         *
         * Only anchors are examined; the <link rel="alternate"> tags in the
         * head name the English URL too, and are supposed to.
         */
        preg_match_all('#<a\b[^>]*href="'.preg_quote(url('/horoscope'), '#').'"[^>]*>#s', $html, $anchors);

        $this->assertNotEmpty($anchors[0], 'A Hindi page should offer a way back to English.');

        foreach ($anchors[0] as $anchor) {
            $this->assertStringContainsString(
                'hreflang="en"',
                $anchor,
                'Every link from a Hindi page into English must be a language switch: '.$anchor
            );
        }
    }

    public function test_the_home_page_links_to_every_sign_page_without_javascript(): void
    {
        $locales = app(LocaleService::class);
        $html = $this->get('/')->assertOk()->getContent();

        // The zodiac strip is a Vue island, but its server-rendered state is
        // the strip itself rather than a row of grey boxes. The home page is
        // the strongest page on the site, so twelve links from it into the
        // sign pages is the most valuable internal linking available - and a
        // crawler will not run the island to find them.
        foreach (array_keys(config('horoscope.slugs.en')) as $sign) {
            $this->assertStringContainsString(
                'href="'.$locales->horoscopeUrl('sign', $sign, 'en').'"',
                $html,
                "The home page should link to the {$sign} page even before the island mounts."
            );
        }
    }

    public function test_the_hub_links_to_every_sign_page(): void
    {
        $locales = app(LocaleService::class);

        foreach (['en' => '/horoscope', 'hi' => '/hindi/aaj-ka-rashifal'] as $locale => $hub) {
            $html = $this->get($hub)->assertOk()->getContent();

            foreach (array_keys(config('horoscope.slugs.en')) as $sign) {
                $this->assertStringContainsString(
                    $locales->horoscopeUrl('sign', $sign, $locale),
                    $html,
                    "The {$locale} hub should link to the {$sign} page."
                );
            }
        }
    }

    public function test_the_sitemap_lists_every_page_in_every_language(): void
    {
        $locales = app(LocaleService::class);
        $xml = $this->get('/sitemap-pages.xml')->assertOk()->getContent();

        foreach ($locales->codes() as $locale) {
            $this->assertStringContainsString($locales->horoscopeUrl('hub', null, $locale), $xml);
            $this->assertStringContainsString($locales->horoscopeUrl('compatibility', null, $locale), $xml);

            foreach (array_keys(config('horoscope.slugs.en')) as $sign) {
                $this->assertStringContainsString(
                    $locales->horoscopeUrl('sign', $sign, $locale),
                    $xml,
                    "The sitemap is missing the {$locale} {$sign} page."
                );
            }
        }
    }

    public function test_a_compatibility_pair_resolves_in_each_languages_own_slugs(): void
    {
        $this->get('/zodiac-compatibility?sign1=aries&sign2=leo')
            ->assertOk()
            ->assertSee('Aries and Leo Compatibility: 94% Love Match', false);

        $this->get('/hindi/rashi-milan?sign1=mesh&sign2=singh')
            ->assertOk()
            ->assertSee('मेष और सिंह का मिलान: 94% प्रेम अनुकूलता', false);
    }

    public function test_a_pair_is_one_page_whichever_order_it_is_asked_for(): void
    {
        $canonical = url('/hindi/rashi-milan?sign1=mesh&sign2=singh');

        foreach (['sign1=mesh&sign2=singh', 'sign1=singh&sign2=mesh'] as $query) {
            $this->get("/hindi/rashi-milan?{$query}")
                ->assertOk()
                ->assertSee('<link rel="canonical" href="'.e($canonical).'">', false);
        }
    }

    public function test_a_pairs_alternates_name_the_same_pair_in_the_other_language(): void
    {
        $this->get('/zodiac-compatibility?sign1=aries&sign2=leo')
            ->assertOk()
            ->assertSee('hreflang="hi-IN" href="'.e(url('/hindi/rashi-milan?sign1=mesh&sign2=singh')).'"', false);
    }

    public function test_only_the_hindi_page_pays_for_the_devanagari_font(): void
    {
        $this->get('/hindi/aaj-ka-rashifal')->assertSee('noto-sans-devanagari', false);
        $this->get('/horoscope')->assertDontSee('noto-sans-devanagari', false);
    }

    public function test_a_sign_page_carries_the_structured_data_it_needs(): void
    {
        $html = $this->get('/horoscope/aries')->assertOk()->getContent();

        // Article, because the reading is rewritten every morning and Article
        // is the type whose dates Google reads for freshness.
        $this->assertStringContainsString('"@type":"Article"', $html);
        $this->assertStringContainsString('"@type":"BreadcrumbList"', $html);
        $this->assertStringContainsString('"@type":"FAQPage"', $html);
    }

    public function test_the_faq_answers_are_on_the_page_and_specific_to_the_sign(): void
    {
        // Google drops an FAQ rich result whose answer a reader cannot see, so
        // the answer has to be rendered, not only marked up.
        $this->get('/horoscope/aries')
            ->assertOk()
            ->assertSee('Which planet rules Aries?', false)
            ->assertSee('Red Coral', false);

        // The same question on another sign resolves to that sign's own facts.
        $this->get('/horoscope/leo')
            ->assertOk()
            ->assertSee('Which planet rules Leo?', false)
            ->assertSee('Ruby', false);
    }

    public function test_a_sign_page_can_be_shared_without_javascript(): void
    {
        $html = $this->get('/hindi/aaj-ka-rashifal/mesh')->assertOk()->getContent();

        preg_match('#api\.whatsapp\.com/send\?text=([^"]+)#', $html, $match);

        $this->assertNotEmpty($match, 'The share link should be in the HTML, not built by JavaScript.');

        $text = urldecode($match[1]);

        // What travels is the reading itself. A horoscope link carrying only a
        // title is one nobody forwards, which is the whole point of the block.
        $this->assertStringContainsString('मेष', $text);
        $this->assertStringContainsString('शुभ अंक', $text);
        $this->assertStringContainsString('शुभ रंग', $text);

        // And the link at the end points back at the page being shared.
        $this->assertStringContainsString(url('/hindi/aaj-ka-rashifal/mesh'), $text);
    }

    public function test_the_whole_section_can_be_switched_off(): void
    {
        $this->setting('horoscope_enabled', false);

        foreach ([
            '/horoscope',
            '/horoscope/aries',
            '/zodiac-compatibility',
            '/hindi/aaj-ka-rashifal',
            '/hindi/aaj-ka-rashifal/mesh',
            '/hindi/rashi-milan',
        ] as $url) {
            $this->get($url)->assertNotFound();
        }
    }

    private function setting(string $key, bool $value): void
    {
        \App\Models\Setting::where('key', $key)->update(['value' => $value ? '1' : '0']);
        app(\App\Services\SettingsService::class)->flush();
    }
}
