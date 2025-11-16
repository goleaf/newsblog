<?php

namespace App\Nova\Actions;

use App\Models\Page;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Http\Requests\NovaRequest;

class PageMoveUp extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = 'Move Up';

    public function handle(ActionFields $fields, \Illuminate\Support\Collection $models)
    {
        /** @var Page $page */
        foreach ($models as $page) {
            $newOrder = max(0, ($page->display_order ?? 0) - 1);
            $page->update(['display_order' => $newOrder]);
        }

        return Action::message(__('Page moved up.'));
    }

    public function fields(NovaRequest $request): array
    {
        return [
            Number::make('Steps', 'steps')->default(1)->min(1)->max(100),
        ];
    }
}



