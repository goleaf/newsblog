<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ __('Admin Search') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form method="GET" action="{{ route('admin.search') }}" class="mb-6">
                        <div class="flex gap-4">
                            <input
                                type="text"
                                name="q"
                                value="{{ $query }}"
                                placeholder="Search..."
                                class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                            <select
                                name="type"
                                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                            >
                                <option value="posts" {{ $type === 'posts' ? 'selected' : '' }}>Posts</option>
                                <option value="users" {{ $type === 'users' ? 'selected' : '' }}>Users</option>
                                <option value="comments" {{ $type === 'comments' ? 'selected' : '' }}>Comments</option>
                            </select>
                            <button
                                type="submit"
                                class="rounded-md bg-indigo-600 px-4 py-2 text-white hover:bg-indigo-700"
                            >
                                Search
                            </button>
                        </div>
                    </form>

                    @if($query)
                        <div class="mb-4">
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Found {{ $results->count() }} results for "{{ $query }}" in {{ $type }}
                            </p>
                        </div>

                        @if($results->isEmpty())
                            <p class="text-gray-600 dark:text-gray-400">No results found.</p>
                        @else
                            <div class="space-y-4">
                                @foreach($results as $result)
                                    <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                        @if($type === 'posts')
                                            <h3 class="text-lg font-semibold">{{ $result->title }}</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                Status: {{ $result->status }} | Author: {{ $result->user->name }}
                                            </p>
                                        @elseif($type === 'users')
                                            <h3 class="text-lg font-semibold">{{ $result->name }}</h3>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">{{ $result->email }}</p>
                                        @elseif($type === 'comments')
                                            <p class="text-sm">{{ Str::limit($result->content, 200) }}</p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                                By: {{ $result->author_name }} | Post: {{ $result->post->title }}
                                            </p>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
