<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Profile') }}
            </h2>
            <a href="{{ route('profile.edit') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Edit Profile
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Profile Header -->
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="h-32 bg-gradient-to-r from-blue-500 to-purple-600"></div>
                <div class="px-6 pb-6">
                    <div class="flex flex-col sm:flex-row items-center sm:items-end -mt-16 sm:-mt-12">
                        <img 
                            src="{{ $user->avatar_url }}" 
                            alt="{{ $user->name }}" 
                            class="w-32 h-32 rounded-full border-4 border-white dark:border-gray-800 shadow-lg"
                        >
                        <div class="mt-4 sm:mt-0 sm:ml-6 text-center sm:text-left flex-1">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $user->name }}
                            </h1>
                            <p class="text-gray-600 dark:text-gray-400">
                                {{ ucfirst($user->role) }}
                            </p>
                            @if($user->bio)
                                <p class="mt-2 text-gray-700 dark:text-gray-300">
                                    {{ $user->bio }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $stats['total_bookmarks'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Bookmarks
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center">
                    <div class="text-3xl font-bold text-green-600 dark:text-green-400">
                        {{ $stats['total_comments'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Comments
                    </div>
                </div>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center">
                    <div class="text-3xl font-bold text-purple-600 dark:text-purple-400">
                        {{ $stats['total_reactions'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Reactions
                    </div>
                </div>
                @if($user->isAuthor() || $user->isEditor() || $user->isAdmin())
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center">
                        <div class="text-3xl font-bold text-orange-600 dark:text-orange-400">
                            {{ $stats['total_posts'] }}
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Published Posts
                        </div>
                    </div>
                @endif
            </div>

            <!-- Authored Articles (if author) -->
            @if($authoredPosts->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Published Articles</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Recent posts by {{ $user->name }}</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($authoredPosts as $post)
                                <article class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden hover:shadow-lg transition-shadow">
                                    @if($post->featured_image)
                                        <img 
                                            src="{{ $post->featured_image_url }}" 
                                            alt="{{ $post->image_alt_text ?? $post->title }}" 
                                            class="w-full h-40 object-cover"
                                            loading="lazy"
                                        >
                                    @endif
                                    <div class="p-4">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium mb-2" style="background-color: {{ $post->category->color_code ?? '#3B82F6' }}20; color: {{ $post->category->color_code ?? '#3B82F6' }}">
                                            {{ $post->category->name }}
                                        </span>
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2 line-clamp-2">
                                            <a href="{{ route('post.show', $post->slug) }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                                {{ $post->title }}
                                            </a>
                                        </h4>
                                        <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                            <span>{{ $post->published_at->format('M d, Y') }}</span>
                                            <span class="mx-2">•</span>
                                            <span>{{ $post->reading_time }} min read</span>
                                        </div>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Recent Comments -->
            @if($recentComments->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
                    <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Comments</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400">Latest discussions</p>
                    </div>
                    <div class="p-6">
                        <div class="space-y-4">
                            @foreach($recentComments as $comment)
                                <div class="border-b border-gray-200 dark:border-gray-700 pb-4 last:border-0 last:pb-0">
                                    <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">
                                        {{ Str::limit($comment->content, 150) }}
                                    </p>
                                    <div class="flex items-center text-xs text-gray-500 dark:text-gray-400">
                                        <span>On</span>
                                        <a href="{{ route('post.show', $comment->post->slug) }}#comment-{{ $comment->id }}" class="mx-1 text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ $comment->post->title }}
                                        </a>
                                        <span class="mx-2">•</span>
                                        <span>{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
