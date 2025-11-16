@props([
    'widget',
    'defaultLat' => 51.5074,
    'defaultLon' => -0.1278,
    'defaultLabel' => 'London',
])

<section
    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4"
    data-widget="weather"
    data-endpoint="{{ route('api.widgets.weather') }}"
    data-default-lat="{{ $defaultLat }}"
    data-default-lon="{{ $defaultLon }}"
    data-default-label="{{ $defaultLabel }}"
>
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
            {{ __('Weather') }}
        </h3>
        <span class="text-xs text-gray-500 dark:text-gray-400" data-weather-location>{{ $defaultLabel }}</span>
    </div>
    <div class="flex items-baseline gap-3">
        <div class="text-3xl font-bold text-gray-900 dark:text-gray-100" data-weather-temp>--</div>
        <div class="text-sm text-gray-600 dark:text-gray-300" data-weather-desc></div>
    </div>
    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400" data-weather-updated></div>
</section>


