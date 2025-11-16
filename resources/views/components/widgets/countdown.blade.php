@props([
    'widget',
    'target',
    'labels' => [
        'days' => 'Days',
        'hours' => 'Hours',
        'minutes' => 'Minutes',
        'seconds' => 'Seconds',
        'done' => 'Completed',
    ],
])

<section
    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4"
    data-widget="countdown"
    data-target="{{ $target }}"
    data-labels='@json($labels)'
>
    <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100 mb-2">
        {{ __('Countdown') }}
    </h3>
    <div class="grid grid-cols-4 gap-2 text-center" data-countdown>
        <div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" data-days>--</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $labels['days'] }}</div>
        </div>
        <div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" data-hours>--</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $labels['hours'] }}</div>
        </div>
        <div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" data-minutes>--</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $labels['minutes'] }}</div>
        </div>
        <div>
            <div class="text-2xl font-bold text-gray-900 dark:text-gray-100" data-seconds>--</div>
            <div class="text-xs text-gray-500 dark:text-gray-400">{{ $labels['seconds'] }}</div>
        </div>
    </div>
    <div class="mt-2 text-sm text-green-600 dark:text-green-400 hidden" data-done>{{ $labels['done'] }}</div>
    <div class="mt-2 text-xs text-gray-500 dark:text-gray-400" data-updated></div>
</section>



