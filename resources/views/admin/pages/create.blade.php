<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Create Page') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('admin.pages.store') }}" class="space-y-6">
                        @csrf

                        <div>
                            <label for="title" class="block text-sm font-medium mb-2">
                                Title <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="title" 
                                   name="title" 
                                   value="{{ old('title') }}"
                                   required
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('title')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="slug" class="block text-sm font-medium mb-2">
                                Slug <span class="text-gray-500 text-xs">(leave empty to auto-generate)</span>
                            </label>
                            <input type="text" 
                                   id="slug" 
                                   name="slug" 
                                   value="{{ old('slug') }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('slug')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="template" class="block text-sm font-medium mb-2">
                                    Template <span class="text-red-500">*</span>
                                </label>
                                <select id="template" 
                                        name="template" 
                                        required
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                    @foreach($templates as $value => $label)
                                        <option value="{{ $value }}" {{ old('template', 'default') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('template')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="status" class="block text-sm font-medium mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select id="status" 
                                        name="status" 
                                        required
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>Draft</option>
                                    <option value="published" {{ old('status') === 'published' ? 'selected' : '' }}>Published</option>
                                </select>
                                @error('status')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="parent_id" class="block text-sm font-medium mb-2">
                                    Parent Page
                                </label>
                                <select id="parent_id" 
                                        name="parent_id" 
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                    <option value="">None</option>
                                    @foreach($parentPages as $parentPage)
                                        <option value="{{ $parentPage->id }}" {{ old('parent_id') == $parentPage->id ? 'selected' : '' }}>
                                            {{ $parentPage->title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('parent_id')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="display_order" class="block text-sm font-medium mb-2">
                                    Display Order
                                </label>
                                <input type="number" 
                                       id="display_order" 
                                       name="display_order" 
                                       value="{{ old('display_order', 0) }}"
                                       min="0"
                                       class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                                @error('display_order')
                                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-medium mb-2">
                                Content <span class="text-red-500">*</span>
                            </label>
                            <textarea id="content" 
                                      name="content" 
                                      rows="15"
                                      required
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white font-mono text-sm">{{ old('content') }}</textarea>
                            @error('content')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="meta_title" class="block text-sm font-medium mb-2">
                                Meta Title
                            </label>
                            <input type="text" 
                                   id="meta_title" 
                                   name="meta_title" 
                                   value="{{ old('meta_title') }}"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">
                            @error('meta_title')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="meta_description" class="block text-sm font-medium mb-2">
                                Meta Description
                            </label>
                            <textarea id="meta_description" 
                                      name="meta_description" 
                                      rows="3"
                                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 dark:bg-gray-700 dark:text-white">{{ old('meta_description') }}</textarea>
                            @error('meta_description')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex gap-4">
                            <button type="submit" 
                                    class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                                Create Page
                            </button>
                            <a href="{{ route('admin.pages.index') }}" 
                               class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
