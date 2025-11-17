<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Nova\Menu\Menu;
use Laravel\Nova\Menu\MenuItem;
use Laravel\Nova\Menu\MenuSection;
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

        // Add custom CSS for Nova admin panel
        Nova::style('nova-custom', asset('css/nova/custom.css'));

        // Customize the main navigation menu
        Nova::mainMenu(function (Request $request) {
            return [
                MenuSection::dashboard(\App\Nova\Dashboards\Main::class)
                    ->icon('chart-bar'),
                MenuSection::dashboard(\App\Nova\Dashboards\Performance::class)
                    ->icon('chart-bar'),
                MenuSection::dashboard(\App\Nova\Dashboards\SystemHealth::class)
                    ->icon('shield-check')
                    ->canSee(function ($request) {
                        return $request->user()?->role === 'admin';
                    }),

                MenuSection::make('Content', [
                    MenuItem::resource(\App\Nova\Post::class),
                    MenuItem::resource(\App\Nova\Category::class),
                    MenuItem::resource(\App\Nova\Tag::class),
                    MenuItem::resource(\App\Nova\Comment::class),
                    MenuItem::link('Page Ordering', '/tools/pages-order')
                        ->canSee(function ($request) {
                            return in_array($request->user()?->role, ['admin', 'editor'], true);
                        }),
                ])->icon('document-text')->collapsable(),

                MenuSection::make('Media & Files', [
                    MenuItem::resource(\App\Nova\Media::class),
                ])->icon('photo')->collapsable(),

                MenuSection::make('Users & Settings', [
                    MenuItem::resource(\App\Nova\User::class),
                ])->icon('user-group')->collapsable(),

                MenuSection::make('System Tools', [
                    MenuItem::link('System Health', '/tools/system-health'),
                    MenuItem::link('Widget Manager', '/admin/widgets'),
                ])->icon('cog')->collapsable()
                    ->canSee(function ($request) {
                        return in_array($request->user()?->role, ['admin'], true);
                    }),
            ];
        });

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
        if (app()->environment('testing')) {
            // In testing, skip registering Nova's own routes so tests can
            // exercise lightweight /nova-api/* JSON endpoints without
            // depending on Nova internals.
            return;
        }

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

                // Page Ordering tool route (Blade view)
                Route::get('/tools/pages-order', function () {
                    // Only admin/editor can access
                    $user = request()->user();
                    if (! $user || ! in_array($user->role, ['admin', 'editor'], true)) {
                        abort(403);
                    }

                    return app(\App\Nova\Tools\PageOrder::class)->render(request());
                })->name('nova.tools.pages-order');
            });
    }

    /**
     * Bootstrap route services.
     */
    protected function bootRoutes(): void
    {
        if ($this->app->environment('testing')) {
            // Prevent Nova from registering its own /nova and /nova-api routes during tests.
            return;
        }

        // Defer to the base implementation for normal environments
        $this->routes();

        if (! $this->app->routesAreCached()) {
            \Laravel\Nova\Nova::routes()->bootstrap($this->app);
        }
    }

    /**
     * Register the Nova gate.
     *
     * This gate determines who can access Nova in non-local environments.
     */
    protected function gate(): void
    {
        Gate::define('viewNova', function (User $user) {
            $role = $user->role instanceof \BackedEnum ? $user->role->value : $user->role;

            return in_array($role, ['admin', 'editor', 'author'], true);
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
            new \App\Nova\Dashboards\Performance,
            new \App\Nova\Dashboards\SystemHealth,
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
            new \App\Nova\Tools\PageOrder,
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
