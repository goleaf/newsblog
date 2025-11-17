@props(['post', 'size' => 'md'])

@php
$sizeClasses = [
    'sm' => 'w-4 h-4',
    'md' => 'w-5 h-5',
    'lg' => 'w-6 h-6',
];
$iconSize = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

<div
    x-data="bookmarkButton({{ $post->id }}, {{ $post->isBookmarkedBy(auth()->id() ?? null) ? 'true' : 'false' }})"
    data-bookmarked="{{ $post->isBookmarkedBy(auth()->id() ?? null) ? 'true' : 'false' }}"
>
    <button
        type="button"
        @click="toggle"
        :title="bookmarked ? 'Remove from reading list' : 'Add to reading list'"
        :aria-label="bookmarked ? 'Remove from reading list' : 'Add to reading list'"
        :aria-pressed="bookmarked.toString()"
        class="bookmark-button inline-flex items-center justify-center p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
    >
        <svg
            class="{{ $iconSize }} transition-colors"
            :class="bookmarked ? 'fill-current text-blue-600 dark:text-blue-400' : 'stroke-current text-gray-600 dark:text-gray-400'"
            xmlns="http://www.w3.org/2000/svg"
            viewBox="0 0 24 24"
            :fill="bookmarked ? 'currentColor' : 'none'"
            stroke="currentColor"
            stroke-width="2"
        >
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
        </svg>
    </button>
</div>

<script>
function bookmarkButton(postId, initialBookmarked) {
    return {
        postId: postId,
        bookmarked: initialBookmarked,
        
        async toggle() {
            try {
                const response = await fetch(`/bookmarks/${this.postId}/toggle`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({})
                });
                
                const data = await response.json();
                
                if (data.ok) {
                    this.bookmarked = data.bookmarked;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to toggle bookmark. Please try again.');
            }
        }
    }
}
</script>
