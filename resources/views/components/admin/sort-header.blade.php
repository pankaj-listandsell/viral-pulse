@props(['column', 'sort', 'direction', 'default' => 'desc'])

@php
    $isActive = $sort === $column;

    /*
     * Clicking the active column flips it. Clicking a new one starts from the
     * direction that reads naturally for that kind of data - newest first for
     * a date, A to Z for a name - rather than always starting ascending and
     * making the reader click twice to see what they came for.
     */
    $next = $isActive ? ($direction === 'asc' ? 'desc' : 'asc') : $default;

    // Every other filter on the page rides along, and the page resets: sorting
    // differently while staying on page 4 lands the reader somewhere arbitrary.
    $url = request()->fullUrlWithQuery(['sort' => $column, 'direction' => $next, 'page' => null]);

    $icon = $isActive
        ? ($direction === 'asc' ? 'chevron-up' : 'chevron-down')
        : 'chevrons-up-down';
@endphp

{{-- aria-sort is what tells a screen reader which column the table is ordered
     by, and it belongs on the cell rather than on the link inside it. --}}
<th scope="col"
    @if($isActive) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif
    {{ $attributes->merge(['class' => 'px-5 py-3 font-medium']) }}>

    <a href="{{ $url }}"
       class="group inline-flex items-center gap-1 transition hover:text-gray-900 dark:hover:text-white
              {{ $isActive ? 'text-gray-900 dark:text-white' : '' }}">
        {{ $slot }}

        <x-icon :name="$icon"
                class="size-3.5 shrink-0 transition
                       {{ $isActive ? 'opacity-100' : 'opacity-0 group-hover:opacity-60' }}" />
    </a>
</th>
