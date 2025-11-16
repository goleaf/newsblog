<div class="px-6 py-6">
    <h1 class="text-2xl font-semibold text-gray-900 dark:text-gray-100 mb-4">Page Ordering</h1>
    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Drag and drop pages to reorder them. This affects navigation and listing order.</p>

    <div id="pages-list" class="space-y-2">
        @foreach($pages as $page)
            <div class="page-item flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-800 rounded-lg cursor-move hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                 data-id="{{ $page->id }}"
                 data-order="{{ $page->display_order }}">
                <div class="flex items-center gap-4">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"></path>
                    </svg>

                    <div>
                        <div class="font-semibold text-gray-900 dark:text-gray-100">
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
                    <a href="{{ url('/page/'.$page->slug_path) }}" target="_blank" class="px-3 py-1 text-sm text-blue-600 dark:text-blue-400 hover:underline">View</a>
                    <a href="{{ route('admin.pages.edit', $page) }}" class="px-3 py-1 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition">Edit</a>
                </div>
            </div>
        @endforeach
    </div>

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
                            if (!data.success) {
                                console.error('Order update failed');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                    }
                });
            }
        });
    </script>
</div>
