<?php

namespace App\Nova\Tools;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class SystemHealth extends Tool
{
    /**
     * Perform any tasks that need to happen when the tool is booted.
     */
    public function boot(): void
    {
        Nova::mix('system-health', __DIR__.'/../../../nova-components/SystemHealth/dist/mix-manifest.json');
    }

    /**
     * Build the menu that renders the navigation links for the tool.
     */
    public function menu(Request $request): MenuSection
    {
        return MenuSection::make('System Health')
            ->path('/system-health')
            ->icon('chart-bar');
    }

    /**
     * Determine if the tool can be seen by the user.
     */
    public function authorize(Request $request): bool
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->role === 'admin';
    }
}
