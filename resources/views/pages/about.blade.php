<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <article>
                        <header class="mb-8 text-center">
                            <h1 class="text-5xl font-bold mb-4">{{ $page->title }}</h1>
                            
                            @if($page->parent)
                                <nav class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    <a href="{{ route('page.show', $page->parent->slug) }}" class="hover:text-gray-900 dark:hover:text-gray-200">
                                        {{ $page->parent->title }}
                                    </a>
                                    <span class="mx-2">/</span>
                                    <span>{{ $page->title }}</span>
                                </nav>
                            @endif
                        </header>

                        <div class="prose dark:prose-invert max-w-none prose-lg">
                            {!! $page->content !!}
                        </div>

                        @if($page->children->count() > 0)
                            <div class="mt-12 pt-8 border-t border-gray-200 dark:border-gray-700">
                                <h2 class="text-2xl font-bold mb-6 text-center">Learn More</h2>
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                    @foreach($page->children as $child)
                                        <a href="{{ route('page.show', $child->slug) }}" 
                                           class="block p-6 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition text-center">
                                            <h3 class="font-semibold text-lg">{{ $child->title }}</h3>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </article>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
