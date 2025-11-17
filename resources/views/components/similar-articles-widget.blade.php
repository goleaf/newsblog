@props(['article'])

@php
    $similarArticles = app(\App\Services\RecommendationService::class)->getSimilarArticles($article, 3);
@endphp

@if($similarArticles->isNotEmpty())
    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" />
            </svg>
            Similar Articles
        </h3>

        <div class="space-y-4">
            @foreach($similarArticles as $similarArticle)
                <article class="group">
                    <h4 class="font-medium text-gray-900 dark:text-gray-100 mb-1">
                        <a href="{{ route('articles.show', $similarArticle->slug) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                            {{ $similarArticle->title }}
                        </a>
                    </h4>
                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                        <span>{{ $similarArticle->user->name }}</span>
                        <span>•</span>
                        <span>{{ $similarArticle->published_at?->diffForHumans() }}</span>
                        @if($similarArticle->reading_time)
                            <span>•</span>
                            <span>{{ $similarArticle->reading_time }} min</span>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
            <a href="{{ route('recommendations.similar', $article) }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline font-medium">
                View all similar articles →
            </a>
        </div>
    </div>
@endif

