@props([
    'type' => 'featured', // featured, trending, ai-generated, sponsored, editors-pick
    'rank' => null, // For trending badge rank
])

@php
    $badgeConfig = [
        'featured' => [
            'text' => 'Featured',
            'icon' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>',
            'bgClass' => 'bg-yellow-500 dark:bg-yellow-600',
            'textClass' => 'text-white',
        ],
        'trending' => [
            'text' => $rank ? "#$rank Trending" : 'Trending',
            'icon' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.99 2.99 0 0113 13a2.99 2.99 0 01-.879 2.121z" clip-rule="evenodd" /></svg>',
            'bgClass' => 'bg-red-500 dark:bg-red-600',
            'textClass' => 'text-white',
        ],
        'ai-generated' => [
            'text' => 'AI Generated',
            'icon' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M11.3 1.046A1 1 0 0112 2v5h4a1 1 0 01.82 1.573l-7 10A1 1 0 018 18v-5H4a1 1 0 01-.82-1.573l7-10a1 1 0 011.12-.38z" clip-rule="evenodd" /></svg>',
            'bgClass' => 'bg-purple-500 dark:bg-purple-600',
            'textClass' => 'text-white',
        ],
        'sponsored' => [
            'text' => 'Sponsored',
            'icon' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v1l-8 4-8-4V5z" /><path d="M18 8.118l-8 4-8-4V15a2 2 0 002 2h12a2 2 0 002-2V8.118z"/></svg>',
            'bgClass' => 'bg-amber-600 dark:bg-amber-700',
            'textClass' => 'text-white',
        ],
        'editors-pick' => [
            'text' => "Editor's Pick",
            'icon' => '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M10 2l2.39 4.84L18 7.27l-4 3.9.94 5.48L10 14.77 5.06 16.65 6 11.17 2 7.27l5.61-.43L10 2z"/></svg>',
            'bgClass' => 'bg-sky-600 dark:bg-sky-700',
            'textClass' => 'text-white',
        ],
    ];
    
    $config = $badgeConfig[$type] ?? $badgeConfig['featured'];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-md {$config['bgClass']} {$config['textClass']}"]) }}>
    {!! $config['icon'] !!}
    <span>{{ $config['text'] }}</span>
</span>
