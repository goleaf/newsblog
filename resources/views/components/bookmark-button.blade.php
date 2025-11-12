@props(['post', 'size' => 'md'])

@php
$isBookmarked = auth()->check() && $post->isBookmarkedBy(auth()->id());
$sizeClasses = [
    'sm' => 'w-4 h-4',
    'md' => 'w-5 h-5',
    'lg' => 'w-6 h-6',
];
$iconSize = $sizeClasses[$size] ?? $sizeClasses['md'];
@endphp

@auth
<button 
    onclick="toggleBookmark({{ $post->id }}, this)"
    class="bookmark-button inline-flex items-center justify-center p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
    data-post-id="{{ $post->id }}"
    data-bookmarked="{{ $isBookmarked ? 'true' : 'false' }}"
    title="{{ $isBookmarked ? 'Remove from reading list' : 'Add to reading list' }}"
>
    <svg class="{{ $iconSize }} transition-colors {{ $isBookmarked ? 'fill-current text-indigo-600 dark:text-indigo-400' : 'stroke-current text-gray-600 dark:text-gray-400' }}" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="{{ $isBookmarked ? 'currentColor' : 'none' }}" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
    </svg>
</button>
@else
<a 
    href="{{ route('login') }}"
    class="inline-flex items-center justify-center p-2 rounded-full hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
    title="Login to bookmark"
>
    <svg class="{{ $iconSize }} stroke-current text-gray-600 dark:text-gray-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
    </svg>
</a>
@endauth

@once
@push('scripts')
<script>
function toggleBookmark(postId, button) {
    const isBookmarked = button.dataset.bookmarked === 'true';
    
    fetch(`/posts/${postId}/bookmark`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const svg = button.querySelector('svg');
            button.dataset.bookmarked = data.bookmarked ? 'true' : 'false';
            
            if (data.bookmarked) {
                // Bookmarked - filled icon
                svg.classList.remove('stroke-current', 'text-gray-600', 'dark:text-gray-400');
                svg.classList.add('fill-current', 'text-indigo-600', 'dark:text-indigo-400');
                svg.setAttribute('fill', 'currentColor');
                button.title = 'Remove from reading list';
            } else {
                // Not bookmarked - outline icon
                svg.classList.remove('fill-current', 'text-indigo-600', 'dark:text-indigo-400');
                svg.classList.add('stroke-current', 'text-gray-600', 'dark:text-gray-400');
                svg.setAttribute('fill', 'none');
                button.title = 'Add to reading list';
            }
            
            // Show toast notification
            showToast(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Failed to update bookmark. Please try again.', 'error');
    });
}

function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed bottom-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 ${type === 'error' ? 'bg-red-500' : 'bg-green-500'}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity 0.3s';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}
</script>
@endpush
@endonce
