<?php

namespace App\Nova\Repeaters;

use App\Enums\MenuItemType;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Repeater;
use Laravel\Nova\Fields\Repeater\Repeatable;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class MenuItemRepeater extends Repeatable
{
    public static string $repeatableName = 'Menu Item';

    public function fields(NovaRequest $request): array
    {
        return [
            ID::make()->hideFromIndex()->hideWhenCreating()->hideWhenUpdating(),

            Select::make(__('Type'), 'type')
                ->options([
                    MenuItemType::Link->value => __('Link'),
                    MenuItemType::Page->value => __('Page'),
                    MenuItemType::Category->value => __('Category'),
                    MenuItemType::Tag->value => __('Tag'),
                ])
                ->displayUsingLabels()
                ->rules('required', 'in:'.implode(',', array_map(fn ($c) => $c->value, MenuItemType::cases()))),

            Text::make(__('Title'), 'title')
                ->rules('required', 'string', 'max:255'),

            Text::make(__('URL'), 'url')
                ->nullable()
                ->rules('nullable', 'string', 'max:2048'),

            Number::make(__('Order'), 'order')
                ->rules('required', 'integer', 'min:0')
                ->help(__('Drag to reorder; this value updates automatically.')),

            Text::make(__('CSS Class'), 'css_class')
                ->nullable()
                ->rules('nullable', 'string', 'max:255'),

            Text::make(__('Target'), 'target')
                ->nullable()
                ->rules('nullable', 'in:_self,_blank,_parent,_top'),

            Repeater::make(__('Children'), 'children')
                ->asHasMany()
                ->repeatables([
                    self::make(),
                ])
                ->help(__('Nest items inside their parent to build hierarchical menus.')),
        ];
    }
}
