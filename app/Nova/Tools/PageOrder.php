<?php

namespace App\Nova\Tools;

use App\Models\Page;
use Illuminate\Http\Request;
use Laravel\Nova\Menu\MenuSection;
use Laravel\Nova\Tool;

class PageOrder extends Tool
{
    /**
     * Build the menu that renders the navigation link for the tool.
     */
    public function menu(Request $request): MenuSection
    {
        return MenuSection::make('Page Ordering')
            ->path('/tools/pages-order')
            ->icon('switch-vertical');
    }

    /**
     * Determine if the tool can be seen by the user.
     */
    public function authorize(Request $request): bool
    {
        $user = $request->user();
        if (! $user) {
            return false;
        }

        return in_array($user->role, ['admin', 'editor'], true);
    }

    /**
     * Build the view that renders the tool.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render(Request $request)
    {
        $pages = Page::with('parent')->ordered()->get();

        return view('nova.pages-order', [
            'pages' => $pages,
        ]);
    }
}
