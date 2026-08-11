@props([
    'points' => [],
    'type' => 'line',
    'label' => '',
    'color' => null,
    'height' => 220,
    'format' => 'M j',
])

@php
    $series = collect($points);

    // Built here rather than inline in the attribute: Blade cannot parse a
    // multi-line array literal inside an HTML attribute.
    $config = [
        'type' => $type,
        'label' => $label,
        'color' => $color,
        'labels' => $series
            ->map(fn (array $point): string => \Illuminate\Support\Carbon::parse($point['date'])->format($format))
            ->all(),
        'data' => $series->pluck('total')->map(fn ($total): int => (int) $total)->all(),
    ];
@endphp

<div style="height: {{ $height }}px" {{ $attributes->merge(['class' => 'relative']) }}>
    <canvas data-chart="{{ json_encode($config) }}"></canvas>
</div>
