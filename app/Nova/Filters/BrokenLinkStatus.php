<?php

namespace App\Nova\Filters;

use Illuminate\Http\Request;
use Laravel\Nova\Filters\Filter;

class BrokenLinkStatus extends Filter
{
    public $name = 'Status';

    public function apply(Request $request, $query, $value)
    {
        // Map UI-friendly labels to stored values
        return $query->where('status', $value);
    }

    public function options(Request $request)
    {
        return [
            'Pending' => 'broken',
            'Fixed' => 'ok',
            'Ignored' => 'ignored',
        ];
    }
}
