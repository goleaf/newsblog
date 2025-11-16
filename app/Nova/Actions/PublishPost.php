<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class PublishPost extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Publish Post';

    /**
     * Perform the action on the given models.
     */
    public function handle(ActionFields $fields, Collection $models): mixed
    {
        $publishedCount = 0;

        foreach ($models as $post) {
            if ($post->status === \App\Enums\PostStatus::Draft) {
                $post->update([
                    'status' => \App\Enums\PostStatus::Published,
                    'published_at' => now(),
                ]);
                $publishedCount++;
            }
        }

        if ($publishedCount === 0) {
            return Action::danger('No draft posts were selected to publish.');
        }

        return Action::message("{$publishedCount} post(s) published successfully!");
    }

    /**
     * Get the fields available on the action.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [];
    }

    /**
     * Determine if the action should be available for the given request.
     */
    public function authorizedToSee($request): bool
    {
        return in_array($request->user()?->role, ['admin', 'editor'], true);
    }
}
