@props(['widget'])

<div class="widget-box">
	<h3 class="text-base font-semibold text-gray-800 dark:text-gray-100 mb-3">{{ $widget->title }}</h3>
	<form method="GET" action="{{ route('search') }}" class="flex">
		<label class="sr-only" for="sidebar-search">{{ __('Search') }}</label>
		<input id="sidebar-search"
			   type="text"
			   name="q"
			   placeholder="{{ __('Search...') }}"
			   class="flex-1 px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-l-lg text-sm dark:bg-gray-700 dark:text-gray-100">
		<button type="submit"
				class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm rounded-r-lg">
			{{ __('Go') }}
		</button>
	</form>
</div>


