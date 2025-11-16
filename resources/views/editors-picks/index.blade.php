@extends('layouts.app', ['page' => 'editors-picks'])

@push('page-scripts')
    @if(app()->environment(['local','production']))
        <x-page-scripts page="editors-picks" />
    @endif
@endpush

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">{{ __("Editor's Picks Ordering") }}</h1>

    @if (session('success'))
        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900 p-4">
            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</p>
        </div>
    @endif

    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ __('Drag to reorder. Only published posts are listed. Top 6 are shown on the homepage.') }}</p>

    <form method="POST" action="{{ route('editors-picks.order') }}" x-data="EditorsPicks()">
        @csrf

        <ul id="pick-list" class="space-y-3">
            @foreach($picks as $post)
                <li class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 flex items-center gap-4 cursor-move" :data-id="{{ $post->id }}">
                    <div class="w-10 h-10 flex items-center justify-center rounded bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300 drag-handle">
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="currentColor"><path d="M7 4h2v2H7V4zm4 0h2v2h-2V4zM7 9h2v2H7V9zm4 0h2v2h-2V9zM7 14h2v2H7v-2zm4 0h2v2h-2v-2z"/></svg>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $post->title }}</div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $post->slug }}</div>
                    </div>
                    <input type="hidden" name="order[]" :value="{{ $post->id }}">
                </li>
            @endforeach
        </ul>

        <div class="mt-6">
            <button type="submit" class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">
                {{ __('Save Order') }}
            </button>
        </div>
    </form>
</div>
@endsection


