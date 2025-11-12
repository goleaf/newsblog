<?php

namespace App\CacheManager;

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Nova;
use Laravel\Nova\Tool;

class CacheManager extends Tool
{
    /**
     * Perform any tasks that need to happen when the tool is booted.
     */
    public function boot(): void
    {
        Nova::mix('cache-manager', __DIR__.'/../dist/mix-manifest.json');
    }

    /**
     * Build the menu that renders the navigation links for the tool.
     */
    public function menu(Request $request): MenuSection
    {
        return MenuSection::make('Cache Manager')
            ->path('/cache-manager')
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
