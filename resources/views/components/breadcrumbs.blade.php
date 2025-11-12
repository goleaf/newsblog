@props(['breadcrumbs' => [], 'structuredData' => null])

@if(count($breadcrumbs) > 1)
<nav aria-label="Breadcrumb" class="mb-4">
    <ol class="flex flex-wrap items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
        @foreach($breadcrumbs as $index => $crumb)
            <li class="flex items-center">
                @if($index > 0)
                    <svg class="w-4 h-4 mx-2 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                    </svg>
                @endif

                @if($crumb['url'])
                    <a href="{{ $crumb['url'] }}" 
                       class="hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-150 truncate max-w-[150px] sm:max-w-none"
                       title="{{ $crumb['title'] }}">
                        {{ $crumb['title'] }}
                    </a>
                @else
                    <span class="text-gray-900 dark:text-gray-100 font-medium truncate max-w-[150px] sm:max-w-none" 
                          title="{{ $crumb['title'] }}">
                        {{ $crumb['title'] }}
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

{{-- Schema.org Structured Data --}}
@if($structuredData)
    <script type="application/ld+json">
        {!! $structuredData !!}
    </script>
@endif
@endif
