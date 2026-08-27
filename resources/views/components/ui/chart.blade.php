@props([
    'points' => [],
    'type' => 'line',
    'label' => '',
    'color' => null,
    'height' => 220,
    'format' => 'M j',
    'rawLabels' => false,
])

@php
    $series = collect($points);
    $labels = $rawLabels
        ? $series->pluck('label')->all()
        : $series->map(fn (array $point): string => \Illuminate\Support\Carbon::parse($point['date'])->format($format))->all();

    // Built here rather than inline in the attribute: Blade cannot parse a
    // multi-line array literal inside an HTML attribute.
    $config = [
        'type' => $type,
        'label' => $label,
        'color' => $color,
        'labels' => $labels,
        'data' => $series->pluck('total')->map(fn ($total): int => (int) $total)->all(),
    ];
@endphp

<div style="height: {{ $height }}px" {{ $attributes->merge(['class' => 'relative']) }}>
    <canvas data-chart="{{ json_encode($config) }}"></canvas>
</div>
