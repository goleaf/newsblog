<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-bold">Revision History</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Post: {{ $post->title }}
                            </p>
                        </div>
                        <a href="{{ route('dashboard') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                            ← Back to Dashboard
                        </a>
                    </div>

                    @if(session('success'))
                        <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300 rounded">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if($revisions->isEmpty())
                        <div class="text-center py-8 text-gray-500 dark:text-gray-400">
                            <p>No revisions found for this post.</p>
                        </div>
                    @else
                        <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded">
                            <p class="text-sm text-blue-800 dark:text-blue-300">
                                <strong>Note:</strong> The system maintains up to 25 revisions per post. Older revisions are automatically deleted.
                            </p>
                        </div>

                        <div class="space-y-4">
                            @foreach($revisions as $revision)
                                <div class="border dark:border-gray-700 rounded-lg p-4 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">
                                                    Revision #{{ $revision->id }}
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $revision->created_at->diffForHumans() }}
                                                </span>
                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                    by {{ $revision->user->name }}
                                                </span>
                                            </div>
                                            
                                            <h3 class="font-medium text-gray-900 dark:text-gray-100 mb-1">
                                                {{ $revision->title }}
                                            </h3>
                                            
                                            @if($revision->revision_note)
                                                <p class="text-sm text-gray-600 dark:text-gray-400 italic">
                                                    Note: {{ $revision->revision_note }}
                                                </p>
                                            @endif
                                            
                                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                                                {{ Str::limit(strip_tags($revision->content), 150) }}
                                            </p>
                                        </div>
                                        
                                        <div class="flex gap-2 ml-4">
                                            <a href="{{ route('admin.posts.revisions.show', [$post, $revision]) }}" 
                                               class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                                                View
                                            </a>
                                            
                                            <form method="POST" 
                                                  action="{{ route('admin.posts.revisions.restore', [$post, $revision]) }}"
                                                  onsubmit="return confirm('Are you sure you want to restore this revision?');">
                                                @csrf
                                                <button type="submit" 
                                                        class="px-3 py-1 text-sm bg-green-600 text-white rounded hover:bg-green-700">
                                                    Restore
                                                </button>
                                            </form>
                                            
                                            <form method="POST" 
                                                  action="{{ route('admin.posts.revisions.destroy', [$post, $revision]) }}"
                                                  onsubmit="return confirm('Are you sure you want to delete this revision?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        @if($revisions->count() >= 2)
                            <div class="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded">
                                <h3 class="font-semibold mb-3">Compare Revisions</h3>
                                <form method="GET" action="{{ route('admin.posts.revisions.compare', $post) }}" class="flex gap-4 items-end">
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-1">Old Revision</label>
                                        <select name="old_revision_id" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800">
                                            @foreach($revisions as $revision)
                                                <option value="{{ $revision->id }}">
                                                    #{{ $revision->id }} - {{ $revision->created_at->format('M d, Y H:i') }} - {{ $revision->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="flex-1">
                                        <label class="block text-sm font-medium mb-1">New Revision</label>
                                        <select name="new_revision_id" required class="w-full rounded border-gray-300 dark:border-gray-600 dark:bg-gray-800">
                                            @foreach($revisions as $revision)
                                                <option value="{{ $revision->id }}" {{ $loop->first ? 'selected' : '' }}>
                                                    #{{ $revision->id }} - {{ $revision->created_at->format('M d, Y H:i') }} - {{ $revision->title }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                                        Compare
                                    </button>
                                </form>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
