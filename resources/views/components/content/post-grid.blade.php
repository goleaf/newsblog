@props([
    'posts',
    'columns' => 3, // 1, 2, 3, 4
    'gap' => 6, // Tailwind gap value (4, 6, 8)
    'cardSize' => 'default', // small, default, large
    'showExcerpt' => true,
    'showImage' => true,
])

@php
    $columnClasses = [
        1 => 'grid-cols-1',
        2 => 'grid-cols-1 md:grid-cols-2',
        3 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3',
        4 => 'grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4',
    ];
    
    $columnClass = $columnClasses[$columns] ?? $columnClasses[3];
    $gapClass = "gap-{$gap}";
@endphp

<div {{ $attributes->merge(['class' => "grid {$columnClass} {$gapClass}"]) }}>
    @forelse($posts as $post)
        <x-content.post-card 
            :post="$post"
            :size="$cardSize"
            :show-excerpt="$showExcerpt"
            :show-image="$showImage"
        />
    @empty
        <div class="col-span-full">
            <x-ui.empty-state
                title="No posts found"
                description="There are no posts to display at the moment."
            >
                <x-slot:icon>
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </x-slot:icon>
            </x-ui.empty-state>
        </div>
    @endforelse
</div>
