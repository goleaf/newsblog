<?php

namespace App\View\Components;

use App\Services\WidgetService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class WidgetArea extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(
        public string $slug,
        protected WidgetService $widgetService
    ) {}

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $content = $this->widgetService->renderArea($this->slug);

        return view('components.widget-area', [
            'content' => $content,
        ]);
    }
}
