<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Category Details') }}: {{ $category->name }}
            </h2>
            <div class="flex gap-2">
                <a href="{{ route('category.show', $category->slug) }}" 
                   target="_blank"
                   class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition">
                    View Public Page
                </a>
                <a href="{{ route('admin.categories.edit', $category) }}" 
                   class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition">
                    Edit
                </a>
                <a href="{{ route('admin.categories.index') }}" 
                   class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition">
                    Back
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Category Details -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Basic Information</h3>
                            
                            <div class="space-y-4">
                                <div class="flex items-start gap-4">
                                    @if($category->icon)
                                        <div class="w-16 h-16 rounded-lg flex items-center justify-center text-3xl flex-shrink-0" 
                                             style="background-color: {{ $category->color_code ?? '#6366f1' }}20;">
                                            {{ $category->icon }}
                                        </div>
                                    @endif
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <h4 class="text-2xl font-bold text-gray-900 dark:text-white">
                                                {{ $category->name }}
                                            </h4>
                                            <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                                {{ $category->status === 'active' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-gray-100 text-gray-800 dark:bg-gray-600 dark:text-gray-200' }}">
                                                {{ ucfirst($category->status) }}
                                            </span>
                                        </div>
                                        @if($category->description)
                                            <p class="text-gray-600 dark:text-gray-300">
                                                {{ $category->description }}
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Slug</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $category->slug }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Display Order</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $category->display_order ?? 0 }}</p>
                                    </div>
                                    @if($category->parent)
                                        <div>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">Parent Category</p>
                                            <a href="{{ route('admin.categories.show', $category->parent) }}" 
                                               class="text-sm font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400">
                                                {{ $category->parent->name }}
                                            </a>
                                        </div>
                                    @endif
                                    <div>
                                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Posts</p>
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $category->posts_count ?? 0 }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Subcategories -->
                    @if($category->children->isNotEmpty())
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Subcategories</h3>
                                
                                <div class="space-y-2">
                                    @foreach($category->children as $child)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="flex items-center gap-3">
                                                @if($child->icon)
                                                    <span class="text-2xl">{{ $child->icon }}</span>
                                                @endif
                                                <div>
                                                    <p class="font-medium text-gray-900 dark:text-white">{{ $child->name }}</p>
                                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                                        {{ $child->posts_count ?? 0 }} {{ Str::plural('post', $child->posts_count ?? 0) }}
                                                    </p>
                                                </div>
                                            </div>
                                            <a href="{{ route('admin.categories.show', $child) }}" 
                                               class="px-3 py-1.5 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition">
                                                View
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Recent Posts -->
                    @if($recentPosts->isNotEmpty())
                        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Recent Posts</h3>
                                
                                <div class="space-y-3">
                                    @foreach($recentPosts as $post)
                                        <div class="flex items-start justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                            <div class="flex-1">
                                                <a href="{{ route('post.show', $post->slug) }}" 
                                                   target="_blank"
                                                   class="font-medium text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
                                                    {{ $post->title }}
                                                </a>
                                                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                                    By {{ $post->user->name }} • {{ $post->published_at->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- SEO Information -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">SEO Information</h3>
                            
                            <div class="space-y-3">
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Meta Title</p>
                                    <p class="text-sm text-gray-900 dark:text-white">
                                        {{ $category->meta_title ?: 'Not set (using default)' }}
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-1">Meta Description</p>
                                    <p class="text-sm text-gray-900 dark:text-white">
                                        {{ $category->meta_description ?: 'Not set (using default)' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Statistics</h3>
                            
                            <div class="space-y-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Total Posts</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $category->posts_count ?? 0 }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Subcategories</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $category->children->count() }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Created</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $category->created_at->format('M d, Y') }}</span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-500 dark:text-gray-400">Last Updated</span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $category->updated_at->diffForHumans() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Actions</h3>
                            
                            <div class="space-y-2">
                                <a href="{{ route('category.show', $category->slug) }}" 
                                   target="_blank"
                                   class="block w-full px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-center rounded-lg transition">
                                    View Public Page
                                </a>
                                <a href="{{ route('admin.categories.edit', $category) }}" 
                                   class="block w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-center rounded-lg transition">
                                    Edit Category
                                </a>
                                <form method="POST" 
                                      action="{{ route('admin.categories.destroy', $category) }}" 
                                      onsubmit="return confirm('Are you sure you want to delete this category? This action cannot be undone.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="block w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-center rounded-lg transition">
                                        Delete Category
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
