<?php

/*
|--------------------------------------------------------------------------
| Zodiac compatibility copy - English
|--------------------------------------------------------------------------
|
| The four kinds of pairing and the numbers each one scores.
|
| Kept as data rather than as branches inside compatibility() because the
| calculator island needs the identical table: the score printed in the page
| title has to be the score the reader sees after changing a select, and two
| copies of the same rules drift apart the first time one is edited.
|
| Templates use {s1}/{s2} for the sign names and {e1}/{e2} for their elements.
| Both the PHP side and the Vue island substitute them, so a translated string
| must keep every placeholder it was given.
|
*/

return [

    'types' => [

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

    ],

    'faqs' => [
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
    ],

];
