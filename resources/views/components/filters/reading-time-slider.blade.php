@props(['minTime' => null, 'maxTime' => null])

<div class="mb-4" x-data="{
    minTime: {{ $minTime ?? 0 }},
    maxTime: {{ $maxTime ?? 60 }},
    updateFilters() {
        filters.reading_time_min = this.minTime;
        filters.reading_time_max = this.maxTime;
    }
}">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Reading Time (minutes)
    </label>

    <!-- Range Display -->
    <div class="flex items-center justify-between mb-2">
        <span class="text-sm text-gray-600 dark:text-gray-400">
            <span x-text="minTime"></span> min
        </span>
        <span class="text-sm text-gray-600 dark:text-gray-400">
            <span x-text="maxTime"></span> min
        </span>
    </div>

    <!-- Dual Range Sliders -->
    <div class="relative pt-1">
        <!-- Min Slider -->
        <input
            type="range"
            min="0"
            max="60"
            step="1"
            x-model="minTime"
            @input="if (minTime > maxTime) maxTime = minTime; updateFilters()"
            class="w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer accent-blue-600"
        >
        
        <!-- Max Slider -->
        <input
            type="range"
            min="0"
            max="60"
            step="1"
            x-model="maxTime"
            @input="if (maxTime < minTime) minTime = maxTime; updateFilters()"
            class="w-full h-2 bg-gray-200 dark:bg-gray-600 rounded-lg appearance-none cursor-pointer accent-blue-600 -mt-2"
        >
    </div>

    <!-- Quick Filters -->
    <div class="mt-3 flex flex-wrap gap-2">
        <button
            type="button"
            @click="minTime = 0; maxTime = 5; updateFilters()"
            class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded transition-colors"
        >
            Quick Read (0-5 min)
        </button>
        <button
            type="button"
            @click="minTime = 5; maxTime = 15; updateFilters()"
            class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded transition-colors"
        >
            Medium (5-15 min)
        </button>
        <button
            type="button"
            @click="minTime = 15; maxTime = 60; updateFilters()"
            class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded transition-colors"
        >
            Long Read (15+ min)
        </button>
        <button
            type="button"
            @click="minTime = 0; maxTime = 60; updateFilters()"
            class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-300 rounded transition-colors"
        >
            All
        </button>
    </div>
</div>
