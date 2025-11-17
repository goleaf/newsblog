@props(['limit' => 5])

@php
    // Get suggested users to follow
    // Logic: Get popular users (by follower count) that the current user is not following
    $suggestedUsers = collect();
    
    if (auth()->check()) {
        $currentUser = auth()->user();
        
        // Get users with most followers that current user is not following
        $suggestedUsers = \App\Models\User::query()
            ->where('id', '!=', $currentUser->id)
            ->whereNotIn('id', function($query) use ($currentUser) {
                $query->select('followed_id')
                    ->from('follows')
                    ->where('follower_id', $currentUser->id);
            })
            ->withCount('followers')
            ->orderByDesc('followers_count')
            ->limit($limit)
            ->get();
    }
@endphp

@if($suggestedUsers->count() > 0)
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
        <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-4 py-3">
            <h3 class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                Suggested to Follow
            </h3>
        </div>
        <div class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($suggestedUsers as $suggestedUser)
                <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    <div class="flex items-start space-x-3">
                        <a href="{{ route('users.show', $suggestedUser) }}" class="flex-shrink-0">
                            <img 
                                src="{{ $suggestedUser->avatar_url }}" 
                                alt="{{ $suggestedUser->name }}" 
                                class="w-10 h-10 rounded-full"
                            >
                        </a>
                        <div class="flex-1 min-w-0">
                            <a href="{{ route('users.show', $suggestedUser) }}" class="text-sm font-semibold text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400 block truncate">
                                {{ $suggestedUser->name }}
                            </a>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $suggestedUser->followers_count }} {{ Str::plural('follower', $suggestedUser->followers_count) }}
                            </p>
                        </div>
                        <div class="flex-shrink-0">
                            <x-follow-button :user="$suggestedUser" />
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @if($suggestedUsers->count() >= $limit)
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-900 border-t border-gray-200 dark:border-gray-700">
                <a href="{{ route('users.show', auth()->user()) }}" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                    View all suggestions
                </a>
            </div>
        @endif
    </div>
@endif
