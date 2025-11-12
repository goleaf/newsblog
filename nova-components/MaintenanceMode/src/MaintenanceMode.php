<?php

namespace App\MaintenanceMode;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class MaintenanceMode extends Tool
{
    /**
     * Perform any tasks that need to happen when the tool is booted.
     */
    public function boot(): void
    {
        Nova::mix('maintenance-mode', __DIR__.'/../dist/mix-manifest.json');
    }

    /**
     * Build the menu that renders the navigation links for the tool.
     */
    public function menu(Request $request): MenuSection
    {
        return MenuSection::make('Maintenance Mode')
            ->path('/maintenance-mode')
            ->icon('server');
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
