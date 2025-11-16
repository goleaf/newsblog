@props([
    'widget',
    'posts' => collect(),
])

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm overflow-hidden">
    <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
        <h3 class="text-sm font-semibold text-gray-900 dark:text-white">{{ __('Most Commented') }}</h3>
    </div>

    <ul class="divide-y divide-gray-100 dark:divide-gray-700">
        @forelse($posts as $post)
            <li class="px-4 py-3">
                <a href="{{ route('post.show', $post->slug) }}#comments" class="group flex items-start gap-3">
                    @if($post->featured_image_url ?? false)
                        <x-optimized-image
                            :src="$post->featured_image_url"
                            :alt="$post->image_alt_text ?? $post->title"
                            :width="64"
                            :height="64"
                            class="w-16 h-16 rounded object-cover flex-none"
                        />
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center justify-between gap-3">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white line-clamp-2 group-hover:text-primary-600 dark:group-hover:text-primary-400">{{ $post->title }}</h4>
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-semibold rounded-md bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                </svg>
                                {{ $post->comments_count ?? 0 }}
                            </span>
                        </div>
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            <time datetime="{{ $post->published_at?->toIso8601String() }}">{{ $post->published_at?->diffForHumans() }}</time>
                        </div>
                    </div>
                </a>
            </li>
        @empty
            <li class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ __('No comments yet.') }}</li>
        @endforelse
    </ul>
</div>
