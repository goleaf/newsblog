@extends('layouts.app')

@section('title', 'Edit Reading List')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Reading List</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Update your reading list details</p>
    </div>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
        <form method="POST" action="{{ route('reading-lists.update', $collection) }}">
            @csrf
            @method('PUT')

            <!-- Name -->
            <div class="mb-6">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    List Name <span class="text-red-500">*</span>
                </label>
                <input 
                    type="text" 
                    name="name" 
                    id="name" 
                    value="{{ old('name', $collection->name) }}"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                    required
                    autofocus
                >
                @error('name')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Description -->
            <div class="mb-6">
                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Description
                </label>
                <textarea 
                    name="description" 
                    id="description" 
                    rows="3"
                    class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                >{{ old('description', $collection->description) }}</textarea>
                @error('description')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Privacy Setting -->
            <div class="mb-6">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Privacy
                </label>
                <div class="space-y-3">
                    <label class="flex items-start p-4 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <input 
                            type="radio" 
                            name="is_public" 
                            value="0" 
                            {{ old('is_public', $collection->is_public ? '1' : '0') == '0' ? 'checked' : '' }}
                            class="mt-1 text-blue-600 focus:ring-blue-500"
                        >
                        <div class="ml-3">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-medium text-gray-900 dark:text-white">Private</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Only you can see this reading list
                            </p>
                        </div>
                    </label>

                    <label class="flex items-start p-4 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <input 
                            type="radio" 
                            name="is_public" 
                            value="1" 
                            {{ old('is_public', $collection->is_public ? '1' : '0') == '1' ? 'checked' : '' }}
                            class="mt-1 text-blue-600 focus:ring-blue-500"
                        >
                        <div class="ml-3">
                            <div class="flex items-center">
                                <svg class="w-4 h-4 mr-1 text-gray-600 dark:text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                    <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                </svg>
                                <span class="font-medium text-gray-900 dark:text-white">Public</span>
                            </div>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                                Anyone with the link can view this reading list
                            </p>
                        </div>
                    </label>
                </div>
                @error('is_public')
                    <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Actions -->
            <div class="flex items-center justify-between pt-4 border-t border-gray-200 dark:border-gray-700">
                <button 
                    type="button"
                    onclick="if(confirm('Are you sure you want to delete this reading list? Articles will remain in your bookmarks.')) { document.getElementById('delete-form').submit(); }"
                    class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:text-red-700 dark:hover:text-red-300"
                >
                    Delete List
                </button>
                <div class="flex items-center gap-3">
                    <a href="{{ route('reading-lists.show', $collection) }}" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                        Cancel
                    </a>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md transition-colors">
                        Save Changes
                    </button>
                </div>
            </div>
        </form>

        <!-- Delete Form -->
        <form id="delete-form" method="POST" action="{{ route('reading-lists.destroy', $collection) }}" class="hidden">
            @csrf
            @method('DELETE')
        </form>
    </div>
</div>
@endsection
