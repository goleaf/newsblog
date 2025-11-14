@props(['recentBookmarks', 'recentComments', 'recentReactions'])

<div class="bg-white dark:bg-gray-800 rounded-lg shadow-md overflow-hidden">
    <div class="border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 px-6 py-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Recent Activity</h3>
        <p class="text-sm text-gray-600 dark:text-gray-400">Your latest interactions</p>
    </div>

    <div class="p-6">
        <div class="space-y-6">
            @php
                // Merge all activities and sort by created_at
                $activities = collect();
                
                foreach ($recentBookmarks as $bookmark) {
                    $activities->push([
                        'type' => 'bookmark',
                        'created_at' => $bookmark->created_at,
                        'data' => $bookmark
                    ]);
                }
                
                foreach ($recentComments as $comment) {
                    $activities->push([
                        'type' => 'comment',
                        'created_at' => $comment->created_at,
                        'data' => $comment
                    ]);
                }
                
                foreach ($recentReactions as $reaction) {
                    $activities->push([
                        'type' => 'reaction',
                        'created_at' => $reaction->created_at,
                        'data' => $reaction
                    ]);
                }
                
                $activities = $activities->sortByDesc('created_at')->take(10);
            @endphp

            @forelse($activities as $activity)
                <div class="flex items-start space-x-4 pb-6 border-b border-gray-200 dark:border-gray-700 last:border-0 last:pb-0">
                    <!-- Icon -->
                    <div class="flex-shrink-0">
                        @if($activity['type'] === 'bookmark')
                            <div class="p-2 bg-blue-100 dark:bg-blue-900/30 rounded-full">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/>
                                </svg>
                            </div>
                        @elseif($activity['type'] === 'comment')
                            <div class="p-2 bg-green-100 dark:bg-green-900/30 rounded-full">
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                </svg>
                            </div>
                        @else
                            <div class="p-2 bg-purple-100 dark:bg-purple-900/30 rounded-full">
                                <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        @if($activity['type'] === 'bookmark')
                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                <span class="font-medium">Bookmarked</span> an article
                            </p>
                            @if($activity['data']->post)
                                <a href="{{ route('post.show', $activity['data']->post->slug) }}" class="mt-1 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 line-clamp-2">
                                    {{ $activity['data']->post->title }}
                                </a>
                            @endif
                        @elseif($activity['type'] === 'comment')
                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                <span class="font-medium">Commented</span> on an article
                            </p>
                            @if($activity['data']->post)
                                <a href="{{ route('post.show', $activity['data']->post->slug) }}#comment-{{ $activity['data']->id }}" class="mt-1 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 line-clamp-2">
                                    {{ $activity['data']->post->title }}
                                </a>
                                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400 line-clamp-2">
                                    "{{ Str::limit($activity['data']->content, 100) }}"
                                </p>
                            @endif
                        @else
                            <p class="text-sm text-gray-900 dark:text-gray-100">
                                <span class="font-medium">Reacted</span> with 
                                <span class="inline-flex items-center">
                                    @if($activity['data']->type === 'like')
                                        👍
                                    @elseif($activity['data']->type === 'love')
                                        ❤️
                                    @elseif($activity['data']->type === 'laugh')
                                        😂
                                    @elseif($activity['data']->type === 'wow')
                                        😮
                                    @elseif($activity['data']->type === 'sad')
                                        😢
                                    @elseif($activity['data']->type === 'angry')
                                        😠
                                    @endif
                                </span>
                            </p>
                            @if($activity['data']->post)
                                <a href="{{ route('post.show', $activity['data']->post->slug) }}" class="mt-1 text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 line-clamp-2">
                                    {{ $activity['data']->post->title }}
                                </a>
                            @endif
                        @endif

                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $activity['created_at']->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-gray-100">No activity yet</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Start exploring articles to see your activity here.</p>
                    <div class="mt-6">
                        <a href="{{ route('home') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            Explore Articles
                        </a>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
