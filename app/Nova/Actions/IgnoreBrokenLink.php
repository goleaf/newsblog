<?php

namespace App\Nova\Actions;

use App\Models\BrokenLink;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class IgnoreBrokenLink extends Action
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public $name = 'Ignore';

    public function handle(ActionFields $fields, Collection $models)
    {
        /** @var BrokenLink $broken */
        foreach ($models as $broken) {
            $broken->update([
                'status' => 'ignored',
                'checked_at' => now(),
                'last_checked_at' => now(), // legacy sync
            ]);
        }

        return Action::message('Links ignored.');
    }
}
