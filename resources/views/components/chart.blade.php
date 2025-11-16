@props([
    'type' => 'line', // line|bar|pie|doughnut|area(alias line with fill)
    'data' => [], // Chart.js dataset config OR provide csv
    'csv' => null, // inline CSV string "label,value\nA,10\nB,20"
    'options' => [],
    'height' => 320,
])

<div
    x-data="chartComponent({
        type: @js($type),
        data: @js($data),
        csv: @js($csv),
        options: @js($options),
    })"
    x-init="init($el)"
    class="w-full"
    style="height: {{ (int) $height }}px"
>
    <canvas class="h-full w-full"></canvas>
</div>


