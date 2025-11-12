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
                        <div class="overflow-hidden rounded-lg bg-white shadow-sm dark:bg-gray-800">
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

                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center rounded-full bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                        {{ $item->posts_count }} {{ Str::plural('post', $item->posts_count) }}
                                    </span>
                                    <a href="{{ route('series.show', $item->slug) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        View Series →
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $series->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
