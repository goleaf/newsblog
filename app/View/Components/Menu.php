<?php

namespace App\View\Components;

use App\Enums\MenuLocation;
use App\Models\Menu as MenuModel;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Menu extends Component
{
    public function __construct(
        public MenuLocation $location,
        public ?string $class = null,
        public ?string $itemClass = null,
        public ?string $linkClass = null,
    ) {}

    public function render(): View
    {
        $menu = MenuModel::query()
            ->where('location', $this->location->value)
            ->with(['rootItems.children' => fn ($q) => $q->orderBy('order')])
            ->first();

        return view('components.menu', [
            'menu' => $menu,
            'location' => $this->location,
            'class' => $this->class,
            'itemClass' => $this->itemClass,
            'linkClass' => $this->linkClass,
        ]);
    }
}
