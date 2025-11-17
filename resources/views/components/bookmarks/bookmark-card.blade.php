@props(['bookmark'])

@php
    $post = $bookmark->post;
@endphp

<article class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow" x-data="bookmarkCard({{ $bookmark->id }}, {{ $bookmark->is_read ? 'true' : 'false' }}, {{ json_encode($bookmark->notes) }})">
    <div class="flex flex-col md:flex-row">
        <!-- Image Section -->
        @if($post->featured_image)
            <div class="md:w-48 md:flex-shrink-0 relative">
                <img src="{{ $post->featured_image_url }}" alt="{{ $post->image_alt_text ?? $post->title }}" class="w-full h-48 md:h-full object-cover" loading="lazy">
                <button 
                    @click="removeBookmark"
                    class="absolute top-2 right-2 p-2 bg-white dark:bg-gray-800 rounded-full shadow-lg text-red-500 hover:text-red-600 transition-colors"
                    title="Remove from reading list"
                >
                    <svg class="w-5 h-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                        <path d="M17 3H7c-1.1 0-2 .9-2 2v16l7-3 7 3V5c0-1.1-.9-2-2-2z"/>
                    </svg>
                </button>
            </div>
        @endif

        <!-- Content Section -->
        <div class="flex-1 p-6">
            <div class="flex items-start justify-between mb-2">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" style="background-color: {{ $post->category->color_code ?? '#3B82F6' }}20; color: {{ $post->category->color_code ?? '#3B82F6' }}">
                        {{ $post->category->name }}
                    </span>
                    @if($bookmark->is_read)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 dark:bg-green-900/20 text-green-800 dark:text-green-400">
                            Read
                        </span>
                    @endif
                </div>
                @if(!$post->featured_image)
                    <button 
                        @click="removeBookmark"
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

            <div class="flex items-center text-xs text-gray-500 dark:text-gray-400 mb-4">
                <span>{{ $post->user->name }}</span>
                <span class="mx-2">•</span>
                <span>{{ $post->reading_time }} min read</span>
                <span class="mx-2">•</span>
                <span>Saved {{ $bookmark->created_at->diffForHumans() }}</span>
            </div>

            <!-- Notes Section -->
            <div class="mb-4">
                <button 
                    @click="showNotes = !showNotes"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 flex items-center gap-1"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span x-text="showNotes ? 'Hide notes' : (notes ? 'View notes' : 'Add notes')"></span>
                </button>

                <div x-show="showNotes" x-transition class="mt-2">
                    <textarea 
                        x-model="notes"
                        @blur="saveNotes"
                        rows="3"
                        class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                        placeholder="Add your notes here..."
                    ></textarea>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Notes are saved automatically</p>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-4">
                <button 
                    @click="toggleReadStatus"
                    class="text-sm font-medium transition-colors"
                    :class="isRead ? 'text-gray-600 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300' : 'text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300'"
                >
                    <span x-text="isRead ? 'Mark as unread' : 'Mark as read'"></span>
                </button>
            </div>
        </div>
    </div>
</article>

<script>
function bookmarkCard(bookmarkId, initialIsRead, initialNotes) {
    return {
        bookmarkId: bookmarkId,
        isRead: initialIsRead,
        notes: initialNotes || '',
        showNotes: false,
        
        async toggleReadStatus() {
            const endpoint = this.isRead ? `/bookmarks/${this.bookmarkId}/unread` : `/bookmarks/${this.bookmarkId}/read`;
            
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                
                const data = await response.json();
                
                if (data.ok) {
                    this.isRead = data.is_read;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to update read status. Please try again.');
            }
        },
        
        async saveNotes() {
            try {
                const response = await fetch(`/bookmarks/${this.bookmarkId}/notes`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ notes: this.notes })
                });
                
                const data = await response.json();
                
                if (data.ok) {
                    this.notes = data.notes;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to save notes. Please try again.');
            }
        },
        
        async removeBookmark() {
            if (!confirm('Remove this bookmark?')) {
                return;
            }
            
            try {
                const response = await fetch('/bookmarks/toggle', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ post_id: {{ $post->id }} })
                });
                
                const data = await response.json();
                
                if (data.ok && !data.bookmarked) {
                    this.$el.remove();
                    
                    // Check if there are no more bookmarks
                    const articles = document.querySelectorAll('article');
                    if (articles.length === 0) {
                        location.reload();
                    }
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Failed to remove bookmark. Please try again.');
            }
        }
    }
}
</script>
