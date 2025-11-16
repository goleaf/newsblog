@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Edit Article</h1>
        <p class="mt-2 text-gray-600 dark:text-gray-400">Update your article</p>
    </div>

    <form action="{{ route('articles.update', $article) }}" method="POST" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 rounded-lg shadow-md border border-gray-200 dark:border-gray-700 p-6">
        @csrf
        @method('PUT')

        <!-- Title -->
        <div class="mb-6">
            <x-input-label for="title" value="Title" />
            <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $article->title)" required autofocus />
            <x-input-error :messages="$errors->get('title')" class="mt-2" />
        </div>

        <!-- Slug -->
        <div class="mb-6">
            <x-input-label for="slug" value="Slug" />
            <x-text-input id="slug" name="slug" type="text" class="mt-1 block w-full" :value="old('slug', $article->slug)" />
            <x-input-error :messages="$errors->get('slug')" class="mt-2" />
        </div>

        <!-- Excerpt -->
        <div class="mb-6">
            <x-input-label for="excerpt" value="Excerpt" />
            <textarea id="excerpt" name="excerpt" rows="3" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('excerpt', $article->excerpt) }}</textarea>
            <x-input-error :messages="$errors->get('excerpt')" class="mt-2" />
        </div>

        <!-- Content -->
        <div class="mb-6">
            <x-input-label for="content" value="Content" />
            <textarea id="content" name="content" rows="15" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>{{ old('content', $article->content) }}</textarea>
            <x-input-error :messages="$errors->get('content')" class="mt-2" />
        </div>

        <!-- Current Featured Image -->
        @if($article->featured_image)
            <div class="mb-4">
                <x-input-label value="Current Featured Image" />
                <img src="{{ $article->featured_image_url }}" alt="{{ $article->title }}" class="mt-2 w-64 h-auto rounded-lg">
            </div>
        @endif

        <!-- Featured Image -->
        <div class="mb-6">
            <x-input-label for="featured_image" value="Featured Image (upload new to replace)" />
            <input id="featured_image" name="featured_image" type="file" accept="image/*" class="mt-1 block w-full text-sm text-gray-500 dark:text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 dark:file:bg-gray-700 dark:file:text-gray-300" />
            <x-input-error :messages="$errors->get('featured_image')" class="mt-2" />
        </div>

        <!-- Image Alt Text -->
        <div class="mb-6">
            <x-input-label for="image_alt_text" value="Image Alt Text" />
            <x-text-input id="image_alt_text" name="image_alt_text" type="text" class="mt-1 block w-full" :value="old('image_alt_text', $article->image_alt_text)" />
            <x-input-error :messages="$errors->get('image_alt_text')" class="mt-2" />
        </div>

        <!-- Category -->
        <div class="mb-6">
            <x-input-label for="category_id" value="Category" />
            <select id="category_id" name="category_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="">Select a category</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ old('category_id', $article->category_id) == $category->id ? 'selected' : '' }}>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
        </div>

        <!-- Tags -->
        <div class="mb-6">
            <x-input-label for="tags" value="Tags" />
            <select id="tags" name="tags[]" multiple class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach($tags as $tag)
                    <option value="{{ $tag->id }}" {{ in_array($tag->id, old('tags', $selectedTags)) ? 'selected' : '' }}>
                        {{ $tag->name }}
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('tags')" class="mt-2" />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Hold Ctrl/Cmd to select multiple tags</p>
        </div>

        <!-- Status -->
        <div class="mb-6">
            <x-input-label for="status" value="Status" />
            <select id="status" name="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                <option value="draft" {{ old('status', $article->status->value) == 'draft' ? 'selected' : '' }}>Draft</option>
                <option value="published" {{ old('status', $article->status->value) == 'published' ? 'selected' : '' }}>Published</option>
                <option value="scheduled" {{ old('status', $article->status->value) == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
            </select>
            <x-input-error :messages="$errors->get('status')" class="mt-2" />
        </div>

        <!-- Scheduled At (shown when status is scheduled) -->
        <div class="mb-6" id="scheduled_at_field" style="display: none;">
            <x-input-label for="scheduled_at" value="Scheduled Date & Time" />
            <x-text-input id="scheduled_at" name="scheduled_at" type="datetime-local" class="mt-1 block w-full" :value="old('scheduled_at', $article->scheduled_at?->format('Y-m-d\TH:i'))" />
            <x-input-error :messages="$errors->get('scheduled_at')" class="mt-2" />
        </div>

        <!-- Meta Title -->
        <div class="mb-6">
            <x-input-label for="meta_title" value="Meta Title (SEO)" />
            <x-text-input id="meta_title" name="meta_title" type="text" class="mt-1 block w-full" :value="old('meta_title', $article->meta_title)" maxlength="70" />
            <x-input-error :messages="$errors->get('meta_title')" class="mt-2" />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Max 70 characters for optimal SEO</p>
        </div>

        <!-- Meta Description -->
        <div class="mb-6">
            <x-input-label for="meta_description" value="Meta Description (SEO)" />
            <textarea id="meta_description" name="meta_description" rows="2" maxlength="160" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('meta_description', $article->meta_description) }}</textarea>
            <x-input-error :messages="$errors->get('meta_description')" class="mt-2" />
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Max 160 characters for optimal SEO</p>
        </div>

        <!-- Actions -->
        <div class="flex items-center justify-between">
            <div class="flex gap-4">
                <a href="{{ route('articles.show', $article->slug) }}" class="text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white">
                    Cancel
                </a>
                <a href="{{ route('articles.show', $article->slug) }}" target="_blank" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                    Preview
                </a>
            </div>
            <x-primary-button>
                Update Article
            </x-primary-button>
        </div>
    </form>

    <!-- Delete Article -->
    <div class="mt-8 bg-white dark:bg-gray-800 rounded-lg shadow-md border border-red-200 dark:border-red-700 p-6">
        <h2 class="text-lg font-semibold text-red-600 dark:text-red-400 mb-2">Danger Zone</h2>
        <p class="text-gray-600 dark:text-gray-400 mb-4">Once you delete an article, there is no going back. Please be certain.</p>
        <form action="{{ route('articles.destroy', $article) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this article?');">
            @csrf
            @method('DELETE')
            <x-danger-button type="submit">
                Delete Article
            </x-danger-button>
        </form>
    </div>
</div>

@push('scripts')
<script>
    // Show/hide scheduled_at field based on status
    document.getElementById('status').addEventListener('change', function() {
        const scheduledField = document.getElementById('scheduled_at_field');
        if (this.value === 'scheduled') {
            scheduledField.style.display = 'block';
        } else {
            scheduledField.style.display = 'none';
        }
    });

    // Trigger on page load
    document.getElementById('status').dispatchEvent(new Event('change'));
</script>
@endpush
@endsection
