<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Broken Links Report') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Statistics -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Broken</div>
                    <div class="text-3xl font-bold text-red-600">{{ $stats['total_pending'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-600 dark:text-gray-400">OK</div>
                    <div class="text-3xl font-bold text-green-600">{{ $stats['total_fixed'] }}</div>
                </div>
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <div class="text-sm text-gray-600 dark:text-gray-400">Ignored</div>
                    <div class="text-3xl font-bold text-gray-600">{{ $stats['total_ignored'] }}</div>
                </div>
            </div>

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    @if ($brokenLinks->isEmpty())
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No broken links found</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">All links are working properly.</p>
                        </div>
                    @else
                        <form id="bulk-action-form" method="POST" action="{{ route('admin.broken-links.bulk-action') }}">
                            @csrf
                            <div class="mb-4 flex gap-2">
                                <select name="action" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300">
                                    <option value="">Bulk Actions</option>
                                    <option value="fix">Mark as Fixed</option>
                                    <option value="ignore">Mark as Ignored</option>
                                    <option value="delete">Delete</option>
                                </select>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    Apply
                                </button>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <th class="px-6 py-3 text-left">
                                                <input type="checkbox" id="select-all" class="rounded">
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Post
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Broken URL
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Status
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Last Checked
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($brokenLinks as $link)
                                            <tr>
                                                <td class="px-6 py-4">
                                                    <input type="checkbox" name="ids[]" value="{{ $link->id }}" class="rounded link-checkbox">
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="text-gray-900 dark:text-gray-100">
                                                        {{ \Illuminate\Support\Str::limit($link->post->title, 50) }}
                                                    </div>
                                                    <a href="{{ route('post.show', $link->post->slug) }}" target="_blank" class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400">
                                                        View Post
                                                    </a>
                                                </td>
                                                <td class="px-6 py-4">
                                                    <a href="{{ $link->url }}" target="_blank" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 break-all" rel="noopener noreferrer">
                                                        {{ $link->url }}
                                                    </a>
                                                </td>
                                                <td class="px-6 py-4">
                                                    @if ($link->response_code)
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                            {{ $link->response_code }}
                                                        </span>
                                                    @else
                                                        <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                            Timeout
                                                        </span>
                                                    @endif
                                                    @if ($link->error_message)
                                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                            {{ \Illuminate\Support\Str::limit($link->error_message, 40) }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400">
                                                    {{ optional($link->checked_at)->diffForHumans() }}
                                                </td>
                                                <td class="px-6 py-4">
                                                    <div class="flex gap-2">
                                                        <form method="POST" action="{{ route('admin.broken-links.mark-fixed', $link) }}" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="text-green-600 hover:text-green-800 dark:text-green-400 text-sm">
                                                                Fix
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('admin.broken-links.mark-ignored', $link) }}" class="inline">
                                                            @csrf
                                                            @method('PATCH')
                                                            <button type="submit" class="text-gray-600 hover:text-gray-800 dark:text-gray-400 text-sm">
                                                                Ignore
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('admin.broken-links.destroy', $link) }}" class="inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-red-600 hover:text-red-800 dark:text-red-400 text-sm" onclick="return confirm('Are you sure?')">
                                                                Delete
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </form>

                        <div class="mt-4">
                            {{ $brokenLinks->links() }}
                        </div>
                    @endif
                </div>
            </div>

            {{-- Recently Fixed Links --}}
            @if(isset($fixedLinks) && $fixedLinks->isNotEmpty())
                <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4">Recently Fixed</h3>
                        <ul class="list-disc pl-5 space-y-2">
                            @foreach($fixedLinks as $link)
                                <li class="break-all text-sm">
                                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="text-green-600 dark:text-green-400 hover:underline">{{ $link->url }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif

            {{-- Recently Ignored Links --}}
            @if(isset($ignoredLinks) && $ignoredLinks->isNotEmpty())
                <div class="mt-8 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <h3 class="text-lg font-semibold mb-4">Recently Ignored</h3>
                        <ul class="list-disc pl-5 space-y-2">
                            @foreach($ignoredLinks as $link)
                                <li class="break-all text-sm">
                                    <a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer" class="text-gray-600 dark:text-gray-400 hover:underline">{{ $link->url }}</a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @push('scripts')
    <script>
        document.getElementById('select-all')?.addEventListener('change', function(e) {
            document.querySelectorAll('.link-checkbox').forEach(checkbox => {
                checkbox.checked = e.target.checked;
            });
        });

        document.getElementById('bulk-action-form')?.addEventListener('submit', function(e) {
            const action = this.querySelector('select[name="action"]').value;
            if (!action) {
                e.preventDefault();
                alert('Please select an action');
                return;
            }

            const checkedBoxes = this.querySelectorAll('.link-checkbox:checked');
            if (checkedBoxes.length === 0) {
                e.preventDefault();
                alert('Please select at least one link');
                return;
            }

            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete the selected links?')) {
                    e.preventDefault();
                }
            }
        });
    </script>
    @endpush
</x-app-layout>
