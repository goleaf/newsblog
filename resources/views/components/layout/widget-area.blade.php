@props([
    'slug',
    'class' => '',
])

@php
    $widgetArea = \App\Models\WidgetArea::where('slug', $slug)
        ->with('activeWidgets')
        ->first();
@endphp

@if($widgetArea && $widgetArea->activeWidgets->isNotEmpty())
<div {{ $attributes->merge(['class' => "widget-area widget-area-{$slug} {$class}"]) }}>
    @foreach($widgetArea->activeWidgets as $widget)
        @php
            $widgetService = app(\App\Services\WidgetService::class);
            $renderedWidget = $widgetService->render($widget);
        @endphp
        
        @if($renderedWidget)
            <div class="widget widget-{{ $widget->type }} mb-6 last:mb-0">
                {!! $renderedWidget !!}
            </div>
        @endif
    @endforeach
</div>
@endif
