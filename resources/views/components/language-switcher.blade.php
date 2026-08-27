@props(['links' => [], 'tone' => 'dark'])

{{--
    Links to this exact page in every language the site publishes.

    Real anchors, not a select that navigates on change: a crawler follows an
    anchor and will not run the change handler, and these are the links that
    tell it the Hindi page exists at all. They also carry hreflang, which makes
    each one agree with the <link rel="alternate"> tags in the head.
--}}
@if(count($links) > 1)
    <div {{ $attributes->merge(['class' => 'flex items-center gap-1.5']) }}>
        <span class="sr-only">{{ __('horoscope.ui.language_label') }}</span>

        @foreach($links as $link)
            @if($link['current'])
                <span aria-current="true"
                      class="rounded-full px-3 py-1 text-xs font-black
                             {{ $tone === 'dark' ? 'bg-white/15 text-white' : 'bg-violet-600 text-white' }}">
                    {{ $link['native'] }}
                </span>
            @else
                <a href="{{ $link['url'] }}"
                   hreflang="{{ $link['code'] }}"
                   lang="{{ $link['code'] }}"
                   class="rounded-full px-3 py-1 text-xs font-black transition
                          {{ $tone === 'dark'
                              ? 'text-white/60 hover:bg-white/10 hover:text-white'
                              : 'text-gray-500 hover:bg-gray-100 hover:text-gray-900 dark:text-gray-400 dark:hover:bg-gray-800 dark:hover:text-white' }}">
                    {{ $link['native'] }}
                </a>
            @endif
        @endforeach
    </div>
@endif
