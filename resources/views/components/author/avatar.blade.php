@props(['author', 'size' => 'size-8'])

{{-- Initials on the brand colour: a desk has no face, and a stock portrait
     would suggest a person who is not there. --}}
<span {{ $attributes->merge(['class' => "{$size} grid shrink-0 place-items-center rounded-lg bg-brand-600 font-black text-white"]) }} aria-hidden="true">
    <span class="text-[0.7em] leading-none tracking-tight">{{ $author->initials() }}</span>
</span>
