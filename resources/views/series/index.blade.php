<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-200">
            {{ __('All Series') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
            @if($series->isEmpty())
                <div class="overflow-hidden bg-white shadow-sm dark:bg-gray-800 sm:rounded-lg">
                    <div class="p-6 text-gray-900 dark:text-gray-100">
                        <p class="text-gray-600 dark:text-gray-400">No series available yet.</p>
                    </div>
                </div>
            @else
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    @foreach($series as $item)
                        <article class="group overflow-hidden rounded-lg bg-white shadow-sm transition-shadow hover:shadow-md dark:bg-gray-800">
                            @if($item->thumbnail)
                                <a href="{{ route('series.show', $item->slug) }}" class="block">
                                    <div class="aspect-video overflow-hidden bg-gray-200 dark:bg-gray-700">
                                        <img 
                                            src="{{ $item->thumbnail }}" 
                                            alt="{{ $item->name }}"
                                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
                                            loading="lazy"
                                        />
                                    </div>
                                </a>
                            @endif
                            
                            <div class="p-6">
                                <h3 class="mb-2 text-xl font-semibold text-gray-900 dark:text-gray-100">
                                    <a href="{{ route('series.show', $item->slug) }}" class="hover:text-indigo-600 dark:hover:text-indigo-400">
                                        {{ $item->name }}
                                    </a>
                                </h3>
                                
                                @if($item->description)
                                    <p class="mb-4 text-gray-600 dark:text-gray-400">
                                        {{ Str::limit($item->description, 150) }}
                                    </p>
                                @endif

                                <div class="flex flex-wrap items-center gap-3 text-sm text-gray-500 dark:text-gray-400">
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                        </svg>
                                        {{ $item->posts_count }} {{ Str::plural('article', $item->posts_count) }}
                                    </span>
                                    
                                    @php
                                        $totalReading = $item->total_reading_time 
                                            ?? $item->posts()->where('status', 'published')->sum('reading_time');
                                    @endphp
                                    <span>•</span>
                                    <span class="inline-flex items-center gap-1">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        {{ (int) $totalReading }} min total
                                    </span>
                                </div>

                                <div class="mt-4 flex justify-end">
                                    <a href="{{ route('series.show', $item->slug) }}" class="inline-flex items-center gap-1 text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        View Series
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $series->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
