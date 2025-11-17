@props(['recommendation'])

@php
    $post = $recommendation instanceof \App\Models\Recommendation ? $recommendation->post : $recommendation['post'];
    $reason = $recommendation instanceof \App\Models\Recommendation ? $recommendation->reason : $recommendation['reason'];
    $score = $recommendation instanceof \App\Models\Recommendation ? $recommendation->score : $recommendation['score'];
@endphp

<article class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
    <div class="flex items-start gap-4">
        @if($post->featured_image)
            <div class="flex-shrink-0 w-24 h-24 rounded-lg overflow-hidden bg-gray-200 dark:bg-gray-700">
                <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" class="w-full h-full object-cover">
            </div>
        @endif

        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-2">
                <x-recommendation-reason :reason="$reason" />
            </div>

            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                <a 
                    href="{{ route('articles.show', $post->slug) }}" 
                    class="hover:text-blue-600 dark:hover:text-blue-400"
                    @auth
                    @click="fetch('{{ route('recommendations.track-click') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ post_id: {{ $post->id }} })
                    })"
                    @endauth
                >
                    {{ $post->title }}
                </a>
            </h3>

            @if($post->excerpt)
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
                    {{ $post->excerpt }}
                </p>
            @endif

            <div class="flex items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                <span class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    {{ $post->user->name }}
                </span>

                @if($post->categories->isNotEmpty())
                    <span class="flex items-center gap-1">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        {{ $post->categories->first()->name }}
                    </span>
                @endif

                <span class="flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ $post->published_at?->diffForHumans() }}
                </span>

                @if($post->reading_time)
                    <span>{{ $post->reading_time }} min read</span>
                @endif
            </div>
        </div>
    </div>
</article>

