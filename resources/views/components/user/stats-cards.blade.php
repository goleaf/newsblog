ent; if (current >= target) { count = target; clearInterval(timer); } else { count = Math.floor(current); } }, 16); }, 100)">
                    <span x-text="count.toLocaleString()">0</span>
                </p>
                <a href="{{ route('bookmarks.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 mt-2 inline-block">
                    View all →
                </a>
            </div>
            <div class="p-3 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Comments Count -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 transition-all hover:shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Comments</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2" x-data="{ count: 0 }" x-init="setTimeout(() => { let target = {{ $stats['comments_count'] }}; let duration = 1000; let increment = target / (duration / 16); let current = 0; let timer = setInterval(() => { current += increment; if (current >= target) { count = target; clearInterval(timer); } else { count = Math.floor(current); } }, 16); }, 200)">
                    <span x-text="count.toLocaleString()">0</span>
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Across all posts
                </p>
            </div>
            <div class="p-3 bg-green-100 dark:bg-green-900/30 rounded-full">
                <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Reactions Count -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 transition-all hover:shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Reactions</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2" x-data="{ count: 0 }" x-init="setTimeout(() => { let target = {{ $stats['reactions_count'] }}; let duration = 1000; let increment = target / (duration / 16); let current = 0; let timer = setInterval(() => { current += increment; if (current >= target) { count = target; clearInterval(timer); } else { count = Math.floor(current); } }, 16); }, 300)">
                    <span x-text="count.toLocaleString()">0</span>
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    Given to articles
                </p>
            </div>
            <div class="p-3 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                </svg>
            </div>
        </div>
    </div>

    <!-- Reading Time -->
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 transition-all hover:shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex-1">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Reading Time</p>
                <p class="text-3xl font-bold text-gray-900 dark:text-gray-100 mt-2" x-data="{ count: 0 }" x-init="setTimeout(() => { let target = {{ $stats['total_reading_time'] }}; let duration = 1000; let increment = target / (duration / 16); let current = 0; let timer = setInterval(() => { current += increment; if (current >= target) { count = target; clearInterval(timer); } else { count = Math.floor(current); } }, 16); }, 400)">
                    <span x-text="count.toLocaleString()">0</span> <span class="text-lg text-gray-600 dark:text-gray-400">min</span>
                </p>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    From bookmarked posts
                </p>
            </div>
            <div class="p-3 bg-orange-100 dark:bg-orange-900/30 rounded-full">
                <svg class="w-8 h-8 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
        </div>
    </div>
</div>
