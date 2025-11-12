<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-bold">Revision #{{ $revision->id }}</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Created {{ $revision->created_at->format('M d, Y H:i') }} by {{ $revision->user->name }}
                            </p>
                        </div>
                        <a href="{{ route('admin.posts.revisions.index', $post) }}" 
                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                            ← Back to Revisions
                        </a>
                    </div>

                    @if($revision->revision_note)
                        <div class="mb-4 p-4 bg-blue-50 dark:bg-blue-900/20 rounded">
                            <p class="text-sm text-blue-800 dark:text-blue-300">
                                <strong>Note:</strong> {{ $revision->revision_note }}
                            </p>
                        </div>
                    @endif

                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold mb-2">Title</h3>
                            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded">
                                {{ $revision->title }}
                            </div>
                        </div>

                        @if($revision->excerpt)
                            <div>
                                <h3 class="text-lg font-semibold mb-2">Excerpt</h3>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded">
                                    {{ $revision->excerpt }}
                                </div>
                            </div>
                        @endif

                        <div>
                            <h3 class="text-lg font-semibold mb-2">Content</h3>
                            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded prose dark:prose-invert max-w-none">
                                {!! $revision->content !!}
                            </div>
                        </div>

                        @if($revision->meta_data)
                            <div>
                                <h3 class="text-lg font-semibold mb-2">Metadata</h3>
                                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded">
                                    <dl class="grid grid-cols-2 gap-4">
                                        @foreach($revision->meta_data as $key => $value)
                                            <div>
                                                <dt class="font-medium text-gray-700 dark:text-gray-300">{{ ucfirst(str_replace('_', ' ', $key)) }}</dt>
                                                <dd class="text-gray-600 dark:text-gray-400">{{ $value ?? 'N/A' }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="mt-6 flex gap-3">
                        <form method="POST" 
                              action="{{ route('admin.posts.revisions.restore', [$post, $revision]) }}"
                              onsubmit="return confirm('Are you sure you want to restore this revision?');">
                            @csrf
                            <button type="submit" 
                                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                Restore This Revision
                            </button>
                        </form>

                        <form method="POST" 
                              action="{{ route('admin.posts.revisions.destroy', [$post, $revision]) }}"
                              onsubmit="return confirm('Are you sure you want to delete this revision?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" 
                                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                                Delete This Revision
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
