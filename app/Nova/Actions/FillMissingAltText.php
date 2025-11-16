<?php

namespace App\Nova\Actions;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class FillMissingAltText extends Action
{
    use InteractsWithQueue, Queueable;

    /**
     * The displayable name of the action.
     *
     * @var string
     */
    public $name = 'Fill Missing Image Alt Text';

    /**
     * Perform the action on the given models.
     */
    public function handle(ActionFields $fields, Collection $models): mixed
    {
        $template = (string) ($fields->get('template') ?? 'Image for: {title} #{n}');
        $onlyMissing = (bool) ($fields->get('only_missing') ?? true);

        $updated = 0;
        foreach ($models as $post) {
            $original = (string) ($post->content ?? '');
            $updatedHtml = $this->fillAltAttributes($original, (string) $post->title, $template, $onlyMissing);

            if ($updatedHtml !== $original) {
                $post->content = $updatedHtml;
                $post->save();
                $updated++;
            }
        }

        if ($updated === 0) {
            return Action::danger('No changes made.');
        }

        return Action::message("Updated {$updated} post(s).");
    }

    /**
     * Get the fields available on the action.
     *
     * @return array<int, \Laravel\Nova\Fields\Field>
     */
    public function fields(NovaRequest $request): array
    {
        return [
            Text::make('Template', 'template')
                ->help('Use {title} for the post title and {n} for the image index (1-based).')
                ->default('Image for: {title} #{n}')
                ->rules('required', 'max:255'),

            Boolean::make('Only Missing', 'only_missing')
                ->help('If enabled, only images without alt text will be updated.')
                ->default(true),
        ];
    }

    /**
     * Determine if the action should be available for the given request.
     */
    public function authorizedToSee($request): bool
    {
        return in_array($request->user()?->role, ['admin', 'editor'], true);
    }

    /**
     * Determine if the action can be run for the given request.
     */
    public function authorizedToRun($request, $model): bool
    {
        return in_array($request->user()?->role, ['admin', 'editor'], true);
    }

    private function fillAltAttributes(string $html, string $title, string $template, bool $onlyMissing): string
    {
        if (trim($html) === '') {
            return $html;
        }

        $dom = new \DOMDocument;
        $internalErrors = libxml_use_internal_errors(true);
        $wrappedHtml = '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body>'.$html.'</body></html>';
        $dom->loadHTML($wrappedHtml);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        $images = $dom->getElementsByTagName('img');
        $index = 1;
        foreach ($images as $img) {
            $alt = $img->getAttribute('alt');
            $hasAlt = $img->hasAttribute('alt') && trim($alt) !== '';
            if ($onlyMissing && $hasAlt) {
                $index++;

                continue;
            }

            $value = str_replace(['{title}', '{n}'], [$title, (string) $index], $template);
            $img->setAttribute('alt', $value);
            $index++;
        }

        // Extract inner HTML of body
        $body = $dom->getElementsByTagName('body')->item(0);
        if (! $body) {
            return $html;
        }

        $result = '';
        foreach ($body->childNodes as $child) {
            $result .= $dom->saveHTML($child);
        }

        return $result;
    }
}
