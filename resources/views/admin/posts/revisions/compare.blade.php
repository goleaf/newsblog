<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <div class="flex justify-between items-center mb-6">
                        <div>
                            <h2 class="text-2xl font-bold">Compare Revisions</h2>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Comparing Revision #{{ $oldRevision->id }} with Revision #{{ $newRevision->id }}
                            </p>
                        </div>
                        <a href="{{ route('admin.posts.revisions.index', $post) }}" 
                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                            ← Back to Revisions
                        </a>
                    </div>

                    <div class="mb-6 grid grid-cols-2 gap-4 text-sm">
                        <div class="p-4 bg-red-50 dark:bg-red-900/20 rounded">
                            <h3 class="font-semibold mb-2">Old Revision #{{ $oldRevision->id }}</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ $oldRevision->created_at->format('M d, Y H:i') }} by {{ $oldRevision->user->name }}
                            </p>
                        </div>
                        <div class="p-4 bg-green-50 dark:bg-green-900/20 rounded">
                            <h3 class="font-semibold mb-2">New Revision #{{ $newRevision->id }}</h3>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ $newRevision->created_at->format('M d, Y H:i') }} by {{ $newRevision->user->name }}
                            </p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        {{-- Title Comparison --}}
                        <div>
                            <h3 class="text-lg font-semibold mb-3">Title</h3>
                            @if($diff['title']['changed'])
                                <div class="border dark:border-gray-700 rounded overflow-hidden">
                                    <div class="grid grid-cols-2">
                                        <div class="p-4 bg-red-50 dark:bg-red-900/20 border-r dark:border-gray-700">
                                            <span class="text-sm font-medium text-red-700 dark:text-red-300">Old</span>
                                            <p class="mt-2 line-through text-red-800 dark:text-red-200">{{ $diff['title']['old'] }}</p>
                                        </div>
                                        <div class="p-4 bg-green-50 dark:bg-green-900/20">
                                            <span class="text-sm font-medium text-green-700 dark:text-green-300">New</span>
                                            <p class="mt-2 text-green-800 dark:text-green-200">{{ $diff['title']['new'] }}</p>
                                        </div>
                                    </div>
                                </div>
                            @else
                                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded">
                                    <p class="text-gray-600 dark:text-gray-400">No changes</p>
                                    <p class="mt-2">{{ $diff['title']['old'] }}</p>
                                </div>
                            @endif
                        </div>

                        {{-- Excerpt Comparison --}}
                        @if($diff['excerpt']['old'] || $diff['excerpt']['new'])
                            <div>
                                <h3 class="text-lg font-semibold mb-3">Excerpt</h3>
                                @if($diff['excerpt']['changed'])
                                    <div class="border dark:border-gray-700 rounded overflow-hidden">
                                        <div class="grid grid-cols-2">
                                            <div class="p-4 bg-red-50 dark:bg-red-900/20 border-r dark:border-gray-700">
                                                <span class="text-sm font-medium text-red-700 dark:text-red-300">Old</span>
                                                <p class="mt-2 line-through text-red-800 dark:text-red-200">{{ $diff['excerpt']['old'] }}</p>
                                            </div>
                                            <div class="p-4 bg-green-50 dark:bg-green-900/20">
                                                <span class="text-sm font-medium text-green-700 dark:text-green-300">New</span>
                                                <p class="mt-2 text-green-800 dark:text-green-200">{{ $diff['excerpt']['new'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded">
                                        <p class="text-gray-600 dark:text-gray-400">No changes</p>
                                        <p class="mt-2">{{ $diff['excerpt']['old'] }}</p>
                                    </div>
                                @endif
                            </div>
                        @endif

                        {{-- Content Comparison --}}
                        <div>
                            <h3 class="text-lg font-semibold mb-3">Content</h3>
                            @if($diff['content']['changed'])
                                <div class="border dark:border-gray-700 rounded overflow-hidden">
                                    <div class="bg-gray-100 dark:bg-gray-900 p-2 text-xs font-mono">
                                        <span class="text-red-600 dark:text-red-400">- Removed</span>
                                        <span class="ml-4 text-green-600 dark:text-green-400">+ Added</span>
                                    </div>
                                    <div class="p-4 bg-white dark:bg-gray-800 font-mono text-sm overflow-x-auto">
                                        @foreach($diff['content']['diff'] as $line)
                                            @if($line['type'] === 'removed')
                                                <div class="bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-200 px-2 py-1">
                                                    <span class="text-red-600 dark:text-red-400">-</span> {{ $line['content'] }}
                                                </div>
                                            @elseif($line['type'] === 'added')
                                                <div class="bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-200 px-2 py-1">
                                                    <span class="text-green-600 dark:text-green-400">+</span> {{ $line['content'] }}
                                                </div>
                                            @else
                                                <div class="px-2 py-1 text-gray-700 dark:text-gray-300">
                                                    <span class="text-gray-400">  </span> {{ $line['content'] }}
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded">
                                    <p class="text-gray-600 dark:text-gray-400">No changes</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="mt-6 flex gap-3">
                        <form method="POST" 
                              action="{{ route('admin.posts.revisions.restore', [$post, $oldRevision]) }}"
                              onsubmit="return confirm('Are you sure you want to restore the old revision?');">
                            @csrf
                            <button type="submit" 
                                    class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                                Restore Old Revision
                            </button>
                        </form>

                        <form method="POST" 
                              action="{{ route('admin.posts.revisions.restore', [$post, $newRevision]) }}"
                              onsubmit="return confirm('Are you sure you want to restore the new revision?');">
                            @csrf
                            <button type="submit" 
                                    class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                                Restore New Revision
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
