<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                Activity Detail
            </h2>
            <a href="{{ route('activities.index') }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                Back to Activities
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg overflow-hidden">
                <div class="p-6">
                    <!-- Actor Info -->
                    <div class="flex items-center space-x-4 mb-6">
                        @if($activity['actor_avatar'])
                            <img 
                                src="{{ $activity['actor_avatar'] }}" 
                                alt="{{ $activity['actor_name'] }}" 
                                class="w-16 h-16 rounded-full"
                            >
                        @else
                            <div class="w-16 h-16 rounded-full bg-gray-300 dark:bg-gray-600 flex items-center justify-center">
                                <span class="text-2xl text-gray-600 dark:text-gray-300">
                                    {{ substr($activity['actor_name'], 0, 1) }}
                                </span>
                            </div>
                        @endif
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                {{ $activity['actor_name'] }}
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $activity['created_at']->diffForHumans() }}
                            </p>
                        </div>
                    </div>

                    <!-- Activity Description -->
                    <div class="mb-6">
                        <p class="text-gray-700 dark:text-gray-300">
                            <span class="font-medium">{{ $activity['actor_name'] }}</span>
                            @if($activity['verb'] === 'published_article' || $activity['verb'] === 'published_post')
                                published an article
                            @elseif($activity['verb'] === 'commented')
                                commented on an article
                            @elseif($activity['verb'] === 'bookmarked')
                                bookmarked an article
                            @elseif($activity['verb'] === 'followed')
                                followed a user
                            @else
                                {{ str_replace('_', ' ', $activity['verb']) }}
                            @endif
                        </p>
                    </div>

                    <!-- Subject Details -->
                    @if($activity['subject'])
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-4">
                                Related Content
                            </h4>
                            
                            @if(isset($activity['subject']['title']))
                                <!-- Article/Post Subject -->
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                    <h5 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-2">
                                        <a href="{{ $activity['subject']['url'] }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                            {{ $activity['subject']['title'] }}
                                        </a>
                                    </h5>
                                    @if(isset($activity['subject']['excerpt']))
                                        <p class="text-sm text-gray-600 dark:text-gray-400">
                                            {{ Str::limit($activity['subject']['excerpt'], 200) }}
                                        </p>
                                    @endif
                                    <a href="{{ $activity['subject']['url'] }}" class="inline-block mt-3 text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                        View {{ $activity['subject']['type'] }} →
                                    </a>
                                </div>
                            @elseif(isset($activity['subject']['name']))
                                <!-- User Subject -->
                                <div class="bg-gray-50 dark:bg-gray-900 rounded-lg p-4">
                                    <div class="flex items-center space-x-3">
                                        @if($activity['subject']['avatar'])
                                            <img 
                                                src="{{ $activity['subject']['avatar'] }}" 
                                                alt="{{ $activity['subject']['name'] }}" 
                                                class="w-12 h-12 rounded-full"
                                            >
                                        @endif
                                        <div>
                                            <h5 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                                <a href="{{ $activity['subject']['url'] }}" class="hover:text-blue-600 dark:hover:text-blue-400">
                                                    {{ $activity['subject']['name'] }}
                                                </a>
                                            </h5>
                                            <a href="{{ $activity['subject']['url'] }}" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                                                View Profile →
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Metadata -->
                    @if($activity['meta'])
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6 mt-6">
                            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-3">
                                Additional Details
                            </h4>
                            <dl class="grid grid-cols-1 gap-3">
                                @foreach($activity['meta'] as $key => $value)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                            {{ ucfirst(str_replace('_', ' ', $key)) }}
                                        </dt>
                                        <dd class="text-sm text-gray-900 dark:text-gray-100">
                                            {{ $value }}
                                        </dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
