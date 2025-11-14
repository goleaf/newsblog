@props(['post', 'showLabels' => true, 'size' => 'md'])

@php
    $viewCount = $post->view_count ?? 0;
    $commentCount = $post->comments()->approved()->count();
    $reactionCount = $post->reactions()->count();
    $bookmarkCount = $post->bookmarks_count ?? 0;
    $isTrending = $post->is_trending ?? false;
    
    $sizeClasses = [
        'sm' => 'text-xs gap-3',
        'md' => 'text-sm gap-4',
        'lg' => 'text-base gap-5',
    ];
    
    $iconSizes = [
        'sm' => 'w-4 h-4',
        'md' => 'w-5 h-5',
        'lg' => 'w-6 h-6',
    ];
    
    $sizeClass = $sizeClasses[$size] ?? $sizeClasses['md'];
    $iconSize = $iconSizes[$size] ?? $iconSizes['md'];
@endphp

<div class="flex items-center flex-wrap {{ $sizeClass }} text-gray-600 dark:text-gray-400">
    <!-- View Count -->
    @if($viewCount > 0)
        <div class="inline-flex items-center gap-1.5" title="{{ number_format($viewCount) }} views">
            <svg class="{{ $iconSize }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
            </svg>
            <span class="font-medium">
                {{ $viewCount >= 1000 ? number_format($viewCount / 1000, 1) . 'k' : number_format($viewCount) }}
            </span>
            @if($showLabels)
                <span class="hidden sm:inline">{{ Str::plural('view', $viewCount) }}</span>
            @endif
        </div>
    @endif
    
    <!-- Comment Count -->
    @if($commentCount > 0)
        <div class="inline-flex items-center gap-1.5" title="{{ number_format($commentCount) }} comments">
            <svg class="{{ $iconSize }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <span class="font-medium">
                {{ $commentCount >= 1000 ? number_format($commentCount / 1000, 1) . 'k' : number_format($commentCount) }}
            </span>
            @if($showLabels)
                <span class="hidden sm:inline">{{ Str::plural('comment', $commentCount) }}</span>
            @endif
        </div>
    @endif
    
    <!-- Reaction Count -->
    @if($reactionCount > 0)
        <div class="inline-flex items-center gap-1.5" title="{{ number_format($reactionCount) }} reactions">
            <svg class="{{ $iconSize }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium">
                {{ $reactionCount >= 1000 ? number_format($reactionCount / 1000, 1) . 'k' : number_format($reactionCount) }}
            </span>
            @if($showLabels)
                <span class="hidden sm:inline">{{ Str::plural('reaction', $reactionCount) }}</span>
            @endif
        </div>
    @endif
    
    <!-- Bookmark Count -->
    @if($bookmarkCount > 0)
        <div class="inline-flex items-center gap-1.5" title="{{ number_format($bookmarkCount) }} bookmarks">
            <svg class="{{ $iconSize }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z" />
            </svg>
            <span class="font-medium">
                {{ $bookmarkCount >= 1000 ? number_format($bookmarkCount / 1000, 1) . 'k' : number_format($bookmarkCount) }}
            </span>
            @if($showLabels)
                <span class="hidden sm:inline">{{ Str::plural('bookmark', $bookmarkCount) }}</span>
            @endif
        </div>
    @endif
    
    <!-- Trending Badge -->
    @if($isTrending)
        <div class="inline-flex items-center gap-1.5 px-2 py-1 bg-orange-100 dark:bg-orange-900 text-orange-700 dark:text-orange-300 rounded-full">
            <svg class="{{ $iconSize }}" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd" />
            </svg>
            @if($showLabels)
                <span class="text-xs font-bold">Trending</span>
            @endif
        </div>
    @endif
    
    <!-- Show placeholder if no metrics -->
    @if($viewCount === 0 && $commentCount === 0 && $reactionCount === 0 && $bookmarkCount === 0 && !$isTrending)
        <div class="inline-flex items-center gap-1.5 text-gray-400 dark:text-gray-600">
            <svg class="{{ $iconSize }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <span class="text-xs">New</span>
        </div>
    @endif
</div>
