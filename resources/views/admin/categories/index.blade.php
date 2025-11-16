<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Categories') }}
            </h2>
            <a href="{{ route('admin.categories.create') }}" 
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                Create Category
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-6 p-4 bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 p-4 bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <!-- Filters -->
                    <form method="GET" action="{{ route('admin.categories.index') }}" class="mb-6 flex gap-4">
                        <div class="flex-1">
                            <input type="text" 
                                   name="search" 
                                   value="{{ request('search') }}"
                                   placeholder="Search categories..."
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                        </div>
                        <div>
                            <select name="status" 
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                        <button type="submit" 
                                class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                            Filter
                        </button>
                        @if(request('search') || request('status'))
                            <a href="{{ route('admin.categories.index') }}" 
                               class="px-4 py-2 bg-gray-300 hover:bg-gray-400 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded-lg transition">
                                Clear
                            </a>
                        @endif
                    </form>

                    <!-- Categories List -->
                    @if($categories->isNotEmpty())
                        <div class="space-y-2">
                            @foreach($categories as $category)
                                <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-4 flex-1">
                                            @if($category->icon)
                                                <div class="w-10 h-10 rounded-lg flex items-center justify-center text-xl" 
                                                     style="background-color: {{ $category->color_code ?? '#6366f1' }}20;">
                                                    {{ $category->icon }}
                                                </div>
                                            @endif
                                            
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-semibold text-gray-900 dark:text-white">
                                                        {{ $category->name }}
                                                    </span>
                                                    <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                                        {{ $category->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200' }}">
                                                        {{ ucfirst($category->status) }}
                                                    </span>
                                                </div>
                                                <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                    {{ $category->posts_count ?? 0 }} {{ Str::plural('post', $category->posts_count ?? 0) }}
                                                    @if($category->description)
                                                        • {{ Str::limit($category->description, 60) }}
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <a href="{{ route('admin.categories.show', $category) }}" 
                                               class="px-3 py-1.5 text-sm bg-gray-200 hover:bg-gray-300 dark:bg-gray-600 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded transition">
                                                View
                                            </a>
                                            <a href="{{ route('admin.categories.edit', $category) }}" 
                                               class="px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition">
                                                Edit
                                            </a>
                                            <form method="POST" 
                                                  action="{{ route('admin.categories.destroy', $category) }}" 
                                                  onsubmit="return confirm('Are you sure you want to delete this category?');"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="px-3 py-1.5 text-sm bg-red-600 hover:bg-red-700 text-white rounded transition">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>

                                    <!-- Subcategories -->
                                    @if($category->children->isNotEmpty())
                                        <div class="mt-3 ml-14 space-y-2">
                                            @foreach($category->children as $child)
                                                <div class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 rounded">
                                                    <div class="flex items-center gap-3 flex-1">
                                                        @if($child->icon)
                                                            <span class="text-lg">{{ $child->icon }}</span>
                                                        @endif
                                                        <div class="flex-1">
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-sm font-medium text-gray-900 dark:text-white">
                                                                    {{ $child->name }}
                                                                </span>
                                                                <span class="text-xs text-gray-500 dark:text-gray-400">
                                                                    ({{ $child->posts_count ?? 0 }} posts)
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="flex items-center gap-2">
                                                        <a href="{{ route('admin.categories.edit', $child) }}" 
                                                           class="px-2 py-1 text-xs bg-blue-600 hover:bg-blue-700 text-white rounded transition">
                                                            Edit
                                                        </a>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No categories found</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                Get started by creating a new category.
                            </p>
                            <div class="mt-6">
                                <a href="{{ route('admin.categories.create') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                                    Create Category
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
