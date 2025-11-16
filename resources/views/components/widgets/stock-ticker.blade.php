@props([
    'widget',
    'symbols' => 'AAPL,MSFT,GOOG',
])

<section
    class="rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4"
    data-widget="stock-ticker"
    data-endpoint="{{ route('api.widgets.stocks') }}"
    data-symbols="{{ $symbols }}"
>
    <div class="flex items-center justify-between mb-2">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
            {{ __('Market') }}
        </h3>
        <button type="button" class="text-xs text-gray-500 dark:text-gray-400 hover:underline" data-refresh>
            {{ __('Refresh') }}
        </button>
    </div>
    <div class="space-y-2" data-ticker-list>
        <!-- Filled by JS -->
    </div>
</section>



