<?php

namespace App\Nova\Tools;

use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Tool;

class SystemHealth extends Tool
{
    /**
     * Build the menu that renders the navigation links for the tool.
     */
    public function menu(Request $request): MenuSection
    {
        return MenuSection::make('System Health')
            ->path('/tools/system-health')
            ->icon('server');
    }
}
