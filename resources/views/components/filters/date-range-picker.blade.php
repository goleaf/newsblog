@props(['dateFrom' => null, 'dateTo' => null])

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Date Range
    </label>
    
    <div class="grid grid-cols-2 gap-2">
        <!-- From Date -->
        <div>
            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">From</label>
            <input
                type="date"
                name="date_from"
                x-model="filters.date_from"
                value="{{ $dateFrom }}"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
        </div>

        <!-- To Date -->
        <div>
            <label class="block text-xs text-gray-600 dark:text-gray-400 mb-1">To</label>
            <input
                type="date"
                name="date_to"
                x-model="filters.date_to"
                value="{{ $dateTo }}"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
        </div>
    </div>

    <!-- Quick Date Filters -->
    <div class="mt-2 flex flex-wrap gap-2">
        <button
            type="button"
            @click="filters.date_from = new Date(Date.now() - 24*60*60*1000).toISOString().split('T')[0]; filters.date_to = new Date().toISOString().split('T')[0]"
            class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded transition-colors"
        >
            Today
        </button>
        <button
            type="button"
            @click="filters.date_from = new Date(Date.now() - 7*24*60*60*1000).toISOString().split('T')[0]; filters.date_to = new Date().toISOString().split('T')[0]"
            class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded transition-colors"
        >
            Last 7 Days
        </button>
        <button
            type="button"
            @click="filters.date_from = new Date(Date.now() - 30*24*60*60*1000).toISOString().split('T')[0]; filters.date_to = new Date().toISOString().split('T')[0]"
            class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded transition-colors"
        >
            Last 30 Days
        </button>
        <button
            type="button"
            @click="filters.date_from = ''; filters.date_to = ''"
            class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded transition-colors"
        >
            Clear
        </button>
    </div>
</div>
