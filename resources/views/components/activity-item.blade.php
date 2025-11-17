@props(['activity'])

<div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
    <div class="flex items-start space-x-4">
        <!-- Actor Avatar -->
        <div class="flex-shrink-0">
            @if($activity['actor_avatar'])
                <img 
                    src="{{ $activity['actor_avatar'] }}" 
                    alt="{{ $activity['actor_name'] }}" 
                    class="w-10 h-10 rounded-full"
                >
            @else
                <div class="w-10 h-10 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                    <span class="text-sm text-gray-600 dark:text-gray-300">
                        {{ substr($activity['actor_name'], 0, 1) }}
                    </span>
                </div>
            @endif
        </div>

        <!-- Activity Content -->
        <div class="flex-1 min-w-0">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <!-- Activity Description -->
                    <p class="text-sm text-gray-900 dark:text-gray-100">
                        <span class="font-medium">{{ $activity['actor_name'] }}</span>
                        
                        @if($activity['verb'] === 'published_article' || $activity['verb'] === 'published_post')
                            <span class="text-gray-600 dark:text-gray-400">published</span>
                            @if(isset($activity['is_aggregated']) && $activity['is_aggregated'])
                                <span class="text-gray-600 dark:text-gray-400">{{ $activity['count'] }} articles</span>
                            @else
                                <span class="text-gray-600 dark:text-gray-400">an article</span>
                            @endif
                        @elseif($activity['verb'] === 'commented')
                            <span class="text-gray-600 dark:text-gray-400">commented on</span>
                            @if(isset($activity['is_aggregated']) && $activity['is_aggregated'])
                                <span class="text-gray-600 dark:text-gray-400">{{ $activity['count'] }} articles</span>
                            @else
                                <span class="text-gray-600 dark:text-gray-400">an article</span>
                            @endif
                        @elseif($activity['verb'] === 'bookmarked')
                            <span class="text-gray-600 dark:text-gray-400">bookmarked</span>
                            @if(isset($activity['is_aggregated']) && $activity['is_aggregated'])
                                <span class="text-gray-600 dark:text-gray-400">{{ $activity['count'] }} articles</span>
                            @else
                                <span class="text-gray-600 dark:text-gray-400">an article</span>
                            @endif
                        @elseif($activity['verb'] === 'followed')
                            <span class="text-gray-600 dark:text-gray-400">followed</span>
                            @if(isset($activity['is_aggregated']) && $activity['is_aggregated'])
                                <span class="text-gray-600 dark:text-gray-400">{{ $activity['count'] }} users</span>
                            @else
                                <span class="text-gray-600 dark:text-gray-400">a user</span>
                            @endif
                        @else
                            <span class="text-gray-600 dark:text-gray-400">{{ str_replace('_', ' ', $activity['verb']) }}</span>
                        @endif
                    </p>

                    <!-- Time -->
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        {{ $activity['created_at']->diffForHumans() }}
                    </p>

                    <!-- Subject Preview -->
                    @if(!isset($activity['is_aggregated']) || !$activity['is_aggregated'])
                        @if($activity['subject'] && isset($activity['subject']['title']))
                            <div class="mt-3 bg-gray-50 dark:bg-gray-900 rounded-lg p-3">
                                <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                    <a href="{{ $activity['subject']['url'] }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                        {{ $activity['subject']['title'] }}
                                    </a>
                                </h4>
                                @if(isset($activity['subject']['excerpt']))
                                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                                        {{ Str::limit($activity['subject']['excerpt'], 100) }}
                                    </p>
                                @endif
                            </div>
                        @elseif($activity['subject'] && isset($activity['subject']['name']))
                            <div class="mt-3 flex items-center space-x-2">
                                @if($activity['subject']['avatar'])
                                    <img 
                                        src="{{ $activity['subject']['avatar'] }}" 
                                        alt="{{ $activity['subject']['name'] }}" 
                                        class="w-8 h-8 rounded-full"
                                    >
                                @endif
                                <a href="{{ $activity['subject']['url'] }}" class="text-sm font-medium text-gray-900 dark:text-gray-100 hover:text-blue-600 dark:hover:text-blue-400">
                                    {{ $activity['subject']['name'] }}
                                </a>
                            </div>
                        @endif
                    @else
                        <!-- Aggregated Activities -->
                        <div class="mt-3 space-y-2">
                            @foreach(array_slice($activity['subjects'], 0, 3) as $subject)
                                @if(isset($subject['title']))
                                    <div class="text-sm">
                                        <a href="{{ $subject['url'] }}" class="text-blue-600 dark:text-blue-400 hover:underline">
                                            {{ Str::limit($subject['title'], 60) }}
                                        </a>
                                    </div>
                                @endif
                            @endforeach
                            @if(count($activity['subjects']) > 3)
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    and {{ count($activity['subjects']) - 3 }} more...
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- View Details Link -->
                <a href="{{ route('activities.show', $activity['id']) }}" class="ml-4 text-xs text-blue-600 dark:text-blue-400 hover:underline whitespace-nowrap">
                    Details
                </a>
            </div>
        </div>
    </div>
</div>
