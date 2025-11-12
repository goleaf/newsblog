<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Http\Requests\NovaRequest;

class FeaturePosts extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Feature Posts';

    /**
     * Indicates if this action is only available on the resource index view.
     *
     * @var bool
     */
    public $onlyOnIndex = false;

    /**
     * Indicates if need to skip log action events for models.
     *
     * @var bool
     */
    public $withoutActionEvents = false;

    /**
     * Perform the action on the given models.
     */
    public function handle(ActionFields $fields, Collection $models): mixed
    {
        $action = $fields->get('action');
        $updatedCount = 0;

        foreach ($models as $post) {
            $newValue = $action === 'feature';

            if ($post->is_featured !== $newValue) {
                $post->update([
                    'is_featured' => $newValue,
                ]);
                $updatedCount++;
            }
        }

        if ($updatedCount === 0) {
            return Action::message('No posts were updated (already in desired state).');
        }

        $actionText = $action === 'feature' ? 'featured' : 'unfeatured';

        return Action::message("{$updatedCount} post(s) {$actionText} successfully!");
    }

    /**
     * Get the fields available on the action.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            Select::make('Action', 'action')
                ->options([
                    'feature' => 'Mark as Featured',
                    'unfeature' => 'Remove Featured',
                ])
                ->default('feature')
                ->rules('required')
                ->help('Choose whether to feature or unfeature the selected posts'),
        ];
    }

    /**
     * Determine if the action should be available for the given request.
     */
    public function authorizedToSee($request): bool
    {
        return in_array($request->user()->role, ['admin', 'editor']);
    }
}
