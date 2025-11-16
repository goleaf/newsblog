<?php

namespace App\Nova;

use App\Enums\MenuItemType;
use App\Models\MenuItem as MenuItemModel;
use Illuminate\Database\Eloquent\Builder;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class MenuItem extends Resource
{
    public static string $model = MenuItemModel::class;

    public static $title = 'title';

    public static $search = [
        'id',
        'title',
        'url',
        'css_class',
        'target',
        'type',
    ];

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->sortable(),

            BelongsTo::make(__('Menu'), 'menu', Menu::class)
                ->rules('required')
                ->sortable(),

            BelongsTo::make(__('Parent Item'), 'parent', self::class)
                ->nullable()
                ->sortable(),

            Select::make(__('Type'), 'type')
                ->options([
                    MenuItemType::Link->value => __('Link'),
                    MenuItemType::Page->value => __('Page'),
                    MenuItemType::Category->value => __('Category'),
                    MenuItemType::Tag->value => __('Tag'),
                ])
                ->displayUsingLabels()
                ->rules('required', 'in:' . implode(',', array_map(fn ($c) => $c->value, MenuItemType::cases())))
                ->sortable(),

            Text::make(__('Title'), 'title')
                ->rules('required', 'string', 'max:255')
                ->sortable(),

            Text::make(__('URL'), 'url')
                ->nullable()
                ->hideFromIndex()
                ->rules('nullable', 'string', 'max:2048'),

            Number::make(__('Reference ID'), 'reference_id')
                ->nullable()
                ->hideFromIndex()
                ->rules('nullable', 'integer', 'min:1'),

            Number::make(__('Order'), 'order')
                ->sortable()
                ->rules('required', 'integer', 'min:0'),

            Text::make(__('CSS Class'), 'css_class')
                ->nullable()
                ->hideFromIndex()
                ->rules('nullable', 'string', 'max:255'),

            Text::make(__('Target'), 'target')
                ->nullable()
                ->hideFromIndex()
                ->rules('nullable', 'in:_self,_blank,_parent,_top'),

            HasMany::make(__('Children'), 'children', self::class),
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

    public static function indexQuery(NovaRequest $request, $query): Builder
    {
        return $query->orderBy('menu_id')->orderBy('parent_id')->orderBy('order');
    }
}


