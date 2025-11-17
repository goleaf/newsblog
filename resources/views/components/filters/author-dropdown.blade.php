@props(['authors', 'selected' => null])

<div class="mb-4">
    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
        Author
    </label>
    <select
        name="author"
        x-model="filters.author"
        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
    >
        <option value="">All Authors</option>
        @foreach($authors as $author)
            <option value="{{ $author->id }}" {{ $selected == $author->id ? 'selected' : '' }}>
                {{ $author->name }}
            </option>
        @endforeach
    </select>
</div>
