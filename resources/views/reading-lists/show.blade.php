@extends('layouts.app')

@section('title', $collection->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-start justify-between">
            <div class="flex-1">
                <div class="flex items-center gap-3 mb-2">
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $collection->name }}</h1>
                    @if($collection->is_public)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                            </svg>
                            Public
                        </span>
                    @endif
                </div>
                @if($collection->description)
                    <p class="text-gray-600 dark:text-gray-400 mb-2">{{ $collection->description }}</p>
                @endif
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ $collection->bookmarks->count() }} {{ Str::plural('article', $collection->bookmarks->count()) }}
                </p>
            </div>
            
            @if($isOwner)
                <div class="flex items-center gap-2 ml-4">
                    <a href="{{ route('reading-lists.index') }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        ← Back to Lists
                    </a>
                    <a href="{{ route('reading-lists.edit', $collection) }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Edit
                    </a>
                    @if($collection->share_token)
                        <button 
                            onclick="copyShareLink()"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors"
                        >
                            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                            Copy Link
                        </button>
                    @else
                        <form method="POST" action="{{ route('reading-lists.share', $collection) }}" class="inline">
                            @csrf
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
                                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                                </svg>
                                Generate Share Link
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @if(session('success'))
        <div class="mb-6 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if(session('share_url'))
        <div class="mb-6 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200 px-4 py-3 rounded-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-medium">Share link generated!</p>
                    <p class="text-sm mt-1">
                        <input 
                            type="text" 
                            id="share-url" 
                            value="{{ session('share_url') }}" 
                            readonly 
                            class="bg-white dark:bg-gray-700 border border-blue-300 dark:border-blue-600 rounded px-2 py-1 text-xs w-96"
                        >
                    </p>
                </div>
                <button 
                    onclick="copyShareLink()"
                    class="px-3 py-1 text-sm font-medium text-blue-700 dark:text-blue-300 hover:text-blue-800 dark:hover:text-blue-200"
                >
                    Copy
                </button>
            </div>
        </div>
    @endif

    @if($collection->bookmarks->count() > 0)
        @if($isOwner)
            <div class="mb-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200 px-4 py-3 rounded-lg">
                <p class="text-sm">
                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    Drag and drop articles to reorder them
                </p>
            </div>
        @endif

        <div 
            class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" 
            x-data="readingListDragDrop({{ $isOwner ? 'true' : 'false' }})"
        >
            @foreach($collection->bookmarks as $bookmark)
                @php $post = $bookmark->post; @endphp
                <article 
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow {{ $isOwner ? 'cursor-move' : '' }}"
                    data-bookmark-id="{{ $bookmark->id }}"
                    @if($isOwner)
                        draggable="true"
                        @dragstart="dragStart($event)"
                        @dragover.prevent="dragOver($event)"
                        @drop="drop($event)"
                        @dragend="dragEnd($event)"
                    @endif
                >
                    @if($post->featured_image)
                        <div class="relative">
                            <img 
                                src="{{ $post->featured_image_url }}" 
                                alt="{{ $post->image_alt_text ?? $post->title }}" 
                                class="w-full h-48 object-cover" 
                                loading="lazy"
                            >
                            @if($isOwner)
                                <form 
                                    method="POST" 
                                    action="{{ route('reading-lists.remove-item', [$collection, $bookmark]) }}"
                                    class="absolute top-2 right-2"
                                    onsubmit="return confirm('Remove this article from the reading list?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button 
                                        type="submit"
                                        class="p-2 bg-white dark:bg-gray-800 rounded-full shadow-lg text-red-500 hover:text-red-600 transition-colors"
                                        title="Remove from reading list"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span 
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" 
                                style="background-color: {{ $post->category->color_code ?? '#3B82F6' }}20; color: {{ $post->category->color_code ?? '#3B82F6' }}"
                            >
                                {{ $post->category->name }}
                            </span>
                            @if(!$post->featured_image && $isOwner)
                                <form 
                                    method="POST" 
                                    action="{{ route('reading-lists.remove-item', [$collection, $bookmark]) }}"
                                    onsubmit="return confirm('Remove this article from the reading list?')"
                                >
                                    @csrf
                                    @method('DELETE')
                                    <button 
                                        type="submit"
                                        class="text-red-500 hover:text-red-600 transition-colors"
                                        title="Remove from reading list"
                                    >
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </form>
                            @endif
                        </div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2 line-clamp-2">
                            <a href="{{ route('post.show', $post->slug) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                {{ $post->title }}
                            </a>
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">
                            {{ $post->excerpt }}
                        </p>
                        <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 mb-2">
                            <span>{{ $post->user->name }}</span>
                            <span class="mx-2">•</span>
                            <span>{{ $post->reading_time }} min read</span>
                        </div>
                        @if($bookmark->notes)
                            <div class="mt-3 p-2 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded text-xs text-gray-700 dark:text-gray-300">
                                <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"/>
                                </svg>
                                {{ $bookmark->notes }}
                            </div>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-16 w-16 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No articles in this list</h3>
            <p class="mt-2 text-gray-500 dark:text-gray-400">
                @if($isOwner)
                    Add articles to this reading list from your bookmarks.
                @else
                    This reading list is empty.
                @endif
            </p>
            @if($isOwner)
                <div class="mt-6">
                    <a href="{{ route('bookmarks.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        View Bookmarks
                    </a>
                </div>
            @endif
        </div>
    @endif
</div>

@if($isOwner && $collection->share_token)
    <input type="hidden" id="share-url-hidden" value="{{ route('reading-lists.shared', $collection->share_token) }}">
@endif

<script>
function copyShareLink() {
    const urlInput = document.getElementById('share-url') || document.getElementById('share-url-hidden');
    if (urlInput) {
        const url = urlInput.value;
        navigator.clipboard.writeText(url).then(() => {
            alert('Share link copied to clipboard!');
        }).catch(err => {
            console.error('Failed to copy:', err);
        });
    }
}

function readingListDragDrop(isOwner) {
    if (!isOwner) {
        return {};
    }
    
    return {
        draggedElement: null,
        
        dragStart(event) {
            this.draggedElement = event.target;
            event.target.classList.add('opacity-50');
        },
        
        dragOver(event) {
            event.preventDefault();
            const container = event.currentTarget.parentElement;
            const afterElement = this.getDragAfterElement(container, event.clientX, event.clientY);
            const draggable = this.draggedElement;
            
            if (afterElement == null) {
                container.appendChild(draggable);
            } else {
                container.insertBefore(draggable, afterElement);
            }
        },
        
        drop(event) {
            event.preventDefault();
        },
        
        dragEnd(event) {
            event.target.classList.remove('opacity-50');
            this.saveOrder();
        },
        
        getDragAfterElement(container, x, y) {
            const draggableElements = [...container.querySelectorAll('article:not(.opacity-50)')];
            
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offsetX = x - box.left - box.width / 2;
                const offsetY = y - box.top - box.height / 2;
                const offset = Math.sqrt(offsetX * offsetX + offsetY * offsetY);
                
                if (offset < closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.POSITIVE_INFINITY }).element;
        },
        
        saveOrder() {
            const bookmarkIds = [...document.querySelectorAll('[data-bookmark-id]')]
                .map(el => el.dataset.bookmarkId);
            
            fetch(`{{ route('reading-lists.reorder', $collection) }}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ bookmark_ids: bookmarkIds })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Order saved successfully');
            })
            .catch(error => {
                console.error('Error saving order:', error);
                alert('Failed to save order. Please refresh the page.');
            });
        }
    }
}
</script>
@endsection
