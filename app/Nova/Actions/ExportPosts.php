<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Http\Requests\NovaRequest;

class ExportPosts extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Export Posts';

    /**
     * Indicates if this action is only available on the resource index view.
     *
     * @var bool
     */
    public $onlyOnIndex = true;

    /**
     * Perform the action on the given models.
     */
    public function handle(ActionFields $fields, Collection $models): mixed
    {
        $filename = 'posts_export_'.now()->format('Y-m-d_His').'.csv';
        $filepath = storage_path('app/public/'.$filename);

        $handle = fopen($filepath, 'w');

        // Write CSV header
        fputcsv($handle, [
            'ID',
            'Title',
            'Slug',
            'Author',
            'Category',
            'Status',
            'Is Featured',
            'Is Trending',
            'View Count',
            'Published At',
            'Created At',
            'Excerpt',
            'Meta Title',
            'Meta Description',
        ]);

        // Write post data
        foreach ($models as $post) {
            fputcsv($handle, [
                $post->id,
                $post->title,
                $post->slug,
                $post->user?->name ?? 'N/A',
                $post->category?->name ?? 'N/A',
                ucfirst($post->status->value),
                $post->is_featured ? 'Yes' : 'No',
                $post->is_trending ? 'Yes' : 'No',
                $post->view_count,
                $post->published_at?->format('Y-m-d H:i:s') ?? 'N/A',
                $post->created_at->format('Y-m-d H:i:s'),
                $post->excerpt ?? '',
                $post->meta_title ?? '',
                $post->meta_description ?? '',
            ]);
        }

        fclose($handle);

        return Action::download(
            asset('storage/'.$filename),
            $filename
        );
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
