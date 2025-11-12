<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaApplicationServiceProvider;

class NovaServiceProvider extends NovaApplicationServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        parent::boot();

        Nova::globalSearchDebounce(500);

        // Handle deprecation notices for old admin URLs
        Nova::serving(function ($event) {
            if (session()->has('deprecated')) {
                $message = session()->get('deprecated', 'This admin URL has been deprecated. Please update your bookmarks to use the Nova admin panel.');

                // Provide deprecation notice to Nova's frontend
                Nova::provideToScript([
                    'deprecated' => true,
                    'deprecatedMessage' => $message,
                ]);

                // Clear the session flash after providing it to script
                session()->forget('deprecated');
            }
        });
    }

    /**
     * Register the configurations for Laravel Fortify.
     */
    protected function fortify(): void
    {
        Nova::fortify()
            ->features([
                Features::updatePasswords(),
                // Features::emailVerification(),
                // Features::twoFactorAuthentication(['confirm' => true, 'confirmPassword' => true]),
            ])
            ->register();
    }

    /**
     * Register the Nova routes.
     */
    protected function routes(): void
    {
        Nova::routes()
            ->withAuthenticationRoutes(default: true)
            ->withPasswordResetRoutes()
            ->withoutEmailVerificationRoutes()
            ->register();

        // Register System Health tool route
        Route::middleware('nova')
            ->group(function () {
                Route::get('/tools/system-health', function () {
                    return Inertia::render('SystemHealth');
                })->name('nova.tools.system-health');
            });
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewNova', function (User $user) {
            return in_array($user->role, ['admin', 'editor', 'author']);
        });

        Nova::auth(function ($request) {
            return Gate::check('viewNova', [$request->user()]);
        });
    }

    /**
     * Get the dashboards that should be listed in the Nova sidebar.
     *
     * @return array<int, \Laravel\Nova\Dashboard>
     */
    protected function dashboards(): array
    {
        return [
            new \App\Nova\Dashboards\Main,
        ];
    }

    /**
     * Get the tools that should be listed in the Nova sidebar.
     *
     * @return array<int, \Laravel\Nova\Tool>
     */
    public function tools(): array
    {
        return [
            new \App\Nova\Tools\SystemHealth,
            new \App\CacheManager\CacheManager,
            new \App\MaintenanceMode\MaintenanceMode,
        ];
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        parent::register();

        //
    }
}
