<?php

namespace App\Nova\Actions;

use App\Jobs\SendCommentApprovedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class ApproveComments extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Approve Comments';

    /**
     * Perform the action on the given models.
     */
    public function handle(ActionFields $fields, Collection $models): mixed
    {
        $approvedCount = 0;

        foreach ($models as $comment) {
            if ($comment->status !== 'approved') {
                $comment->update([
                    'status' => 'approved',
                ]);

                // Queue notification to post author (Requirement 24.1)
                dispatch(new SendCommentApprovedNotification($comment));

                $approvedCount++;
            }
        }

        if ($approvedCount === 0) {
            return Action::message('No comments were updated (already approved).');
        }

        return Action::message("{$approvedCount} comment(s) approved successfully!");
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
