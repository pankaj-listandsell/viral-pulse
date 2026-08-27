<?php

/*
|--------------------------------------------------------------------------
| Horoscope copy - English
|--------------------------------------------------------------------------
|
| Everything a reader sees on the horoscope pages lives here rather than in
| HoroscopeService, so a second language is a second file instead of a second
| copy of the logic.
|
| The `pools` arrays must stay the same length in every language and stay in
| the same order. A reading is picked by an index seeded from the sign and the
| date, so entry 3 in English and entry 3 in Hindi are the same prediction -
| a reader who switches language mid-page sees a translation rather than a
| different forecast for the same day.
|
*/

return [

    /*
    | Per-sign copy. The untranslatable half of a sign - its slug, symbol,
    | element key, colour, image and matches - stays in HoroscopeService,
    | because those are identities rather than words.
    */
    'signs' => [

        'aries' => [
            'name' => 'Aries',
            'vedic' => 'Mesh (मेष)',
            'symbol_name' => 'The Ram',
            'dates' => 'Mar 21 – Apr 19',
            'traits' => 'Bold, Ambitious, Energetic',
            'strengths' => ['Courageous', 'Determined', 'Honest'],
            'weaknesses' => ['Impatient', 'Short-tempered'],
            'about' => 'Aries is the first sign of the zodiac and it shows: ruled by Mars, Aries natives move first and think later, which is exactly why they win the races nobody else dares to start. Their honesty is disarming and their energy is contagious, though patience remains the lesson of a lifetime.',
            'letters' => 'A, L, E',
        ],

        'taurus' => [
            'name' => 'Taurus',
            'vedic' => 'Vrishabh (वृषभ)',
            'symbol_name' => 'The Bull',
            'dates' => 'Apr 20 – May 20',
            'traits' => 'Reliable, Patient, Practical',
            'strengths' => ['Loyal', 'Patient', 'Grounded'],
            'weaknesses' => ['Stubborn', 'Possessive'],
            'about' => 'Venus-ruled Taurus builds slowly and builds to last — in money, in love, and in the comforts they surround themselves with. Their patience looks like stubbornness from the outside, but it is really the confidence of someone who has never needed to rush a good thing.',
            'letters' => 'B, V, U',
        ],

        'gemini' => [
            'name' => 'Gemini',
            'vedic' => 'Mithun (मिथुन)',
            'symbol_name' => 'The Twins',
            'dates' => 'May 21 – Jun 20',
            'traits' => 'Curious, Adaptable, Witty',
            'strengths' => ['Quick-witted', 'Sociable', 'Adaptable'],
            'weaknesses' => ['Restless', 'Indecisive'],
            'about' => 'Mercury gives Gemini the fastest mind in the zodiac and a talent for talking their way into — and out of — anything. They collect ideas the way others collect possessions, and boredom, not difficulty, is the only thing that ever defeats them.',
            'letters' => 'K, Chh, Gh',
        ],

        'cancer' => [
            'name' => 'Cancer',
            'vedic' => 'Kark (कर्क)',
            'symbol_name' => 'The Crab',
            'dates' => 'Jun 21 – Jul 22',
            'traits' => 'Intuitive, Caring, Protective',
            'strengths' => ['Empathetic', 'Loyal', 'Intuitive'],
            'weaknesses' => ['Moody', 'Over-sensitive'],
            'about' => 'Ruled by the Moon, Cancer feels the weather of a room before anyone speaks. Home and chosen family are the axis their whole chart turns on, and the famous hard shell exists only to protect a remarkably soft, remarkably accurate instinct.',
            'letters' => 'H, D',
        ],

        'leo' => [
            'name' => 'Leo',
            'vedic' => 'Singh (सिंह)',
            'symbol_name' => 'The Lion',
            'dates' => 'Jul 23 – Aug 22',
            'traits' => 'Confident, Generous, Charismatic',
            'strengths' => ['Warm-hearted', 'Creative', 'Loyal'],
            'weaknesses' => ['Proud', 'Needs applause'],
            'about' => 'The Sun rules Leo, and like the Sun they are impossible to place anywhere but the centre. Their generosity is genuine and enormous — Leo gives away credit, money and loyalty freely — but withhold appreciation and the lion notices immediately.',
            'letters' => 'M, T',
        ],

        'virgo' => [
            'name' => 'Virgo',
            'vedic' => 'Kanya (कन्या)',
            'symbol_name' => 'The Maiden',
            'dates' => 'Aug 23 – Sep 22',
            'traits' => 'Analytical, Helpful, Meticulous',
            'strengths' => ['Precise', 'Reliable', 'Practical'],
            'weaknesses' => ['Over-critical', 'Worrier'],
            'about' => 'Virgo is the quiet engine room of the zodiac — the one who notices the detail everyone else scrolled past and fixes it before it becomes a problem. Their criticism, including the constant self-criticism, comes from a sincere wish to make things work properly.',
            'letters' => 'P, Sh, Th',
        ],

        'libra' => [
            'name' => 'Libra',
            'vedic' => 'Tula (तुला)',
            'symbol_name' => 'The Scales',
            'dates' => 'Sep 23 – Oct 22',
            'traits' => 'Charming, Balanced, Diplomatic',
            'strengths' => ['Fair-minded', 'Charming', 'Diplomatic'],
            'weaknesses' => ['Indecisive', 'Conflict-avoidant'],
            'about' => 'Venus-ruled Libra reads a situation for its balance the way a musician reads a room for its pitch. They are the natural mediators of the zodiac, which is also why choosing for themselves — a restaurant, a career, a person — can take three times longer than it should.',
            'letters' => 'R, T',
        ],

        'scorpio' => [
            'name' => 'Scorpio',
            'vedic' => 'Vrishchik (वृश्चिक)',
            'symbol_name' => 'The Scorpion',
            'dates' => 'Oct 23 – Nov 21',
            'traits' => 'Passionate, Resourceful, Magnetic',
            'strengths' => ['Fearless', 'Loyal', 'Perceptive'],
            'weaknesses' => ['Secretive', 'Unforgiving'],
            'about' => 'Scorpio does nothing by halves. Ruled by Mars and Pluto, they are built for the intense end of every experience — total loyalty, total focus, total transformation — and they can read a motive across a crowded room long before it is admitted out loud.',
            'letters' => 'N, Y',
        ],

        'sagittarius' => [
            'name' => 'Sagittarius',
            'vedic' => 'Dhanu (धनु)',
            'symbol_name' => 'The Archer',
            'dates' => 'Nov 22 – Dec 21',
            'traits' => 'Optimistic, Adventurous, Honest',
            'strengths' => ['Optimistic', 'Free-spirited', 'Frank'],
            'weaknesses' => ['Blunt', 'Commitment-shy'],
            'about' => 'Jupiter hands Sagittarius luck, appetite and an incurable belief that the next horizon is better than this one. They tell the truth even when tact would serve them better, and their optimism has an odd habit of turning out to be justified.',
            'letters' => 'Bh, Dh, Ph, Ddh',
        ],

        'capricorn' => [
            'name' => 'Capricorn',
            'vedic' => 'Makar (मकर)',
            'symbol_name' => 'The Goat',
            'dates' => 'Dec 22 – Jan 19',
            'traits' => 'Disciplined, Strategic, Ambitious',
            'strengths' => ['Disciplined', 'Strategic', 'Responsible'],
            'weaknesses' => ['Workaholic', 'Reserved'],
            'about' => 'Saturn teaches Capricorn early that nothing worthwhile is quick, and they build accordingly — one deliberate step at a time, up a mountain most people never attempt. The dry humour is real, and so is the softness they show only to the few who get past the gate.',
            'letters' => 'Kh, J',
        ],

        'aquarius' => [
            'name' => 'Aquarius',
            'vedic' => 'Kumbh (कुंभ)',
            'symbol_name' => 'The Water Bearer',
            'dates' => 'Jan 20 – Feb 18',
            'traits' => 'Visionary, Independent, Original',
            'strengths' => ['Original', 'Humanitarian', 'Independent'],
            'weaknesses' => ['Detached', 'Contrary'],
            'about' => 'Aquarius arrives at the answer from an angle nobody else considered, which is why they are so often ahead of the room and so rarely comfortable inside it. They care deeply about people in general and guard their personal freedom fiercely.',
            'letters' => 'G, S, Sh',
        ],

        'pisces' => [
            'name' => 'Pisces',
            'vedic' => 'Meen (मीन)',
            'symbol_name' => 'The Fish',
            'dates' => 'Feb 19 – Mar 20',
            'traits' => 'Empathetic, Creative, Mystical',
            'strengths' => ['Compassionate', 'Imaginative', 'Gentle'],
            'weaknesses' => ['Escapist', 'Over-trusting'],
            'about' => 'The last sign of the zodiac carries a little of all the others, which is why Pisces absorbs the mood of everyone around them. Neptune gives them the imagination of an artist and the boundaries of a sponge — protecting their own energy is the single skill that changes their life.',
            'letters' => 'D, Ch, Th, Jh',
        ],

    ],

    'elements' => [
        'Fire' => [
            'name' => 'Fire',
            'traits' => 'Passionate, spontaneous, led by instinct and appetite.',
            'pairs' => 'Happiest with Fire and Air signs.',
        ],
        'Earth' => [
            'name' => 'Earth',
            'traits' => 'Grounded, patient, loyal to whatever they build.',
            'pairs' => 'Happiest with Earth and Water signs.',
        ],
        'Air' => [
            'name' => 'Air',
            'traits' => 'Curious, communicative, driven by ideas.',
            'pairs' => 'Happiest with Air and Fire signs.',
        ],
        'Water' => [
            'name' => 'Water',
            'traits' => 'Intuitive, emotional, devoted well past reason.',
            'pairs' => 'Happiest with Water and Earth signs.',
        ],
    ],

    /*
    | Single words that appear in the sign fact table. Keyed by the English
    | term HoroscopeService stores, so a missing translation falls back to a
    | readable word rather than to a raw key.
    */
    'qualities' => [
        'Cardinal' => 'Cardinal',
        'Fixed' => 'Fixed',
        'Mutable' => 'Mutable',
    ],

    'planets' => [
        'Mars' => 'Mars',
        'Venus' => 'Venus',
        'Mercury' => 'Mercury',
        'Moon' => 'Moon',
        'Sun' => 'Sun',
        'Pluto & Mars' => 'Pluto & Mars',
        'Jupiter' => 'Jupiter',
        'Saturn' => 'Saturn',
        'Uranus' => 'Uranus',
        'Neptune' => 'Neptune',
    ],

    'gemstones' => [
        'Red Coral' => 'Red Coral',
        'Diamond' => 'Diamond',
        'Emerald' => 'Emerald',
        'Pearl' => 'Pearl',
        'Ruby' => 'Ruby',
        'Opal' => 'Opal',
        'Topaz' => 'Topaz',
        'Yellow Sapphire' => 'Yellow Sapphire',
        'Blue Sapphire' => 'Blue Sapphire',
        'Amethyst' => 'Amethyst',
        'Aquamarine' => 'Aquamarine',
    ],

    'days' => [
        'Sunday' => 'Sunday',
        'Monday' => 'Monday',
        'Tuesday' => 'Tuesday',
        'Wednesday' => 'Wednesday',
        'Thursday' => 'Thursday',
        'Friday' => 'Friday',
        'Saturday' => 'Saturday',
    ],

    /*
    | The static reading pools.
    |
    | These are the fallback, not the main event: a reading written by the AI
    | writer for this sign and this date is preferred whenever one exists. They
    | matter on the mornings generation has not landed yet, or has failed, and
    | on those mornings the page still has to be complete rather than empty.
    |
    | Every array here must keep its length and order across languages.
    */
    'pools' => [

        'overviews' => [
            'Cosmic planetary alignments favour decisive action today. Your sharp intuition and creative clarity open doors that looked shut yesterday.',
            'A harmonious solar vibration surrounds you today. Collaboration and plain speaking resolve a bottleneck that has been draining you for weeks.',
            'High vitality and fresh motivation define your day. Channel it into the one priority you keep postponing and expect a breakthrough by evening.',
            'The stars highlight transformation and long-range planning. Trust your own compass over the loudest voice in the room when the decision arrives.',
            'A burst of inspiration sparks an idea worth writing down. Networking and speaking in your own voice bring support from an unexpected direction.',
            'Today rewards patience and precision over speed. Financial clarity and one constructive conversation lay foundations you will still be standing on next year.',
            'The Moon softens a situation that felt fixed. Something you had written off as settled turns out to still be open, and the opening is yours to take.',
            'Momentum returns after a slow stretch. Say yes to the invitation that arrives late in the day — it leads somewhere the calendar cannot show you yet.',
            'A quiet, productive day with one bright spot in it. Recognition arrives from someone whose opinion you did not realise you valued this much.',
            'Old effort begins to pay. What you built without applause months ago becomes visible today, and the timing is better than it would have been then.',
        ],

        'love' => [
            'Open, unhurried conversation strengthens the bond. Single natives may feel a genuine spark with someone already in their circle.',
            'Show appreciation out loud today. A small, specific gesture lands harder than a grand one and warms the whole week.',
            'A shared plan or a lighthearted outing rekindles the excitement. Speak from the heart and let yourself be a little vulnerable.',
            'Patience and real listening dissolve an old misunderstanding. Emotional honesty is the theme of the evening.',
            'Someone has been waiting for you to make the first move. Make it — the risk is smaller than it looks from here.',
            'Give the relationship your attention rather than your opinion today. Being heard is what your partner is actually asking for.',
            'An old connection resurfaces. Enjoy the warmth without rewriting history; the memory is fonder than the reality was.',
            'Family matters take priority over romance today, and handling them well is itself an act of love.',
        ],

        'career' => [
            'Your leadership shows in group settings. Pitching an idea or volunteering for the harder task earns visible appreciation.',
            'A productive day for finalising paperwork, closing pending items and organising the quarter ahead.',
            'Gains arrive from an unexpected avenue. Keep the budget tight and focus on steady execution rather than a gamble.',
            'Teamwork produces the best result today. Stay open to feedback from someone who has already walked the path.',
            'Say no to one request today. Protecting the hours around your real work is what makes the real work good.',
            'A senior colleague notices something you assumed had gone unrecorded. Keep doing it the careful way.',
            'Negotiation goes your way if you speak second. Let the other side name a number before you name yours.',
            'A skill you learned for an old role turns out to be exactly what today needs. Volunteer it.',
        ],

        'health' => [
            'Energy runs high, but sleep is where it is repaid — protect your bedtime tonight.',
            'Hydration and a short walk outdoors do more for your focus today than another coffee.',
            'Watch your posture and your screen hours. Ten minutes of stretching resets the whole afternoon.',
            'A light, home-cooked meal and an early evening leave you sharper than the day promised.',
            'Your body is asking for movement rather than rest today. Even fifteen minutes changes the mood.',
            'Stress is showing up somewhere physical. Name it early and it stays small.',
            'Eat at regular hours today. Most of the afternoon slump you are expecting is a skipped lunch.',
            'Rest is productive today, not lazy. Take the break before you need it rather than after.',
        ],

        'money' => [
            'Review one recurring expense today — a single quiet cancellation pays for itself all year.',
            'A pending payment or refund is likely to move. Avoid lending large sums this week.',
            'Good day to plan a long-term investment; poor day for an impulse purchase you have not slept on.',
            'Money follows organisation today. An hour with your accounts brings unexpected relief.',
            'A small extra income stream is worth taking seriously today, even if the first amount is trivial.',
            'Compare before you commit. The first quote you were given is not the best one available.',
            'Household spending needs a decision rather than a discussion. Make it and move on.',
            'Save the unexpected money instead of spending it. There is a use for it later this month.',
        ],

        'mantras' => [
            'Do the difficult thing first — the rest of the day follows it.',
            'Say less, mean more.',
            'Progress today, perfection never.',
            'Protect your energy like it is your most valuable asset, because it is.',
            'Trust the instinct you had before you started overthinking.',
            'Begin badly rather than not at all.',
            'The calm answer is the strong one.',
            'What you tolerate, you teach.',
        ],

        'colors' => [
            'Crimson Red',
            'Royal Blue',
            'Emerald Green',
            'Golden Yellow',
            'Mystic Violet',
            'Sunset Coral',
            'Deep Navy',
            'Pure Pearl',
        ],

        'moods' => [
            'Empowered & Clear',
            'Optimistic & Radiant',
            'Focused & Grounded',
            'Creative & Inspired',
            'Harmonious & Peaceful',
        ],

        'directions' => [
            'North',
            'North-East',
            'East',
            'South-East',
            'South',
            'South-West',
            'West',
            'North-West',
        ],

    ],

    /*
    | Questions readers actually type into Google, answered on the page and
    | mirrored into FAQPage structured data.
    |
    | Both halves matter: Google drops an FAQ rich result whose answer a reader
    | cannot find on the page, so these are rendered as well as marked up.
    */
    'faqs' => [
        [
            'question' => 'What is a daily horoscope?',
            'answer' => 'A daily horoscope is a short astrological forecast for one of the 12 zodiac signs, based on where the Sun, Moon and planets sit on that date. It covers the mood of the day along with guidance on love, career, money and health.',
        ],
        [
            'question' => 'How do I know which zodiac sign I am?',
            'answer' => 'Your sun sign comes from your date of birth: Aries Mar 21 – Apr 19, Taurus Apr 20 – May 20, Gemini May 21 – Jun 20, Cancer Jun 21 – Jul 22, Leo Jul 23 – Aug 22, Virgo Aug 23 – Sep 22, Libra Sep 23 – Oct 22, Scorpio Oct 23 – Nov 21, Sagittarius Nov 22 – Dec 21, Capricorn Dec 22 – Jan 19, Aquarius Jan 20 – Feb 18 and Pisces Feb 19 – Mar 20. You can also enter your birth date in the finder on this page.',
        ],
        [
            'question' => 'What is the difference between a horoscope and a rashifal?',
            'answer' => 'They describe the same thing in two traditions. Rashifal is the Vedic reading, usually taken from the moon sign or rashi, while the Western horoscope is taken from the sun sign. This page carries both names for every sign, so Mesh and Aries appear together.',
        ],
        [
            'question' => 'When is the horoscope on this page updated?',
            'answer' => 'Every reading refreshes at midnight, so the forecast on screen always belongs to the current date. For a given sign the prediction then stays the same for the whole day.',
        ],
        [
            'question' => 'What do the lucky number and lucky colour mean?',
            'answer' => 'They are the number and shade considered most favourable for your sign on that particular day. Many readers treat them as a small daily ritual — wearing the colour, or choosing it for an important meeting.',
        ],
        [
            'question' => 'Which zodiac signs are most compatible?',
            'answer' => 'Signs of the same element usually understand each other instantly, and Fire pairs naturally with Air while Earth pairs with Water. Use the love compatibility calculator on this site to check the match score for any two signs.',
        ],
        [
            'question' => 'Is this horoscope available in Hindi?',
            'answer' => 'Yes. Every reading on this page is also published in Hindi as a full rashifal, with its own page for each of the 12 rashis. Use the language link at the top of the page to switch.',
        ],
    ],

    /*
    | The FAQ block on an individual sign's page.
    |
    | One set of templates, filled with that sign's own facts. The questions
    | are the ones that carry search volume per sign - dates, ruling planet,
    | stone, best match - and because every answer is built from :dates,
    | :planet, :gemstone and :matches, the twelve pages end up answering twelve
    | different questions rather than repeating one block with a name swapped.
    |
    | Available placeholders: :name :other :dates :element :quality :planet
    | :gemstone :day :symbol :matches :traits
    */
    'sign_faqs' => [
        [
            'question' => 'What are the :name dates?',
            'answer' => 'Anyone born between :dates is a :name. If your birthday falls within a day of either end, check the year you were born — the cusp moves slightly, and the finder on this page will settle it.',
        ],
        [
            'question' => 'Which planet rules :name?',
            'answer' => ':name is ruled by :planet, and that is the placement the whole sign is read from. It is also why :name is a :element sign of the :quality kind — steady in some things, restless in others, in the particular mix :planet produces.',
        ],
        [
            'question' => 'Which signs are the best match for :name?',
            'answer' => ':name matches most naturally with :matches. Those pairings share or complement :name\'s :element energy, which is what removes most of the translating a couple otherwise has to do. Any other pairing can still work — it simply asks for more of it.',
        ],
        [
            'question' => 'What is the lucky stone and lucky day for :name?',
            'answer' => 'The stone traditionally associated with :name is :gemstone, and the most favourable day of the week is :day. Many readers wear the stone or schedule anything important for that day; treat it as a small ritual rather than a rule.',
        ],
        [
            'question' => 'What is the :name personality like?',
            'answer' => ':name natives are usually described as :traits. The sign\'s symbol is :symbol, which is closer to the truth than it sounds — the traits that make :name easy to recognise are the same ones the symbol has always stood for.',
        ],
        [
            'question' => 'How often is the :name horoscope updated?',
            'answer' => 'The daily :name reading refreshes at midnight, the weekly one every Monday and the monthly one on the 1st. All three are on this page, so the forecast you are reading always belongs to the period it names.',
        ],
        [
            'question' => 'What are the name starting letters for :name?',
            'answer' => 'The name starting letters (Rashi Akshar) traditionally associated with :name are :letters.',
        ],
    ],

    /*
    | Titles, descriptions and keywords.
    |
    | These are the strings that decide whether a page is clicked, so they are
    | written per language rather than translated: the Hindi title leads with
    | "आज का राशिफल" because that is the query, not because it is what the
    | English title says.
    |
    | Titles stay under ~60 characters once :date is filled, which is where
    | Google truncates. Descriptions stay under ~155.
    */
    'seo' => [

        'hub_title' => 'Daily Horoscope Today (:date) – All 12 Zodiac Signs',
        'hub_description' => "Free daily horoscope for :longdate: today's prediction for all 12 zodiac signs, from Aries to Pisces, with lucky number, colour, love and career.",
        'hub_keywords' => 'daily horoscope, horoscope today, aaj ka rashifal, rashifal, zodiac signs, astrology prediction, lucky number today, love compatibility, aries, taurus, gemini, cancer, leo, virgo, libra, scorpio, sagittarius, capricorn, aquarius, pisces',
        'hub_heading' => 'Daily Horoscope Today',
        'hub_intro' => 'Your free prediction for all 12 zodiac signs, rewritten every morning — love, career, money and health, plus the lucky number and colour for the day.',

        'sign_title' => ':name Horoscope Today (:date) – Love, Career, Lucky Number',
        'sign_description' => ':name horoscope for :longdate. Today\'s prediction for :name (:dates) covering love, career, money and health, with your lucky number, colour and time — plus this week and this month.',
        'sign_keywords' => ':lowername horoscope, :lowername horoscope today, :lowername daily horoscope, :lowername rashifal, :lowername love life, :lowername career, :lowername lucky number, :lowername compatibility, zodiac :lowername',
        'sign_heading' => ':name Horoscope Today',

        'compat_title' => 'Zodiac Love Compatibility Calculator – All 12 Signs',
        'compat_pair_title' => ':s1 and :s2 Compatibility: :score% Love Match',
        'compat_description' => 'Free zodiac compatibility calculator for all 144 sign pairs. Check love, friendship and communication scores for any two signs, plus the advice each needs.',
        'compat_pair_description' => 'How well do :s1 and :s2 match? :score% overall — love :love%, friendship :friendship%, communication :communication% — plus the friction to expect.',
        'compat_keywords' => 'zodiac compatibility, love compatibility calculator, zodiac love match, rashi match, astrology compatibility, star sign compatibility',
        'compat_pair_keywords' => ':lows1 and :lows2 compatibility, :lows1 :lows2 love match, zodiac compatibility, rashi match, astrology love calculator',

        'breadcrumb_home' => 'Home',
        'breadcrumb_horoscope' => 'Horoscope',
        'breadcrumb_compatibility' => 'Love Compatibility',

    ],

    /*
    | Interface copy. Everything a template prints that is not a prediction.
    */
    'ui' => [

        'today' => 'Today',
        'this_week' => 'This week',
        'this_month' => 'This month',
        'daily' => 'Daily',
        'weekly' => 'Weekly',
        'monthly' => 'Monthly',

        'overview' => 'Overview',
        'love' => 'Love & Relationships',
        'career' => 'Career & Work',
        'health' => 'Health & Wellbeing',
        'money' => 'Money & Finance',
        'mantra' => 'Mantra for the day',

        'lucky_number' => 'Lucky number',
        'lucky_color' => 'Lucky colour',
        'lucky_time' => 'Lucky time',
        'lucky_direction' => 'Lucky direction',
        'mood' => 'Mood',
        'score' => 'Day score',

        'about_sign' => 'About :name',
        'sign_profile' => ':name at a glance',
        'symbol' => 'Symbol',
        'element' => 'Element',
        'quality' => 'Quality',
        'ruling_planet' => 'Ruling planet',
        'gemstone' => 'Lucky gemstone',
        'lucky_day' => 'Lucky day',
        'rashi_letters' => 'Name letters',
        'date_range' => 'Dates',
        'strengths' => 'Strengths',
        'weaknesses' => 'Weaknesses',

        'compatibility_heading' => ':name compatibility with every sign',
        'compatibility_intro' => 'How :name matches with each of the other eleven signs. Tap any pairing for the full reading.',
        'best_match' => 'Best match',
        'view_match' => 'View match',
        'open_calculator' => 'Open the compatibility calculator',

        'all_signs' => 'All 12 signs',
        'other_signs' => 'Other zodiac signs',
        'back_to_all' => 'All horoscopes',
        'read_full' => 'Read full horoscope',
        'choose_sign' => 'Choose your sign',
        'find_your_sign' => 'Find your sign',
        'birthday_prompt' => 'Enter your date of birth',

        'faq_heading' => 'Frequently asked questions',
        'sign_faq_heading' => ':name — frequently asked questions',

        'language_label' => 'Language',
        'read_in_hindi' => 'हिन्दी में पढ़ें',
        'read_in_english' => 'Read in English',

        'updated_at' => 'Updated :date',
        'week_of' => 'Week of :from – :to',
        'month_of' => ':month',

        'disclaimer' => 'Horoscopes are written for entertainment and reflection. They are not a substitute for medical, legal or financial advice.',

        /*
        | The hub page. Short forms of the four life areas live here too: the
        | hub prints twelve cards, and "Love & Relationships" twelve times over
        | crowds out the reading it is labelling.
        */
        'love_short' => 'Love',
        'career_short' => 'Career',
        'money_short' => 'Money',
        'health_short' => 'Health',

        'hub_eyebrow' => 'Daily Horoscope & Rashifal',
        // The headline is split across two lines so the second can carry the
        // gradient. Both halves are translated; a single string would put the
        // colour on whatever words happened to land second.
        'hub_h1' => 'Daily Horoscope & Rashifal',
        'hub_h1_accent' => 'for all 12 zodiac signs',
        'updated_label' => 'Updated',
        'hub_hero_intro' => "Today's reading for every sign — from Aries to Pisces — with the mood of the day, your lucky number and colour, and what the stars say about love, career, money and health. Free, and rewritten every midnight.",
        'read_my_sign' => 'Read my sign',
        // The name is styled separately from the words after it, so this is
        // the suffix alone. Both languages happen to put the sign name first.
        'horoscope_today_suffix' => 'Horoscope Today',

        /*
        | Strings the Vue islands print.
        |
        | They are passed in as a prop rather than looked up in JavaScript: the
        | translations live in PHP, the island is rendered by the browser, and
        | shipping a second copy of the dictionary to every visitor to avoid
        | one prop would be the wrong trade.
        */
        'all_12' => 'All 12',

        /*
        | Sharing.
        |
        | Double-quoted so the \n are real newlines: WhatsApp renders them, and
        | a reading arriving as one unbroken paragraph is one nobody forwards.
        | The URL is appended by the share bar, so it is not in the template.
        */
        'share_heading' => 'Share this reading',
        'share_message' => "🔮 *:name Horoscope Today*\n\n✨ :overview\n\n🔢 Lucky number: *:number*\n🎨 Lucky colour: *:color*\n💫 Energy: *:score%*\n\n👉 Read the full prediction:",
        'find_sign_heading' => 'Find your sign in one tap',
        'find_sign_intro' => 'Enter your date of birth, or pick a sign from the wheel below.',
        'find_my_sign' => 'Find my sign',
        'pick_another_sign' => 'Pick another sign',
        'your_sign' => 'Your sign',
        'their_sign' => 'Their sign',
        'full_breakdown' => 'Full breakdown',

        'stat_signs' => 'Signs',
        'stat_refreshed' => 'Refreshed',
        'stat_cost' => 'Cost',
        'stat_daily' => 'Daily',
        'stat_free' => 'Free',

        'jump_to' => 'Jump to',
        'picker_heading' => 'Find and read your zodiac sign',
        'picker_fallback' => "Choose your sign from the list below to read today's forecast.",

        'signs_heading' => "Today's horoscope, sign by sign",
        'signs_intro' => 'Every reading below is written for :date and covers love, career, money and health, along with the lucky details for the day.',
        'signs_badge' => '12 signs · updated daily',

        'mood_today' => 'Mood today',
        'energy' => 'energy',
        'strengths_label' => 'Strengths:',
        'watch_out' => 'Watch out for:',
        'best_matches' => 'Best matches',

        'elements_heading' => 'The four elements, and why they decide compatibility',
        'elements_intro' => 'Each of the 12 zodiac signs belongs to one of four elements. The element sets the temperature of a sign — how it loves, argues, spends and recovers — and it is the first thing an astrologer looks at when checking whether two people will get along.',
        'element_signs' => ':element signs',

        'compat_cta_heading' => 'How well do your two signs actually match?',
        'compat_cta_intro' => "Pick your sign and your partner's to get a match score with love chemistry, friendship, communication and the friction to expect.",
        'compat_cta_button' => 'Open the calculator',

        'hub_faq_heading' => 'Horoscope questions, answered',
        'trending_heading' => 'Trending on the site right now',

        'compat_love' => 'Love & romance',
        'compat_friendship' => 'Friendship',
        'compat_communication' => 'Communication',
        'compat_trust' => 'Trust',
        'compat_values' => 'Shared values',
        'compat_chart_heading' => 'Zodiac compatibility chart: all 144 pairs',
        'compat_chart_intro' => "Read your sign down the left and your partner's across the top. Every score links to the full reading for that pair.",
        'sign_label' => 'Sign',
        'compat_matrix_title' => ':s1 and :s2 compatibility: :score%',
        'compat_read_heading' => 'How zodiac compatibility is read',
        'compat_read_intro' => 'Every sign belongs to one of four elements, and the relationship between those two elements is the first thing an astrologer looks at. It decides the tempo of the relationship — how fast each person moves, how they argue, and how quickly they recover.',
        'popular_pairings' => 'Most-checked pairings',
        'compat_reading_label' => 'Compatibility reading',
        'compat_faq_heading' => 'Compatibility questions, answered',
        'compat_footer_heading' => "Read today's horoscope for both signs",
        'compat_footer_intro' => 'Daily predictions for all 12 signs with lucky number, colour and timing — refreshed every midnight.',
        'compat_footer_button' => 'Open daily horoscope',
        'compat_disclaimer' => 'Compatibility readings on this page are generated for entertainment and general guidance, from sun signs alone. They are not a substitute for professional relationship advice.',
        'compat_eyebrow' => 'Zodiac love match',
        'compat_h1' => 'Zodiac Love Compatibility',
        'compat_h1_accent' => 'Calculator',
        'compat_hero_intro' => 'Pick two signs and get the full reading: an overall match score with separate love, friendship, communication, trust and shared-values numbers — plus what each pairing is actually like to live in. All 144 combinations, free.',
        'compat_check_btn' => 'Check a match',
        'compat_retry_btn' => 'Try another pair',
        'todays_horoscope' => "Today's horoscope",
        'compat_and' => 'and',
        'compat_pair_subtitle_format' => 'compatibility: :score% match',
        'compat_picker_fallback' => 'Pick any two signs from the compatibility table below to read their match.',
        'compat_reading_header' => 'The reading',
        'compat_overall_match' => 'Overall match',
        'compat_what_works' => 'What works',
        'compat_what_to_watch' => 'What to watch',
        'compat_advice_label' => 'Advice',

        'compat_match' => 'Match',
        'compat_friends_short' => 'Friends',
        'compat_talk_short' => 'Talk',
        'compat_preview' => 'You are previewing :s1 & :s2 — open the full reading for the complete breakdown.',
        'compat_open_pair' => 'Open :s1 & :s2',
        'share_whatsapp' => 'Share on WhatsApp',
        'copy_link' => 'Copy link',
        'link_copied' => 'Link copied',

        'energy_label' => 'Energy',
        'lucky_number_label' => 'Lucky number',
        'lucky_color_label' => 'Lucky colour',
        'lucky_time_label' => 'Lucky time',
        'mood_label' => 'Mood',
        'copy_reading' => 'Copy reading',
        'copied' => 'Copied',
        'check_love_match' => 'Check love match',
        'finder_error' => 'That date did not match a sign — pick yours below.',
        'share_horoscope_format' => '🔮 {sign} horoscope for {date}',
        'read_yours' => 'Read yours:',
        'share_check_match' => 'Check your own match:',
    ],

];
