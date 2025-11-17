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
                            <div class="flex items-center justify-between">
                                <div>
                                    <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        {{ $user->name }}
                                    </h1>
                                    <p class="text-gray-600 dark:text-gray-400">
                                        {{ ucfirst($user->role->value) }}
                                    </p>
                                </div>
                                @if(!$isOwnProfile && auth()->check())
                                    <x-follow-button :user="$user" />
                                @endif
                            </div>
                            @if($user->bio)
                                <p class="mt-2 text-gray-700 dark:text-gray-300">
                                    {{ $user->bio }}
                                </p>
                            @endif
                            
                            <!-- Additional Profile Info -->
                            @if($user->profile)
                                <div class="mt-3 flex flex-wrap gap-4 text-sm text-gray-600 dark:text-gray-400">
                                    @if($user->profile->location && ($user->preferences->preferences['show_location'] ?? true))
                                        <div class="flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                            </svg>
                                            {{ $user->profile->location }}
                                        </div>
                                    @endif
                                    @if($user->profile->website)
                                        <a href="{{ $user->profile->website }}" target="_blank" rel="noopener noreferrer" class="flex items-center hover:text-blue-600 dark:hover:text-blue-400">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9" />
                                            </svg>
                                            {{ parse_url($user->profile->website, PHP_URL_HOST) }}
                                        </a>
                                    @endif
                                </div>
                                
                                <!-- Social Links -->
                                @if($user->profile->social_links && count(array_filter($user->profile->social_links)))
                                    <div class="mt-3 flex gap-3">
                                        @if(!empty($user->profile->social_links['twitter']))
                                            <a href="https://twitter.com/{{ $user->profile->social_links['twitter'] }}" target="_blank" rel="noopener noreferrer" class="text-gray-600 dark:text-gray-400 hover:text-blue-500 dark:hover:text-blue-400">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                                </svg>
                                            </a>
                                        @endif
                                        @if(!empty($user->profile->social_links['github']))
                                            <a href="https://github.com/{{ $user->profile->social_links['github'] }}" target="_blank" rel="noopener noreferrer" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-100">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path fill-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z" clip-rule="evenodd" />
                                                </svg>
                                            </a>
                                        @endif
                                        @if(!empty($user->profile->social_links['linkedin']))
                                            <a href="{{ $user->profile->social_links['linkedin'] }}" target="_blank" rel="noopener noreferrer" class="text-gray-600 dark:text-gray-400 hover:text-blue-700 dark:hover:text-blue-500">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                                </svg>
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                <a href="{{ route('users.followers', $user) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center hover:shadow-lg transition-shadow">
                    <div class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                        {{ $stats['followers_count'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Followers
                    </div>
                </a>
                <a href="{{ route('users.following', $user) }}" class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center hover:shadow-lg transition-shadow">
                    <div class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                        {{ $stats['following_count'] }}
                    </div>
                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Following
                    </div>
                </a>
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 text-center">
                    <div class="text-3xl font-bold text-teal-600 dark:text-teal-400">
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
