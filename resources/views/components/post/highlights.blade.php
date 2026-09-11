@props(['post', 'accent' => null])

@php
    $accentColor = $accent ?: ($post->category?->color ?: '#ef4444');

    // Extract bullet highlights from post excerpt or initial sentences
    $highlights = [];
    if (!empty($post->excerpt)) {
        // Split by period, semicolon or bullet points
        $sentences = preg_split('/(?<=[.!?])\s+/', trim($post->excerpt), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($sentences as $s) {
            $cleaned = trim(strip_tags($s));
            if (mb_strlen($cleaned) > 15) {
                $highlights[] = $cleaned;
            }
        }
    }

    // If less than 2 points, extract from post content paragraphs
    if (count($highlights) < 2 && !empty($post->content)) {
        if (preg_match_all('/<p>(.*?)<\/p>/is', $post->content, $pMatches)) {
            foreach ($pMatches[1] as $pText) {
                $plain = trim(strip_tags($pText));
                if (mb_strlen($plain) > 35 && mb_strlen($plain) < 220) {
                    $highlights[] = $plain;
                }
                if (count($highlights) >= 3) {
                    break;
                }
            }
        }
    }

    $highlights = array_slice($highlights, 0, 3);
@endphp

@if(count($highlights) > 0)
    <div class="my-6 overflow-hidden rounded-2xl border border-gray-200/80 bg-gradient-to-br from-gray-50/80 via-white to-gray-50/50 p-5 shadow-xs transition-all dark:border-gray-800/80 dark:from-gray-900/60 dark:via-gray-900/30 dark:to-gray-900/60">
        <div class="flex items-center gap-2.5 pb-3">
            <span class="grid size-7 place-items-center rounded-lg text-white shadow-xs" style="background-color: {{ $accentColor }};">
                <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                </svg>
            </span>
            <h3 class="text-sm font-black uppercase tracking-wider text-gray-900 dark:text-white">
                Key Takeaways / मुख्य बातें
            </h3>
        </div>

        <ul class="mt-1 space-y-2.5 text-sm font-medium leading-relaxed text-gray-700 dark:text-gray-300">
            @foreach($highlights as $item)
                <li class="flex items-start gap-2.5">
                    <span class="mt-1.5 size-1.5 shrink-0 rounded-full" style="background-color: {{ $accentColor }};"></span>
                    <span>{{ $item }}</span>
                </li>
            @endforeach
        </ul>
    </div>
@endif
