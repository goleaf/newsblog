@props([
    'slug',
    'class' => '',
])

@php
    $widgetArea = \Illuminate\Support\Facades\Cache::remember("widget-area:{$slug}", 600, function () use ($slug) {
        return \App\Models\WidgetArea::where('slug', $slug)
            ->with('activeWidgets')
            ->first();
    });
@endphp

@if($widgetArea && $widgetArea->activeWidgets->isNotEmpty())
<div {{ $attributes->merge(['class' => "widget-area widget-area-{$slug} {$class}"]) }}>
    @foreach($widgetArea->activeWidgets as $widget)
        @php
            $renderedWidget = \Illuminate\Support\Facades\Cache::remember("widget:render:{$widget->id}:".optional($widget->updated_at)->timestamp, 600, function () use ($widget) {
                $widgetService = app(\App\Services\WidgetService::class);
                return $widgetService->render($widget);
            });
        @endphp
        
        @if($renderedWidget)
            <div class="widget widget-{{ $widget->type }} mb-6 last:mb-0">
                {!! $renderedWidget !!}
            </div>
        @endif
    @endforeach
</div>
@endif
