<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Pages') }}
            </h2>
            <a href="{{ route('admin.pages.create') }}" 
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                Create Page
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                        Drag and drop pages to reorder them
                    </div>

                    <div id="pages-list" class="space-y-2">
                        @foreach($pages as $page)
                            <div class="page-item flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg cursor-move hover:bg-gray-100 dark:hover:bg-gray-600 transition"
                                 data-id="{{ $page->id }}"
                                 data-order="{{ $page->display_order }}">
                                <div class="flex items-center gap-4">
                                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                                    </svg>
                                    
                                    <div>
                                        <div class="font-semibold">
                                            @if($page->parent)
                                                <span class="text-gray-500 dark:text-gray-400">{{ $page->parent->title }} /</span>
                                            @endif
                                            {{ $page->title }}
                                        </div>
                                        <div class="text-sm text-gray-600 dark:text-gray-400">
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                                {{ $page->status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200' }}">
                                                {{ ucfirst($page->status) }}
                                            </span>
                                            <span class="ml-2">Template: {{ ucfirst($page->template) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-2">
                                    <a href="{{ route('page.show', $page->slug_path) }}" 
                                       target="_blank"
                                       class="px-3 py-1 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                        View
                                    </a>
                                    <a href="{{ route('admin.pages.edit', $page) }}" 
                                       class="px-3 py-1 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.pages.destroy', $page) }}" 
                                          onsubmit="return confirm('Are you sure you want to delete this page?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="px-3 py-1 text-sm bg-red-600 hover:bg-red-700 text-white rounded transition">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-6">
                        {{ $pages->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pagesList = document.getElementById('pages-list');
            
            if (pagesList) {
                new Sortable(pagesList, {
                    animation: 150,
                    handle: '.page-item',
                    onEnd: function(evt) {
                        const pages = [];
                        document.querySelectorAll('.page-item').forEach((item, index) => {
                            pages.push({
                                id: item.dataset.id,
                                display_order: index
                            });
                        });

                        fetch('{{ route("admin.pages.update-order") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ pages })
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                console.log('Order updated successfully');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    }
                });
            }
        });
    </script>
    @endpush
</x-app-layout>
