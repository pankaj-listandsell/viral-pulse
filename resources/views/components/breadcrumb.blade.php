@props(['crumbs' => []])

{{-- The visible half of the BreadcrumbList already in the page's JSON-LD. A
     crawler reads the schema, a reader reads this, and both need to agree. --}}
@if(! empty($crumbs))
    <nav aria-label="Breadcrumb" {{ $attributes->merge(['class' => 'mb-6 flex flex-wrap items-center gap-1.5 text-xs font-semibold text-gray-500 dark:text-gray-400']) }}>
        <a href="{{ route('home') }}" class="transition hover:text-brand-600 dark:hover:text-brand-400">Home</a>

        @foreach($crumbs as $crumb)
            <span aria-hidden="true">&rsaquo;</span>

            @if($loop->last)
                <span class="text-gray-900 dark:text-white" aria-current="page">{{ $crumb['name'] }}</span>
            @else
                <a href="{{ $crumb['url'] }}" class="transition hover:text-brand-600 dark:hover:text-brand-400">{{ $crumb['name'] }}</a>
            @endif
        @endforeach
    </nav>
@endif
