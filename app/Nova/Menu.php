<?php

namespace App\Nova;

use App\Enums\MenuLocation;
use App\Models\Menu as MenuModel;
use App\Nova\Repeaters\MenuItemRepeater;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Repeater;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class Menu extends Resource
{
    public static string $model = MenuModel::class;

    public static $title = 'name';

    public static $search = [
        'id',
        'name',
        'location',
    ];

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            Text::make(__('Name'), 'name')
                ->rules('required', 'string', 'max:255')
                ->sortable(),

            Select::make(__('Location'), 'location')
                ->options([
                    MenuLocation::Header->value => __('Header'),
                    MenuLocation::Footer->value => __('Footer'),
                    MenuLocation::Mobile->value => __('Mobile'),
                ])
                ->displayUsingLabels()
                ->sortable()
                ->rules('required', 'in:' . implode(',', array_map(fn ($c) => $c->value, MenuLocation::cases()))),

            Repeater::make(__('Menu Items'), 'items')
                ->asHasMany()
                ->repeatables([
                    MenuItemRepeater::make(),
                ])
                ->help(__('Drag to reorder items. Use the nested list to manage children.')),

            HasMany::make(__('Menu Items (table view)'), 'items', MenuItem::class)->hideFromIndex(),
        ];
    }

    public function cards(NovaRequest $request): array
    {
        return [];
    }

    public function filters(NovaRequest $request): array
    {
        return [];
    }

    public function lenses(NovaRequest $request): array
    {
        return [];
    }

    public function actions(NovaRequest $request): array
    {
        return [];
    }

    public static function indexQuery(NovaRequest $request, Builder $query): Builder
    {
        return $query->orderBy('location');
    }
}


