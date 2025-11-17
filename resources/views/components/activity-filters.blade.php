@props(['currentType' => null, 'isFollowing' => false])

<div class="bg-white dark:bg-gray-800 shadow-sm rounded-lg p-4">
    <div class="flex flex-wrap gap-2">
        <a 
            href="{{ $isFollowing ? route('activities.following') : route('activities.index') }}" 
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ !$currentType ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
        >
            All Activities
        </a>
        
        <a 
            href="{{ $isFollowing ? route('activities.following', ['type' => 'published_article']) : route('activities.index', ['type' => 'published_article']) }}" 
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $currentType === 'published_article' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
        >
            <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Published
        </a>
        
        <a 
            href="{{ $isFollowing ? route('activities.following', ['type' => 'commented']) : route('activities.index', ['type' => 'commented']) }}" 
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $currentType === 'commented' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
        >
            <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            Comments
        </a>
        
        <a 
            href="{{ $isFollowing ? route('activities.following', ['type' => 'bookmarked']) : route('activities.index', ['type' => 'bookmarked']) }}" 
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $currentType === 'bookmarked' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
        >
            <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
            </svg>
            Bookmarks
        </a>
        
        <a 
            href="{{ $isFollowing ? route('activities.following', ['type' => 'followed']) : route('activities.index', ['type' => 'followed']) }}" 
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $currentType === 'followed' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}"
        >
            <svg class="inline-block w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
            Follows
        </a>
    </div>
</div>
