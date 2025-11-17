<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Similar Articles
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Original Article Reference -->
            <div class="mb-6 bg-white dark:bg-gray-800 shadow-md rounded-lg p-6">
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">Similar to:</p>
                <h3 class="text-xl font-bold text-gray-900 dark:text-gray-100">
                    <a href="{{ route('articles.show', $article->slug) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                        {{ $article->title }}
                    </a>
                </h3>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">
                    By {{ $article->user->name }} • {{ $article->published_at?->diffForHumans() }}
                </p>
            </div>

            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                        Articles You Might Like
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        Based on content similarity, categories, and tags
                    </p>
                </div>

                @if($similarArticles->count() > 0)
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @foreach($similarArticles as $similarArticle)
                            <x-similar-article :article="$similarArticle" />
                        @endforeach
                    </div>
                @else
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No similar articles found</h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            We couldn't find articles similar to this one yet.
                        </p>
                        <div class="mt-6">
                            <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                Browse All Articles
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>

