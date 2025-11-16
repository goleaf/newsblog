<?php

namespace App\Nova\Actions;

use App\Models\Post;
use App\Models\PostRevision;
use App\Services\PostRevisionService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Textarea;
use Laravel\Nova\Http\Requests\NovaRequest;

class RestoreRevision extends Action
{
    use Queueable;

    /**
     * Perform the action on the given models.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\PostRevision>  $models
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        /** @var PostRevision $revision */
        foreach ($models as $revision) {
            /** @var Post $post */
            $post = $revision->post;
            app(PostRevisionService::class)->restoreRevision($post, $revision);
        }

        return Action::message('Revision restored successfully.');
    }

    /**
     * Get the fields available on the action.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            Textarea::make('Note')
                ->help('Optional note to include in the automatic backup before restoring.'),
        ];
    }
}
