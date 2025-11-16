<?php

namespace App\Nova\Filters;

use App\Models\Setting;
use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class SettingGroupFilter extends Filter
{
    public $name = 'Group';

    public function apply(Request $request, $query, $value)
    {
        return $query->where('group', $value);
    }

    public function options(Request $request): array
    {
        // Flip to label => value as Nova expects
        return collect(Setting::GROUPS)->mapWithKeys(fn ($label, $value) => [$label => $value])->toArray();
    }
}
