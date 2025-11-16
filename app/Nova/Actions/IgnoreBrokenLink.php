<?php

namespace App\Nova\Actions;

use App\Models\BrokenLink;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
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
            $payload = [
                'status' => 'ignored',
                'checked_at' => now(),
            ];
            if (Schema::hasColumn('broken_links', 'last_checked_at')) {
                $payload['last_checked_at'] = now();
            }

            $broken->update($payload);
        }

        return Action::message('Links ignored.');
    }
}
