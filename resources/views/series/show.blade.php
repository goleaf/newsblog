<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ $series->name }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="seriesProgress({{ $series->id }}, {{ json_encode($series->posts->pluck('id')) }})">
        <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
            <!-- Series Header -->
            <div class="mb-6 overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <h1 class="mb-4 text-3xl font-bold">{{ $series->name }}</h1>
                    
                    @if($series->description)
                        <p class="mb-6 text-lg text-gray-600 dark:text-gray-400">
                            {{ $series->description }}
                        </p>
                    @endif

                    <!-- Series Stats -->
                    <div class="mb-6 flex flex-wrap items-center gap-4 text-sm text-gray-500 dark:text-gray-400">
                        <span class="inline-flex items-center gap-1">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            {{ $series->posts->count() }} {{ Str::plural('article', $series->posts->count()) }}
                        </span>
                        
                        @if($totalReadingTime > 0)
                            <span>•</span>
                            <span class="inline-flex items-center gap-1">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                {{ $totalReadingTime }} min total reading time
                            </span>
                        @endif
                        
                        <span>•</span>
                        <span>Updated {{ $series->updated_at->diffForHumans() }}</span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-700 dark:text-gray-300">Your Progress</span>
                            <span class="text-gray-600 dark:text-gray-400">
                                <span x-text="readPosts.length"></span> of {{ $series->posts->count() }} articles
                                (<span x-text="completionPercentage"></span>%)
                            </span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                            <div 
                                class="h-full rounded-full bg-indigo-600 transition-all duration-300 dark:bg-indigo-500"
                                :style="`width: ${completionPercentage}%`"
                            ></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Completion Badge -->
            <div class="mb-6">
                <x-series.completion-badge 
                    :series-id="$series->id"
                    :series-name="$series->name"
                    :related-series="$relatedSeries"
                />
            </div>

            @if($series->posts->isEmpty())
                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <p class="text-gray-600 dark:text-gray-400">No posts in this series yet.</p>
                    </div>
                </div>
            @else
                <!-- Articles List -->
                <div class="space-y-4">
                    @foreach($series->posts as $post)
                        <article 
                            class="group overflow-hidden rounded-lg bg-white shadow-sm transition-shadow hover:shadow-md dark:bg-gray-800"
                            x-data="{ isRead: readPosts.includes({{ $post->id }}) }"
                            data-post-id="{{ $post->id }}"
                        >
                            <div class="p-6">
                                <div class="mb-4 flex items-start gap-4">
                                    <!-- Order Number with Read Indicator -->
                                    <div class="relative flex-shrink-0">
                                        <span 
                                            class="flex h-10 w-10 items-center justify-center rounded-full text-lg font-bold transition-colors"
                                            :class="isRead ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200'"
                                        >
                                            {{ $post->pivot->order + 1 }}
                                        </span>
                                        <!-- Checkmark for read articles -->
                                        <span 
                                            x-show="isRead"
                                            class="absolute -right-1 -top-1 flex h-5 w-5 items-center justify-center rounded-full bg-green-500 text-white"
                                        >
                                            <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                            </svg>
                                        </span>
                                    </div>
                                    
                                    <div class="flex-1">
                                        <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-gray-100">
                                            <a href="{{ route('post.show', $post->slug) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                                {{ $post->title }}
                                            </a>
                                        </h3>
                                        
                                        @if($post->excerpt)
                                            <p class="mb-3 text-gray-600 dark:text-gray-400">
                                                {{ $post->excerpt }}
                                            </p>
                                        @endif

                                        <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                            <span>by {{ $post->user->name }}</span>
                                            <span>•</span>
                                            <span class="inline-flex items-center gap-1 rounded-full bg-gray-100 px-2 py-0.5 dark:bg-gray-700">
                                                {{ $post->category->name }}
                                            </span>
                                            @if($post->published_at)
                                                <span>•</span>
                                                <span>{{ $post->published_at->format('M d, Y') }}</span>
                                            @endif
                                            @if($post->reading_time)
                                                <span>•</span>
                                                <span class="inline-flex items-center gap-1">
                                                    <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                    {{ $post->reading_time }} min
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center justify-between">
                                    <!-- Mark as Read Toggle -->
                                    <button 
                                        @click="toggleRead({{ $post->id }})"
                                        class="inline-flex items-center gap-1 text-sm transition-colors"
                                        :class="isRead ? 'text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300'"
                                    >
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        <span x-text="isRead ? 'Mark as unread' : 'Mark as read'"></span>
                                    </button>

                                    <a 
                                        href="{{ route('post.show', $post->slug) }}" 
                                        class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300"
                                    >
                                        Read Article
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </div>
    </div>


</x-app-layout>
