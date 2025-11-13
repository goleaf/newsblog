<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <article>
                        <header class="mb-8">
                            <h1 class="text-4xl font-bold mb-4">{{ $page->title }}</h1>
                        </header>

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div>
                                <div class="prose dark:prose-invert max-w-none mb-8">
                                    {!! $page->content !!}
                                </div>
                            </div>

                            <div>
                                @if(session('success'))
                                    <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 rounded-lg">
                                        {{ session('success') }}
                                    </div>
                                @endif

                                <form method="POST" action="{{ route('page.contact.submit') }}" class="space-y-6">
                                    @csrf

                                    <div>
                                        <label for="name" class="block text-sm font-medium mb-2">
                                            Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" 
                                               id="name" 
                                               name="name" 
                                               value="{{ old('name') }}"
                                               required
                                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                        @error('name')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="email" class="block text-sm font-medium mb-2">
                                            Email <span class="text-red-500">*</span>
                                        </label>
                                        <input type="email" 
                                               id="email" 
                                               name="email" 
                                               value="{{ old('email') }}"
                                               required
                                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                        @error('email')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="subject" class="block text-sm font-medium mb-2">
                                            Subject <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" 
                                               id="subject" 
                                               name="subject" 
                                               value="{{ old('subject') }}"
                                               required
                                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                        @error('subject')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="message" class="block text-sm font-medium mb-2">
                                            Message <span class="text-red-500">*</span>
                                        </label>
                                        <textarea id="message" 
                                                  name="message" 
                                                  rows="6"
                                                  required
                                                  class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">{{ old('message') }}</textarea>
                                        @error('message')
                                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <button type="submit" 
                                            class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                                        Send Message
                                    </button>
                                </form>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
