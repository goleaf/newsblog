<x-app-layout>
    <x-page-scripts page="admin-calendar" />
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
                {{ __('Content Calendar') }}
            </h2>
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-3 w-3 rounded-full bg-green-500"></span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Published') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-3 w-3 rounded-full bg-blue-500"></span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Scheduled') }}</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex h-3 w-3 rounded-full bg-gray-500"></span>
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ __('Draft') }}</span>
                </div>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                <div class="p-6">
                    <!-- Calendar Navigation -->
                    <div class="mb-6 flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <a href="{{ route('admin.calendar.index', ['month' => $date->copy()->subMonth()->month, 'year' => $date->copy()->subMonth()->year]) }}"
                               class="rounded-md bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                &larr; {{ __('Previous') }}
                            </a>
                            <h3 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                {{ $date->format('F Y') }}
                            </h3>
                            <a href="{{ route('admin.calendar.index', ['month' => $date->copy()->addMonth()->month, 'year' => $date->copy()->addMonth()->year]) }}"
                               class="rounded-md bg-gray-200 px-4 py-2 text-gray-700 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-300 dark:hover:bg-gray-600">
                                {{ __('Next') }} &rarr;
                            </a>
                        </div>
                        <div>
                            <input type="month"
                                   id="monthPicker"
                                   value="{{ $date->format('Y-m') }}"
                                   class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                   onchange="window.location.href = '{{ route('admin.calendar.index') }}?month=' + this.value.split('-')[1] + '&year=' + this.value.split('-')[0]">
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div x-data="contentCalendar()" class="grid grid-cols-7 gap-2">
                        <!-- Day Headers -->
                        @foreach([__('Sun'), __('Mon'), __('Tue'), __('Wed'), __('Thu'), __('Fri'), __('Sat')] as $day)
                            <div class="p-2 text-center text-sm font-semibold text-gray-700 dark:text-gray-300">
                                {{ $day }}
                            </div>
                        @endforeach

                        <!-- Calendar Days -->
                        @php
                            $startOfMonth = $date->copy()->startOfMonth();
                            $endOfMonth = $date->copy()->endOfMonth();
                            $startDay = $startOfMonth->dayOfWeek;
                            $daysInMonth = $date->daysInMonth;
                            
                            // Add empty cells for days before the month starts
                            $totalCells = $startDay + $daysInMonth;
                            $rows = ceil($totalCells / 7);
                        @endphp

                        @for($i = 0; $i < $startDay; $i++)
                            <div class="min-h-32 rounded-lg border border-gray-200 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-900"></div>
                        @endfor

                        @for($day = 1; $day <= $daysInMonth; $day++)
                            @php
                                $currentDate = $date->copy()->day($day);
                                $dateKey = $currentDate->format('Y-m-d');
                                $dayPosts = $posts->get($dateKey, collect());
                                $isToday = $currentDate->isToday();
                            @endphp
                            <div class="min-h-32 rounded-lg border p-2 {{ $isToday ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-800' }}"
                                 data-date="{{ $dateKey }}"
                                 @drop.prevent="handleDrop($event, '{{ $dateKey }}')"
                                 @dragover.prevent
                                 @click="showPostsForDate('{{ $dateKey }}')">
                                <div class="mb-2 text-sm font-semibold text-gray-700 dark:text-gray-300">
                                    {{ $day }}
                                </div>
                                <div class="space-y-1">
                                    @foreach($dayPosts as $post)
                                        <div draggable="true"
                                             @dragstart="handleDragStart($event, {{ $post->id }})"
                                             class="cursor-move rounded px-2 py-1 text-xs {{ $post->status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : ($post->status === 'scheduled' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200') }}"
                                             title="{{ $post->title }}">
                                            {{ \Illuminate\Support\Str::limit($post->title, 20) }}
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endfor

                        <!-- Fill remaining cells -->
                        @php
                            $remainingCells = ($rows * 7) - $totalCells;
                        @endphp
                        @for($i = 0; $i < $remainingCells; $i++)
                            <div class="min-h-32 rounded-lg border border-gray-200 bg-gray-50 p-2 dark:border-gray-700 dark:bg-gray-900"></div>
                        @endfor
                    </div>
                </div>
            </div>

            <!-- Sidebar for selected date posts -->
            <div x-data="{ open: false, posts: [], selectedDate: '' }"
                 x-show="open"
                 @show-posts.window="open = true; posts = $event.detail.posts; selectedDate = $event.detail.date"
                 @close-sidebar.window="open = false"
                 x-cloak
                 class="fixed inset-y-0 right-0 z-50 w-96 transform bg-white shadow-xl transition-transform dark:bg-gray-800"
                 :class="{ 'translate-x-0': open, 'translate-x-full': !open }">
                <div class="flex h-full flex-col">
                    <div class="flex items-center justify-between border-b border-gray-200 p-4 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                            {{ __('Posts for') }} <span x-text="selectedDate"></span>
                        </h3>
                        <button @click="open = false" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4">
                        <template x-if="posts.length === 0">
                            <p class="text-gray-500 dark:text-gray-400">{{ __('No posts scheduled for this date.') }}</p>
                        </template>
                        <div class="space-y-3">
                            <template x-for="post in posts" :key="post.id">
                                <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100" x-text="post.title"></h4>
                                    <div class="mt-2 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                                        <p><span class="font-medium">{{ __('Status:') }}</span> <span :class="{
                                            'text-green-600 dark:text-green-400': post.status === 'published',
                                            'text-blue-600 dark:text-blue-400': post.status === 'scheduled',
                                            'text-gray-600 dark:text-gray-400': post.status === 'draft'
                                        }" x-text="post.status"></span></p>
                                        <p><span class="font-medium">{{ __('Author:') }}</span> <span x-text="post.author"></span></p>
                                        <p><span class="font-medium">{{ __('Category:') }}</span> <span x-text="post.category"></span></p>
                                        <template x-if="post.published_at">
                                            <p><span class="font-medium">{{ __('Published:') }}</span> <span x-text="post.published_at"></span></p>
                                        </template>
                                        <template x-if="post.scheduled_at">
                                            <p><span class="font-medium">{{ __('Scheduled:') }}</span> <span x-text="post.scheduled_at"></span></p>
                                        </template>
                                    </div>
                                    <div class="mt-3">
                                        <a :href="post.edit_url" class="text-sm text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                            {{ __('Edit Post →') }}
                                        </a>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Overlay -->
            <div x-data="{ open: false }"
                 @show-posts.window="open = true"
                 @close-sidebar.window="open = false"
                 x-show="open"
                 @click="$dispatch('close-sidebar')"
                 x-cloak
                 class="fixed inset-0 z-40 bg-black bg-opacity-50"></div>
        </div>
    </div>
</x-app-layout>
