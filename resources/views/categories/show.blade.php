@extends('layouts.app')

@section('title', $category->meta_title ?? $category->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900 dark:text-white">{{ $category->name }}</h1>
        @if($category->description)
            <p class="mt-4 text-lg text-gray-600 dark:text-gray-300">{{ $category->description }}</p>
        @endif
    </div>

    <!-- Post Filters Component (Requirements 26.1-26.5) -->
    <x-post-filters :current-url="route('category.show', $category->slug)" />

    <!-- Infinite Scroll Component (Requirements 27.1-27.5) -->
    <x-infinite-scroll :posts="$posts">
        @foreach($posts as $post)
            <div data-post-item>
                @include('partials.post-card', ['post' => $post])
            </div>
        @endforeach
    </x-infinite-scroll>
</div>
@endsection

