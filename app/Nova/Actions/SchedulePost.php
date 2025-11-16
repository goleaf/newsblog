<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Http\Requests\NovaRequest;

class SchedulePost extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Schedule Post';

    /**
     * Perform the action on the given models.
     */
    public function handle(ActionFields $fields, Collection $models): mixed
    {
        if (! $fields->scheduled_at) {
            return Action::danger('Please provide a schedule date and time.');
        }

        $scheduledAt = $fields->scheduled_at;
        $scheduledCount = 0;

        foreach ($models as $post) {
            $post->update([
                'status' => \App\Enums\PostStatus::Scheduled,
                'scheduled_at' => $scheduledAt,
            ]);

            $scheduledCount++;
        }

        return Action::message("{$scheduledCount} post(s) scheduled successfully!");
    }

    /**
     * Get the fields available on the action.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            DateTime::make('Scheduled At', 'scheduled_at')
                ->rules('required', 'date')
                ->help('Date and time when the post should be published'),
        ];
    }

    /**
     * Determine if the action should be available for the given request.
     */
    public function authorizedToSee($request): bool
    {
        return in_array($request->user()?->role, ['admin', 'editor'], true);
    }
}
