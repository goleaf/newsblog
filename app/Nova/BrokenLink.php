<?php

namespace App\Nova;

use App\Nova\Actions\FixBrokenLink;
use App\Nova\Actions\IgnoreBrokenLink;
use App\Nova\Filters\BrokenLinkStatus;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class BrokenLink extends Resource
{
    public static $model = \App\Models\BrokenLink::class;

    public static $title = 'url';

    public static $search = [
        'url', 'status',
    ];

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),
            BelongsTo::make('Post', 'post', Post::class)->sortable()->searchable(),
            Text::make('URL', 'url')->sortable()->readonly(),
            Text::make('Status', 'status')->sortable()->filterable(),
            Number::make('Response Code', 'response_code')->sortable()->nullable(),
            DateTime::make('Checked At', 'checked_at')->sortable()->nullable(),
            DateTime::make('Created At', 'created_at')->onlyOnDetail(),
            DateTime::make('Updated At', 'updated_at')->onlyOnDetail(),
        ];
    }

    public function actions(NovaRequest $request): array
    {
        return [
            new FixBrokenLink,
            new IgnoreBrokenLink,
        ];
    }

    public function filters(NovaRequest $request): array
    {
        return [
            new BrokenLinkStatus,
        ];
    }
}

