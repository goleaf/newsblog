@php
    $items = [
        ['keys' => '/', 'label' => __('keyboard.search')],
        ['keys' => 'Esc', 'label' => __('keyboard.close_modal')],
        ['keys' => 'N', 'label' => __('keyboard.next_page')],
        ['keys' => 'P', 'label' => __('keyboard.previous_page')],
        ['keys' => '?', 'label' => __('keyboard.show_help')],
    ];
@endphp

<x-ui.modal id="shortcuts-help" :title="__('keyboard.shortcuts')" :showFooter="false">
    <div class="space-y-4">
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ __('keyboard.description') }}
        </p>
        <ul class="divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($items as $item)
                <li class="py-3 flex items-center justify-between">
                    <span class="text-gray-900 dark:text-gray-100">{{ $item['label'] }}</span>
                    <span class="inline-flex items-center gap-1">
                        @foreach(str_split($item['keys']) as $k)
                            @if($k === ' ')
                                <span class="w-2"></span>
                            @else
                                <kbd class="px-2 py-1 text-xs font-semibold rounded border border-gray-300 dark:border-gray-600 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">{{ $k }}</kbd>
                            @endif
                        @endforeach
                    </span>
                </li>
            @endforeach
        </ul>
    </div>

    @push('scripts')
    <script>
        // Allow opening via event if store isn't ready yet
        window.addEventListener('open-shortcuts-help', () => {
            if (window.Alpine && Alpine.store && Alpine.store('modal')) {
                Alpine.store('modal').open('shortcuts-help');
            }
        });
    </script>
    @endpush
</x-ui.modal>


