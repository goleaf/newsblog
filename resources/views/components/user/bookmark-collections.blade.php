@props(['collections', 'bookmarks', 'currentCollection' => null])

<div x-data="bookmarkCollections()" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-xl font-bold text-gray-900 dark:text-white">Collections</h2>
        <button 
            @click="showCreateModal = true"
            class="inline-flex items-center px-3 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
        >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            New Collection
        </button>
    </div>

    <!-- Collections List -->
    <div class="space-y-2">
        <!-- All Bookmarks (Default) -->
        <a 
            href="{{ route('bookmarks.index') }}"
            class="flex items-center justify-between p-3 rounded-lg transition-colors {{ !$currentCollection ? 'bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300' : 'hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300' }}"
        >
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                </svg>
                <span class="font-medium">All Bookmarks</span>
            </div>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $bookmarks->total() }}</span>
        </a>

        <!-- User Collections -->
        @foreach($collections as $collection)
            <div 
                class="flex items-center justify-between p-3 rounded-lg transition-colors {{ $currentCollection && $currentCollection->id === $collection->id ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-gray-50 dark:hover:bg-gray-700' }}"
                x-data="{ editing: false }"
            >
                <a 
                    href="{{ route('bookmarks.collection', $collection->id) }}"
                    class="flex items-center flex-1 {{ $currentCollection && $currentCollection->id === $collection->id ? 'text-blue-700 dark:text-blue-300' : 'text-gray-700 dark:text-gray-300' }}"
                    x-show="!editing"
                >
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <div class="flex-1">
                        <div class="font-medium">{{ $collection->name }}</div>
                        @if($collection->description)
                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">{{ Str::limit($collection->description, 50) }}</div>
                        @endif
                    </div>
                    <span class="text-sm text-gray-500 dark:text-gray-400 ml-2">{{ $collection->bookmarks_count ?? 0 }}</span>
                </a>

                <!-- Edit Form -->
                <form 
                    x-show="editing"
                    @submit.prevent="updateCollection({{ $collection->id }}, $el)"
                    class="flex-1"
                >
                    <input 
                        type="text" 
                        name="name"
                        value="{{ $collection->name }}"
                        class="w-full px-2 py-1 text-sm border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500"
                        required
                    >
                </form>

                <!-- Actions -->
                <div class="flex items-center space-x-2 ml-2">
                    <button 
                        @click="editing = !editing"
                        class="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                        title="Edit collection"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                    </button>
                    <button 
                        @click="deleteCollection({{ $collection->id }})"
                        class="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                        title="Delete collection"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>
                    @if($collection->is_public)
                        <button 
                            @click="shareCollection({{ $collection->id }})"
                            class="p-1 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400"
                            title="Share collection"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>
        @endforeach

        @if($collections->isEmpty())
            <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                <svg class="mx-auto h-12 w-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                </svg>
                <p class="text-sm">No collections yet</p>
                <p class="text-xs mt-1">Create a collection to organize your bookmarks</p>
            </div>
        @endif
    </div>

    <!-- Create Collection Modal -->
    <div 
        x-show="showCreateModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto"
        @keydown.escape.window="showCreateModal = false"
    >
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div 
                x-show="showCreateModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75"
                @click="showCreateModal = false"
            ></div>

            <!-- Modal panel -->
            <div 
                x-show="showCreateModal"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6"
            >
                <form @submit.prevent="createCollection">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-4">Create New Collection</h3>
                        
                        <div class="space-y-4">
                            <div>
                                <label for="collection-name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Collection Name
                                </label>
                                <input 
                                    type="text" 
                                    id="collection-name"
                                    x-model="newCollection.name"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="e.g., Must Read, Tutorials, Inspiration"
                                    required
                                >
                            </div>

                            <div>
                                <label for="collection-description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Description (Optional)
                                </label>
                                <textarea 
                                    id="collection-description"
                                    x-model="newCollection.description"
                                    rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 rounded-md focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="What's this collection about?"
                                ></textarea>
                            </div>

                            <div class="flex items-center">
                                <input 
                                    type="checkbox" 
                                    id="collection-public"
                                    x-model="newCollection.is_public"
                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                >
                                <label for="collection-public" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                    Make this collection public (others can view it)
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex justify-end space-x-3">
                        <button 
                            type="button"
                            @click="showCreateModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600"
                        >
                            Cancel
                        </button>
                        <button 
                            type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Create Collection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function bookmarkCollections() {
    return {
        showCreateModal: false,
        newCollection: {
            name: '',
            description: '',
            is_public: false
        },

        createCollection() {
            fetch('{{ route("bookmarks.collections.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify(this.newCollection)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to create collection');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to create collection. Please try again.');
            });
        },

        updateCollection(collectionId, form) {
            const formData = new FormData(form);
            
            fetch(`/bookmarks/collections/${collectionId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    name: formData.get('name')
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to update collection');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to update collection. Please try again.');
            });
        },

        deleteCollection(collectionId) {
            if (!confirm('Are you sure you want to delete this collection? Bookmarks will not be deleted, just moved to "All Bookmarks".')) {
                return;
            }

            fetch(`/bookmarks/collections/${collectionId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Failed to delete collection');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to delete collection. Please try again.');
            });
        },

        shareCollection(collectionId) {
            const url = `${window.location.origin}/bookmarks/collections/${collectionId}/public`;
            
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(() => {
                    alert('Collection link copied to clipboard!');
                });
            } else {
                prompt('Copy this link to share:', url);
            }
        }
    }
}
</script>
