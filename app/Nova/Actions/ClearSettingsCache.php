<?php

namespace App\Nova\Actions;

use App\Services\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Collection;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;

class ClearSettingsCache extends Action
{
    use Queueable;

    public $name = 'Clear Settings Cache';

    public function handle(ActionFields $fields, Collection $models)
    {
        app(SettingsService::class)->clearAllCache();

        return Action::message(__('settings.cache_cleared'));
    }
}
