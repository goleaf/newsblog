@props([
    'widget',
    'content' => '',
])

<div class="bg-white dark:bg-gray-800 rounded-lg p-6">
    @if($widget->title)
    <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">
        {{ $widget->title }}
    </h3>
    @endif
    
    @if($content)
        <div class="prose prose-sm dark:prose-invert max-w-none">
            {!! strip_tags($content, '<p><a><strong><em><ul><ol><li><br><h1><h2><h3><h4><h5><h6><blockquote><code><pre>') !!}
        </div>
    @else
        <x-ui.empty-state 
            message="No content available"
            size="sm"
        />
    @endif
</div>
