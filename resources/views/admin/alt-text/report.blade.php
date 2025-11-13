<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Accessibility Report - Image Alt Text') }}
            </h2>
            <a href="{{ route('admin.alt-text.bulk-edit') }}" class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition ease-in-out duration-150">
                Bulk Edit Alt Text
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Published Posts</div>
                        <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-gray-100">
                            {{ $summary['total_published_posts'] }}
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Posts with Issues</div>
                        <div class="mt-2 text-3xl font-bold text-red-600 dark:text-red-400">
                            {{ $summary['posts_with_issues'] }}
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Compliance Rate</div>
                        <div class="mt-2 text-3xl font-bold text-green-600 dark:text-green-400">
                            {{ $summary['compliance_rate'] }}%
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Media Without Alt</div>
                        <div class="mt-2 text-3xl font-bold text-orange-600 dark:text-orange-400">
                            {{ $summary['media_without_alt'] }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Posts with Issues -->
            @if($postsWithIssues->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-8">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">
                            Posts with Missing Alt Text
                        </h3>

                        <div class="space-y-4">
                            @foreach($postsWithIssues as $item)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                                    <div class="flex justify-between items-start mb-2">
                                        <div>
                                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $item['post']->title }}
                                            </h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                By {{ $item['post']->user->name }} • {{ $item['post']->formatted_date }}
                                            </p>
                                        </div>
                                        <a href="/nova/resources/posts/{{ $item['post']->id }}/edit" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                                            Edit Post
                                        </a>
                                    </div>

                                    <div class="mt-3 space-y-2">
                                        @foreach($item['issues'] as $issue)
                                            <div class="flex items-start gap-2 text-sm">
                                                <svg class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                                <span class="text-gray-700 dark:text-gray-300">
                                                    {{ $issue['message'] }}
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6 text-center">
                    <svg class="w-12 h-12 text-green-600 dark:text-green-400 mx-auto mb-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <h3 class="text-lg font-semibold text-green-800 dark:text-green-200 mb-2">
                        All Posts Are Compliant!
                    </h3>
                    <p class="text-green-700 dark:text-green-300">
                        All published posts have proper alt text for images.
                    </p>
                </div>
            @endif

            <!-- Media Without Alt Text -->
            @if($mediaWithoutAlt->count() > 0)
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                                Media Library Images Without Alt Text
                            </h3>
                            <a href="{{ route('admin.alt-text.bulk-edit') }}" class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 text-sm">
                                Bulk Edit →
                            </a>
                        </div>

                        <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                            Showing first 10 images. Use bulk edit to update all images.
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                            @foreach($mediaWithoutAlt->take(10) as $media)
                                <div class="border border-gray-200 dark:border-gray-700 rounded-lg overflow-hidden">
                                    <img src="{{ $media->thumbnail_url }}" alt="" class="w-full h-32 object-cover">
                                    <div class="p-2">
                                        <p class="text-xs text-gray-600 dark:text-gray-400 truncate">
                                            {{ $media->file_name }}
                                        </p>
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
