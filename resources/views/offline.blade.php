@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-3xl px-4 py-16">
	<h1 class="text-3xl font-bold mb-4">{{ __('You are offline') }}</h1>
	<p class="text-gray-600 dark:text-gray-300">
		{{ __('It looks like you lost your internet connection. Some features may be unavailable.') }}
	</p>
	<p class="mt-4">
		<a href="{{ url('/') }}" class="inline-flex items-center gap-2 rounded-md bg-sky-600 px-4 py-2 text-white hover:bg-sky-700">
			{{ __('Go to homepage') }}
		</a>
	</p>
	<div class="mt-8">
		<x-widgets.recent-posts />
	</div>
@endsection



