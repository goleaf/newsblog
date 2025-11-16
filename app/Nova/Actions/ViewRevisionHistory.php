<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class ViewRevisionHistory extends Action
{
    use Queueable;

    /**
     * Perform the action on the given models.
     *
     * @param  \Illuminate\Support\Collection<int, \App\Models\Post>  $models
     */
    public function handle(ActionFields $fields, Collection $models)
    {
        $post = $models->first();
        if (! $post) {
            return Action::danger('No post selected.');
        }

        // Redirect to the post detail; revisions are shown in the HasMany panel
        $url = url('/nova/resources/posts/'.$post->id);

        return Action::redirect($url);
    }
}

