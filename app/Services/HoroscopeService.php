<?php

namespace App\Services;

use Carbon\Carbon;

class HoroscopeService
{
    /**
     * Complete list of all 12 Zodiac signs with meta details.
     *
     * Everything here is evergreen reference copy, rendered server-side into
     * the horoscope page: a crawler reads real astrological content rather
     * than an empty shell waiting for JavaScript.
     *
     * @return array<string, array<string, mixed>>
     */
    public function signs(): array
    {
        return [
            'aries' => [
                'slug' => 'aries',
                'name' => 'Aries',
                'vedic' => 'Mesh (मेष)',
                'symbol' => '♈',
                'symbol_name' => 'The Ram',
                'icon' => 'flame',
                'dates' => 'Mar 21 – Apr 19',
                'range' => ['03-21', '04-19'],
                'element' => 'Fire',
                'quality' => 'Cardinal',
                'planet' => 'Mars',
                'gemstone' => 'Red Coral',
                'lucky_day' => 'Tuesday',
                'traits' => 'Bold, Ambitious, Energetic',
                'strengths' => ['Courageous', 'Determined', 'Honest'],
                'weaknesses' => ['Impatient', 'Short-tempered'],
                'best_matches' => ['leo', 'sagittarius', 'gemini'],
                'about' => 'Aries is the first sign of the zodiac and it shows: ruled by Mars, Aries natives move first and think later, which is exactly why they win the races nobody else dares to start. Their honesty is disarming and their energy is contagious, though patience remains the lesson of a lifetime.',
                'color' => '#ef4444',
                'image' => '/images/zodiac/aries.webp',
            ],
            'taurus' => [
                'slug' => 'taurus',
                'name' => 'Taurus',
                'vedic' => 'Vrishabh (वृषभ)',
                'symbol' => '♉',
                'symbol_name' => 'The Bull',
                'icon' => 'shield',
                'dates' => 'Apr 20 – May 20',
                'range' => ['04-20', '05-20'],
                'element' => 'Earth',
                'quality' => 'Fixed',
                'planet' => 'Venus',
                'gemstone' => 'Diamond',
                'lucky_day' => 'Friday',
                'traits' => 'Reliable, Patient, Practical',
                'strengths' => ['Loyal', 'Patient', 'Grounded'],
                'weaknesses' => ['Stubborn', 'Possessive'],
                'best_matches' => ['virgo', 'capricorn', 'cancer'],
                'about' => 'Venus-ruled Taurus builds slowly and builds to last — in money, in love, and in the comforts they surround themselves with. Their patience looks like stubbornness from the outside, but it is really the confidence of someone who has never needed to rush a good thing.',
                'color' => '#10b981',
                'image' => '/images/zodiac/taurus.webp',
            ],
            'gemini' => [
                'slug' => 'gemini',
                'name' => 'Gemini',
                'vedic' => 'Mithun (मिथुन)',
                'symbol' => '♊',
                'symbol_name' => 'The Twins',
                'icon' => 'sparkles',
                'dates' => 'May 21 – Jun 20',
                'range' => ['05-21', '06-20'],
                'element' => 'Air',
                'quality' => 'Mutable',
                'planet' => 'Mercury',
                'gemstone' => 'Emerald',
                'lucky_day' => 'Wednesday',
                'traits' => 'Curious, Adaptable, Witty',
                'strengths' => ['Quick-witted', 'Sociable', 'Adaptable'],
                'weaknesses' => ['Restless', 'Indecisive'],
                'best_matches' => ['libra', 'aquarius', 'aries'],
                'about' => 'Mercury gives Gemini the fastest mind in the zodiac and a talent for talking their way into — and out of — anything. They collect ideas the way others collect possessions, and boredom, not difficulty, is the only thing that ever defeats them.',
                'color' => '#f59e0b',
                'image' => '/images/zodiac/gemini.webp',
            ],
            'cancer' => [
                'slug' => 'cancer',
                'name' => 'Cancer',
                'vedic' => 'Kark (कर्क)',
                'symbol' => '♋',
                'symbol_name' => 'The Crab',
                'icon' => 'heart',
                'dates' => 'Jun 21 – Jul 22',
                'range' => ['06-21', '07-22'],
                'element' => 'Water',
                'quality' => 'Cardinal',
                'planet' => 'Moon',
                'gemstone' => 'Pearl',
                'lucky_day' => 'Monday',
                'traits' => 'Intuitive, Caring, Protective',
                'strengths' => ['Empathetic', 'Loyal', 'Intuitive'],
                'weaknesses' => ['Moody', 'Over-sensitive'],
                'best_matches' => ['scorpio', 'pisces', 'taurus'],
                'about' => 'Ruled by the Moon, Cancer feels the weather of a room before anyone speaks. Home and chosen family are the axis their whole chart turns on, and the famous hard shell exists only to protect a remarkably soft, remarkably accurate instinct.',
                'color' => '#06b6d4',
                'image' => '/images/zodiac/cancer.webp',
            ],
            'leo' => [
                'slug' => 'leo',
                'name' => 'Leo',
                'vedic' => 'Singh (सिंह)',
                'symbol' => '♌',
                'symbol_name' => 'The Lion',
                'icon' => 'sun',
                'dates' => 'Jul 23 – Aug 22',
                'range' => ['07-23', '08-22'],
                'element' => 'Fire',
                'quality' => 'Fixed',
                'planet' => 'Sun',
                'gemstone' => 'Ruby',
                'lucky_day' => 'Sunday',
                'traits' => 'Confident, Generous, Charismatic',
                'strengths' => ['Warm-hearted', 'Creative', 'Loyal'],
                'weaknesses' => ['Proud', 'Needs applause'],
                'best_matches' => ['aries', 'sagittarius', 'libra'],
                'about' => 'The Sun rules Leo, and like the Sun they are impossible to place anywhere but the centre. Their generosity is genuine and enormous — Leo gives away credit, money and loyalty freely — but withhold appreciation and the lion notices immediately.',
                'color' => '#ea580c',
                'image' => '/images/zodiac/leo.webp',
            ],
            'virgo' => [
                'slug' => 'virgo',
                'name' => 'Virgo',
                'vedic' => 'Kanya (कन्या)',
                'symbol' => '♍',
                'symbol_name' => 'The Maiden',
                'icon' => 'check-circle',
                'dates' => 'Aug 23 – Sep 22',
                'range' => ['08-23', '09-22'],
                'element' => 'Earth',
                'quality' => 'Mutable',
                'planet' => 'Mercury',
                'gemstone' => 'Emerald',
                'lucky_day' => 'Wednesday',
                'traits' => 'Analytical, Helpful, Meticulous',
                'strengths' => ['Precise', 'Reliable', 'Practical'],
                'weaknesses' => ['Over-critical', 'Worrier'],
                'best_matches' => ['taurus', 'capricorn', 'cancer'],
                'about' => 'Virgo is the quiet engine room of the zodiac — the one who notices the detail everyone else scrolled past and fixes it before it becomes a problem. Their criticism, including the constant self-criticism, comes from a sincere wish to make things work properly.',
                'color' => '#84cc16',
                'image' => '/images/zodiac/virgo.webp',
            ],
            'libra' => [
                'slug' => 'libra',
                'name' => 'Libra',
                'vedic' => 'Tula (तुला)',
                'symbol' => '♎',
                'symbol_name' => 'The Scales',
                'icon' => 'scale',
                'dates' => 'Sep 23 – Oct 22',
                'range' => ['09-23', '10-22'],
                'element' => 'Air',
                'quality' => 'Cardinal',
                'planet' => 'Venus',
                'gemstone' => 'Opal',
                'lucky_day' => 'Friday',
                'traits' => 'Charming, Balanced, Diplomatic',
                'strengths' => ['Fair-minded', 'Charming', 'Diplomatic'],
                'weaknesses' => ['Indecisive', 'Conflict-avoidant'],
                'best_matches' => ['gemini', 'aquarius', 'leo'],
                'about' => 'Venus-ruled Libra reads a situation for its balance the way a musician reads a room for its pitch. They are the natural mediators of the zodiac, which is also why choosing for themselves — a restaurant, a career, a person — can take three times longer than it should.',
                'color' => '#3b82f6',
                'image' => '/images/zodiac/libra.webp',
            ],
            'scorpio' => [
                'slug' => 'scorpio',
                'name' => 'Scorpio',
                'vedic' => 'Vrishchik (वृश्चिक)',
                'symbol' => '♏',
                'symbol_name' => 'The Scorpion',
                'icon' => 'zap',
                'dates' => 'Oct 23 – Nov 21',
                'range' => ['10-23', '11-21'],
                'element' => 'Water',
                'quality' => 'Fixed',
                'planet' => 'Pluto & Mars',
                'gemstone' => 'Topaz',
                'lucky_day' => 'Tuesday',
                'traits' => 'Passionate, Resourceful, Magnetic',
                'strengths' => ['Fearless', 'Loyal', 'Perceptive'],
                'weaknesses' => ['Secretive', 'Unforgiving'],
                'best_matches' => ['cancer', 'pisces', 'virgo'],
                'about' => 'Scorpio does nothing by halves. Ruled by Mars and Pluto, they are built for the intense end of every experience — total loyalty, total focus, total transformation — and they can read a motive across a crowded room long before it is admitted out loud.',
                'color' => '#8b5cf6',
                'image' => '/images/zodiac/scorpio.webp',
            ],
            'sagittarius' => [
                'slug' => 'sagittarius',
                'name' => 'Sagittarius',
                'vedic' => 'Dhanu (धनु)',
                'symbol' => '♐',
                'symbol_name' => 'The Archer',
                'icon' => 'compass',
                'dates' => 'Nov 22 – Dec 21',
                'range' => ['11-22', '12-21'],
                'element' => 'Fire',
                'quality' => 'Mutable',
                'planet' => 'Jupiter',
                'gemstone' => 'Yellow Sapphire',
                'lucky_day' => 'Thursday',
                'traits' => 'Optimistic, Adventurous, Honest',
                'strengths' => ['Optimistic', 'Free-spirited', 'Frank'],
                'weaknesses' => ['Blunt', 'Commitment-shy'],
                'best_matches' => ['aries', 'leo', 'aquarius'],
                'about' => 'Jupiter hands Sagittarius luck, appetite and an incurable belief that the next horizon is better than this one. They tell the truth even when tact would serve them better, and their optimism has an odd habit of turning out to be justified.',
                'color' => '#d946ef',
                'image' => '/images/zodiac/sagittarius.webp',
            ],
            'capricorn' => [
                'slug' => 'capricorn',
                'name' => 'Capricorn',
                'vedic' => 'Makar (मकर)',
                'symbol' => '♑',
                'symbol_name' => 'The Goat',
                'icon' => 'mountain',
                'dates' => 'Dec 22 – Jan 19',
                'range' => ['12-22', '01-19'],
                'element' => 'Earth',
                'quality' => 'Cardinal',
                'planet' => 'Saturn',
                'gemstone' => 'Blue Sapphire',
                'lucky_day' => 'Saturday',
                'traits' => 'Disciplined, Strategic, Ambitious',
                'strengths' => ['Disciplined', 'Strategic', 'Responsible'],
                'weaknesses' => ['Workaholic', 'Reserved'],
                'best_matches' => ['taurus', 'virgo', 'pisces'],
                'about' => 'Saturn teaches Capricorn early that nothing worthwhile is quick, and they build accordingly — one deliberate step at a time, up a mountain most people never attempt. The dry humour is real, and so is the softness they show only to the few who get past the gate.',
                'color' => '#64748b',
                'image' => '/images/zodiac/capricorn.webp',
            ],
            'aquarius' => [
                'slug' => 'aquarius',
                'name' => 'Aquarius',
                'vedic' => 'Kumbh (कुंभ)',
                'symbol' => '♒',
                'symbol_name' => 'The Water Bearer',
                'icon' => 'wind',
                'dates' => 'Jan 20 – Feb 18',
                'range' => ['01-20', '02-18'],
                'element' => 'Air',
                'quality' => 'Fixed',
                'planet' => 'Uranus',
                'gemstone' => 'Amethyst',
                'lucky_day' => 'Saturday',
                'traits' => 'Visionary, Independent, Original',
                'strengths' => ['Original', 'Humanitarian', 'Independent'],
                'weaknesses' => ['Detached', 'Contrary'],
                'best_matches' => ['gemini', 'libra', 'sagittarius'],
                'about' => 'Aquarius arrives at the answer from an angle nobody else considered, which is why they are so often ahead of the room and so rarely comfortable inside it. They care deeply about people in general and guard their personal freedom fiercely.',
                'color' => '#0284c7',
                'image' => '/images/zodiac/aquarius.webp',
            ],
            'pisces' => [
                'slug' => 'pisces',
                'name' => 'Pisces',
                'vedic' => 'Meen (मीन)',
                'symbol' => '♓',
                'symbol_name' => 'The Fish',
                'icon' => 'droplet',
                'dates' => 'Feb 19 – Mar 20',
                'range' => ['02-19', '03-20'],
                'element' => 'Water',
                'quality' => 'Mutable',
                'planet' => 'Neptune',
                'gemstone' => 'Aquamarine',
                'lucky_day' => 'Thursday',
                'traits' => 'Empathetic, Creative, Mystical',
                'strengths' => ['Compassionate', 'Imaginative', 'Gentle'],
                'weaknesses' => ['Escapist', 'Over-trusting'],
                'best_matches' => ['cancer', 'scorpio', 'capricorn'],
                'about' => 'The last sign of the zodiac carries a little of all the others, which is why Pisces absorbs the mood of everyone around them. Neptune gives them the imagination of an artist and the boundaries of a sponge — protecting their own energy is the single skill that changes their life.',
                'color' => '#ec4899',
                'image' => '/images/zodiac/pisces.webp',
            ],
        ];
    }

    /**
     * The four elements, used by the filter pills and the explainer section.
     *
     * @return array<string, array<string, mixed>>
     */
    public function elements(): array
    {
        return [
            'Fire' => [
                'name' => 'Fire',
                'icon' => '🔥',
                'signs' => ['aries', 'leo', 'sagittarius'],
                'traits' => 'Passionate, spontaneous, led by instinct and appetite.',
                'pairs' => 'Happiest with Fire and Air signs.',
                'accent' => 'text-orange-600 dark:text-orange-400',
                'ring' => 'border-orange-500/30',
                'tint' => 'bg-orange-50 dark:bg-orange-500/10',
            ],
            'Earth' => [
                'name' => 'Earth',
                'icon' => '🌿',
                'signs' => ['taurus', 'virgo', 'capricorn'],
                'traits' => 'Grounded, patient, loyal to whatever they build.',
                'pairs' => 'Happiest with Earth and Water signs.',
                'accent' => 'text-emerald-600 dark:text-emerald-400',
                'ring' => 'border-emerald-500/30',
                'tint' => 'bg-emerald-50 dark:bg-emerald-500/10',
            ],
            'Air' => [
                'name' => 'Air',
                'icon' => '💨',
                'signs' => ['gemini', 'libra', 'aquarius'],
                'traits' => 'Curious, communicative, driven by ideas.',
                'pairs' => 'Happiest with Air and Fire signs.',
                'accent' => 'text-sky-600 dark:text-sky-400',
                'ring' => 'border-sky-500/30',
                'tint' => 'bg-sky-50 dark:bg-sky-500/10',
            ],
            'Water' => [
                'name' => 'Water',
                'icon' => '💧',
                'signs' => ['cancer', 'scorpio', 'pisces'],
                'traits' => 'Intuitive, emotional, devoted well past reason.',
                'pairs' => 'Happiest with Water and Earth signs.',
                'accent' => 'text-indigo-600 dark:text-indigo-400',
                'ring' => 'border-indigo-500/30',
                'tint' => 'bg-indigo-50 dark:bg-indigo-500/10',
            ],
        ];
    }

    /**
     * Generate daily horoscope consistently based on the date.
     *
     * Every value is drawn from a seed built out of the sign and the date, so
     * one reader sees the same reading all day and two readers on the same day
     * see the same page - which is also what makes the response cacheable.
     *
     * @return array<string, mixed>
     */
    public function daily(string $slug, ?Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();
        $signs = $this->signs();
        $sign = $signs[$slug] ?? $signs['aries'];

        // Seed deterministically based on date and sign so daily predictions remain stable all day
        $seed = crc32($sign['slug'].$date->toDateString());
        mt_srand($seed);

        $overviews = [
            "Cosmic planetary alignments favour decisive action today. Your sharp intuition and creative clarity open doors that looked shut yesterday.",
            "A harmonious solar vibration surrounds you today. Collaboration and plain speaking resolve a bottleneck that has been draining you for weeks.",
            "High vitality and fresh motivation define your day. Channel it into the one priority you keep postponing and expect a breakthrough by evening.",
            "The stars highlight transformation and long-range planning. Trust your own compass over the loudest voice in the room when the decision arrives.",
            "A burst of inspiration sparks an idea worth writing down. Networking and speaking in your own voice bring support from an unexpected direction.",
            "Today rewards patience and precision over speed. Financial clarity and one constructive conversation lay foundations you will still be standing on next year.",
        ];

        $loveTips = [
            "Open, unhurried conversation strengthens the bond. Single natives may feel a genuine spark with someone already in their circle.",
            "Show appreciation out loud today. A small, specific gesture lands harder than a grand one and warms the whole week.",
            "A shared plan or a lighthearted outing rekindles the excitement. Speak from the heart and let yourself be a little vulnerable.",
            "Patience and real listening dissolve an old misunderstanding. Emotional honesty is the theme of the evening.",
        ];

        $careerTips = [
            "Your leadership shows in group settings. Pitching an idea or volunteering for the harder task earns visible appreciation.",
            "A productive day for finalising paperwork, closing pending items and organising the quarter ahead.",
            "Gains arrive from an unexpected avenue. Keep the budget tight and focus on steady execution rather than a gamble.",
            "Teamwork produces the best result today. Stay open to feedback from someone who has already walked the path.",
        ];

        $healthTips = [
            "Energy runs high, but sleep is where it is repaid — protect your bedtime tonight.",
            "Hydration and a short walk outdoors do more for your focus today than another coffee.",
            "Watch your posture and your screen hours. Ten minutes of stretching resets the whole afternoon.",
            "A light, home-cooked meal and an early evening leave you sharper than the day promised.",
        ];

        $moneyTips = [
            "Review one recurring expense today — a single quiet cancellation pays for itself all year.",
            "A pending payment or refund is likely to move. Avoid lending large sums this week.",
            "Good day to plan a long-term investment; poor day for an impulse purchase you have not slept on.",
            "Money follows organisation today. An hour with your accounts brings unexpected relief.",
        ];

        $mantras = [
            "Do the difficult thing first — the rest of the day follows it.",
            "Say less, mean more.",
            "Progress today, perfection never.",
            "Protect your energy like it is your most valuable asset, because it is.",
            "Trust the instinct you had before you started overthinking.",
        ];

        $colors = ['Crimson Red', 'Royal Blue', 'Emerald Green', 'Golden Yellow', 'Mystic Violet', 'Sunset Coral', 'Deep Navy', 'Pure Pearl'];
        $moods = ['Empowered & Clear', 'Optimistic & Radiant', 'Focused & Grounded', 'Creative & Inspired', 'Harmonious & Peaceful'];
        $directions = ['North', 'North-East', 'East', 'South-East', 'South', 'South-West', 'West', 'North-West'];

        $luckyNumber = mt_rand(1, 99);
        $luckyColor = $colors[mt_rand(0, count($colors) - 1)];
        $overview = $overviews[mt_rand(0, count($overviews) - 1)];
        $love = $loveTips[mt_rand(0, count($loveTips) - 1)];
        $career = $careerTips[mt_rand(0, count($careerTips) - 1)];
        $health = $healthTips[mt_rand(0, count($healthTips) - 1)];
        $money = $moneyTips[mt_rand(0, count($moneyTips) - 1)];
        $mantra = $mantras[mt_rand(0, count($mantras) - 1)];
        $mood = $moods[mt_rand(0, count($moods) - 1)];
        $direction = $directions[mt_rand(0, count($directions) - 1)];
        $score = mt_rand(82, 98);
        $scores = [
            'love' => mt_rand(68, 97),
            'career' => mt_rand(68, 97),
            'health' => mt_rand(68, 97),
            'money' => mt_rand(68, 97),
        ];

        // Drawn from the seeded sequence, not after it. A value generated once
        // the seed has been reset would change on every request, and the
        // reading would stop matching itself from one refresh to the next.
        $luckyTime = sprintf('%d:00 AM – %d:00 PM', mt_rand(8, 11), mt_rand(1, 4));

        // Reset random seed
        mt_srand();

        return [
            'sign' => $sign,
            'date' => $date->format('F j, Y'),
            'date_iso' => $date->toDateString(),
            'overview' => $overview,
            'love' => $love,
            'career' => $career,
            'health' => $health,
            'money' => $money,
            'mantra' => $mantra,
            'lucky_number' => $luckyNumber,
            'lucky_color' => $luckyColor,
            'lucky_time' => $luckyTime,
            'lucky_direction' => $direction,
            'mood' => $mood,
            'score' => $score,
            'scores' => $scores,
        ];
    }

    /**
     * Questions readers actually search for. Rendered on the page and mirrored
     * into FAQPage structured data, so the same answer can win the rich result.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    public function faqs(): array
    {
        return [
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
        ];
    }

    /**
     * FAQ block for the compatibility page, rendered and mirrored into
     * FAQPage data the same way the horoscope one is.
     *
     * @return array<int, array{question: string, answer: string}>
     */
    public function compatibilityFaqs(): array
    {
        return [
            [
                'question' => 'How is zodiac love compatibility calculated?',
                'answer' => 'The match is read from the two elements involved. Signs sharing an element move at the same speed, Fire pairs with Air and Earth pairs with Water for natural balance, and the remaining combinations need more translation. The calculator turns that into an overall score plus separate love, friendship, communication, trust and shared-values readings.',
            ],
            [
                'question' => 'Which zodiac signs are the best match for each other?',
                'answer' => 'Same-element pairs score highest — Aries with Leo or Sagittarius, Cancer with Scorpio or Pisces, Taurus with Virgo or Capricorn, Gemini with Libra or Aquarius. Complementary pairings such as Fire with Air, or Earth with Water, score almost as well and are usually the more interesting relationship.',
            ],
            [
                'question' => 'Can two incompatible zodiac signs have a lasting relationship?',
                'answer' => 'Yes. A low elemental score means the pairing needs more explaining, not that it is doomed. Couples who do that work often end up more durable than an easy match, because nothing between them was ever assumed.',
            ],
            [
                'question' => 'Does the order of the two signs change the result?',
                'answer' => 'No. Compatibility here is symmetric, so Aries and Leo returns exactly the same score as Leo and Aries.',
            ],
            [
                'question' => 'Should I use my sun sign or my moon sign?',
                'answer' => 'This calculator reads sun signs, which is what most people know their sign as. In Vedic astrology the same comparison is usually made from the moon sign or rashi, and a full synastry reading weighs the whole chart rather than one placement.',
            ],
        ];
    }

    /**
     * The four kinds of pairing, and the numbers each one scores.
     *
     * Kept as data rather than as branches inside compatibility() because the
     * calculator island needs the identical table: the score printed in the
     * page title has to be the score the reader sees after changing a select,
     * and two copies of the same rules drift apart the first time one is
     * edited. Templates use {s1}/{s2} for the sign names and {e1}/{e2} for
     * their elements.
     *
     * @return array<string, array<string, mixed>>
     */
    public function compatibilityTypes(): array
    {
        return [
            'twin' => [
                'key' => 'twin',
                'title' => 'Mirror Souls',
                'score' => 88,
                'scores' => ['love' => 91, 'friendship' => 93, 'communication' => 90, 'trust' => 86, 'values' => 95],
                'summary' => 'Two {s1} natives share one set of instincts. Nobody needs anything explained twice, and nobody is fooled by the other for very long either.',
                'detail' => 'A same-sign pairing is the easiest one to fall into and the hardest one to hide in. You recognise your own strengths in each other instantly — and your own blind spots just as fast, which is where the friction starts. When it works, it works because both of you decided to grow in the same direction at the same time.',
                'strengths' => ['Instant understanding', 'Shared pace and priorities', 'No pretending required'],
                'challenges' => ['The same blind spot, twice', 'Stubbornness with no counterweight'],
                'advice' => 'Give each other room. Two people with identical instincts need separate interests far more than they need another thing in common.',
            ],
            'element' => [
                'key' => 'element',
                'title' => 'Cosmic Synergy',
                'score' => 94,
                'scores' => ['love' => 97, 'friendship' => 99, 'communication' => 95, 'trust' => 93, 'values' => 96],
                'summary' => '{s1} and {s2} both run on {e1} energy, so the tempo of the relationship never needs negotiating.',
                'detail' => 'Same-element pairs are the classic easy match in astrology. You want the same amount of noise, the same amount of rest and the same amount of risk, which removes most of what couples actually argue about. The work here is keeping it from getting comfortable enough to stop being interesting.',
                'strengths' => ['Effortless emotional rhythm', 'Same appetite for risk', 'Shared long-term goals'],
                'challenges' => ['Comfort tipping into routine', 'Nobody plays devil’s advocate'],
                'advice' => 'Keep introducing something new from outside the two of you — this pairing coasts beautifully and can coast for years.',
            ],
            'complementary' => [
                'key' => 'complementary',
                'title' => 'Magnetic Attraction',
                'score' => 92,
                'scores' => ['love' => 95, 'friendship' => 97, 'communication' => 93, 'trust' => 90, 'values' => 88],
                'summary' => '{e1} and {e2} feed each other rather than compete. {s1} and {s2} end up balanced without either of them working at it.',
                'detail' => 'Complementary elements are the pairing astrologers point to when they talk about chemistry that lasts past the first year. One of you supplies momentum, the other supplies direction, and each keeps the other from overdoing what they do naturally. The attraction is immediate and, unusually, it survives familiarity.',
                'strengths' => ['Natural balance of energy', 'Each covers the other’s gap', 'Chemistry that outlasts the honeymoon'],
                'challenges' => ['Different definitions of “urgent”', 'One partner setting the pace by default'],
                'advice' => 'Name what each of you brings out loud. This match runs on an exchange, and it stalls when one side quietly starts doing all the giving.',
            ],
            'contrast' => [
                'key' => 'contrast',
                'title' => 'Opposites Grow',
                'score' => 78,
                'scores' => ['love' => 81, 'friendship' => 83, 'communication' => 74, 'trust' => 79, 'values' => 72],
                'summary' => '{e1} and {e2} want different things from the same day. {s1} and {s2} fascinate each other precisely because neither is obvious to the other.',
                'detail' => 'This is the pairing that takes translation. {e1} and {e2} process the same event at different speeds and measure success by different yardsticks, so misunderstandings arrive early and often. Couples who do the translating anyway tend to end up with the most durable version of a relationship, because nothing about it was ever assumed.',
                'strengths' => ['Genuine fascination', 'Each learns a skill the other has', 'Nothing taken for granted'],
                'challenges' => ['Different emotional vocabulary', 'Small misreadings compounding'],
                'advice' => 'Ask instead of assuming. Almost every fight in this pairing is a translation error rather than a difference in feeling.',
            ],
        ];
    }

    /**
     * Which of the four pairing types two signs fall into.
     */
    public function compatibilityType(string $element1, string $element2, bool $sameSign): string
    {
        if ($sameSign) {
            return 'twin';
        }

        if ($element1 === $element2) {
            return 'element';
        }

        $complementary = ($element1 === 'Fire' && $element2 === 'Air') || ($element1 === 'Air' && $element2 === 'Fire')
            || ($element1 === 'Earth' && $element2 === 'Water') || ($element1 === 'Water' && $element2 === 'Earth');

        return $complementary ? 'complementary' : 'contrast';
    }

    /**
     * Compute compatibility between two signs.
     *
     * @return array<string, mixed>
     */
    public function compatibility(string $slug1, string $slug2): array
    {
        $signs = $this->signs();
        $s1 = $signs[$slug1] ?? $signs['aries'];
        $s2 = $signs[$slug2] ?? $signs['leo'];

        $type = $this->compatibilityType($s1['element'], $s2['element'], $s1['slug'] === $s2['slug']);
        $rule = $this->compatibilityTypes()[$type];

        $fill = fn (string $text): string => strtr($text, [
            '{s1}' => $s1['name'],
            '{s2}' => $s2['name'],
            '{e1}' => $s1['element'],
            '{e2}' => $s2['element'],
        ]);

        return [
            'sign1' => $s1,
            'sign2' => $s2,
            'type' => $type,
            'score' => $rule['score'],
            'title' => $rule['title'],
            'summary' => $fill($rule['summary']),
            'detail' => $fill($rule['detail']),
            'strengths' => $rule['strengths'],
            'challenges' => $rule['challenges'],
            'advice' => $rule['advice'],
            'scores' => $rule['scores'],
            // Kept flat as well: the older callers and the share text read these.
            'love' => $rule['scores']['love'],
            'friendship' => $rule['scores']['friendship'],
        ];
    }

    /**
     * Score for every pair of signs, for the compatibility table.
     *
     * @return array<string, array<string, int>>
     */
    public function compatibilityMatrix(): array
    {
        $types = $this->compatibilityTypes();
        $signs = $this->signs();
        $matrix = [];

        foreach ($signs as $slug1 => $first) {
            foreach ($signs as $slug2 => $second) {
                $type = $this->compatibilityType($first['element'], $second['element'], $slug1 === $slug2);
                $matrix[$slug1][$slug2] = $types[$type]['score'];
            }
        }

        return $matrix;
    }
}
