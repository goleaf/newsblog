@extends('layouts.app')

@section('title', $collection->name . ' - My Reading List')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white">{{ $collection->name }}</h1>
                @if($collection->description)
                    <p class="mt-2 text-gray-600 dark:text-gray-400">{{ $collection->description }}</p>
                @endif
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {{ $bookmarks->total() }} {{ Str::plural('bookmark', $bookmarks->total()) }}
                    @if($collection->is_public)
                        <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                            Public
                        </span>
                    @endif
                </p>
            </div>
            <a href="{{ route('bookmarks.index') }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                ← Back to All Bookmarks
            </a>
        </div>
    </div>

    <!-- Collections Sidebar -->
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6 mb-6">
        <div class="lg:col-span-1">
            <x-user.bookmark-collections 
                :collections="$collections" 
                :bookmarks="$bookmarks"
                :current-collection="$collection"
            />
        </div>
        
        <div class="lg:col-span-3">
    @if($bookmarks->count() > 0)
        <!-- Grid Layout -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" x-data="bookmarkDragDrop()">
            @foreach($bookmarks as $bookmark)
                @php $post = $bookmark->post; @endphp
                <article 
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow cursor-move"
                    draggable="true"
                    data-bookmark-id="{{ $bookmark->id }}"
                    @dragstart="dragStart($event)"
                    @dragover.prevent="dragOver($event)"
                    @drop="drop($event)"
                    @dragend="dragEnd($event)"
                >
                    @if($post->featured_image)
                        <div class="relative">
                            <img src="{{ $post->featured_image_url }}" alt="{{ $post->image_alt_text ?? $post->title }}" class="w-full h-48 object-cover" loading="lazy">
                            <button 
                                onclick="toggleBookmark({{ $post->id }}, this)"
                                class="absolute top-2 right-2 p-2 bg-white dark:bg-gray-800 rounded-full shadow-lg text-red-500 hover:text-red-600 transition-colors"
                                title="Remove from reading list"
                            >
                                <svg class="w-5 h-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                    <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                                </svg>
                            </button>
                        </div>
                    @endif
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: {{ $post->category->color_code ?? '#3B82F6' }}20; color: {{ $post->category->color_code ?? '#3B82F6' }}">
                                {{ $post->category->name }}
                            </span>
                            @if(!$post->featured_image)
                                <button 
                                    onclick="toggleBookmark({{ $post->id }}, this)"
                                    class="text-red-500 hover:text-red-600 transition-colors"
                                    title="Remove from reading list"
                                >
                                    <svg class="w-5 h-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                                    </svg>
                                </button>
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
                        <div class="text-xs text-gray-400 dark:text-gray-500">
                            Saved {{ $bookmark->created_at->diffForHumans() }}
                        </div>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-8">
            {{ $bookmarks->links() }}
        </div>
    @else
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
            </svg>
            <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-white">No bookmarks in this collection</h3>
            <p class="mt-2 text-gray-500 dark:text-gray-400">
                Add bookmarks to this collection from your reading list.
            </p>
            <div class="mt-6">
                <a href="{{ route('bookmarks.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    View All Bookmarks
                </a>
            </div>
        </div>
    @endif
        </div>
    </div>
</div>

<script>
function toggleBookmark(postId, button) {
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
            // Remove the article from the page
            button.closest('article').remove();
            
            // Check if there are no more bookmarks
            const articles = document.querySelectorAll('article');
            if (articles.length === 0) {
                location.reload();
            }
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to remove bookmark. Please try again.');
    });
}

function bookmarkDragDrop() {
    return {
        draggedElement: null,
        
        dragStart(event) {
            this.draggedElement = event.target;
            event.target.classList.add('opacity-50');
        },
        
        dragOver(event) {
            event.preventDefault();
            const afterElement = this.getDragAfterElement(event.currentTarget.parentElement, event.clientY);
            const draggable = this.draggedElement;
            
            if (afterElement == null) {
                event.currentTarget.parentElement.appendChild(draggable);
            } else {
                event.currentTarget.parentElement.insertBefore(draggable, afterElement);
            }
        },
        
        drop(event) {
            event.preventDefault();
        },
        
        dragEnd(event) {
            event.target.classList.remove('opacity-50');
            this.saveOrder();
        },
        
        getDragAfterElement(container, y) {
            const draggableElements = [...container.querySelectorAll('article:not(.opacity-50)')];
            
            return draggableElements.reduce((closest, child) => {
                const box = child.getBoundingClientRect();
                const offset = y - box.top - box.height / 2;
                
                if (offset < 0 && offset > closest.offset) {
                    return { offset: offset, element: child };
                } else {
                    return closest;
                }
            }, { offset: Number.NEGATIVE_INFINITY }).element;
        },
        
        saveOrder() {
            const bookmarkIds = [...document.querySelectorAll('[data-bookmark-id]')]
                .map(el => el.dataset.bookmarkId);
            
            fetch(`/bookmarks/collections/{{ $collection->id }}/reorder`, {
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
                if (!data.success) {
                    console.error('Failed to save order');
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }
    }
}
</script>
@endsection
