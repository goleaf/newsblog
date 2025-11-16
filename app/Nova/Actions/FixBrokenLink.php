<?php

namespace App\Nova\Actions;

use App\Models\BrokenLink;
use App\Models\Post;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Text;

class FixBrokenLink extends Action
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'Fix';

    public function handle(ActionFields $fields, Collection $models)
    {
        $newUrl = (string) $fields->get('new_url');
        if ($newUrl === '') {
            return Action::danger('New URL is required.');
        }

        /** @var BrokenLink $broken */
        foreach ($models as $broken) {
            $post = Post::find($broken->post_id);
            if (!$post) {
                continue;
            }
            $original = $broken->url;
            $updatedContent = str_replace($original, $newUrl, (string) $post->content);
            if ($updatedContent !== $post->content) {
                $post->content = $updatedContent;
                $post->save();
            }

            $broken->update([
                'url' => $newUrl,
                'status' => 'ok',
                'response_code' => null,
                'checked_at' => now(),
            ]);
        }

        return Action::message('Links updated.');
    }

    public function fields(): array
    {
        return [
            Text::make('New URL', 'new_url')
                ->rules('required', 'url')
                ->help('Provide the corrected URL to replace in post content.'),
        ];
    }
}


