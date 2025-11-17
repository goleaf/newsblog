<?php

namespace App\View\Components;

use App\Enums\MenuLocation;
use App\Models\Menu as MenuModel;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Menu extends Component
{
    public function __construct(
        public ?MenuLocation $location = null,
        public ?string $class = null,
        public ?string $itemClass = null,
        public ?string $linkClass = null,
    ) {}

    public function render(): View
    {
        $effectiveLocation = $this->location ?? MenuLocation::Header;

        $menu = MenuModel::query()
            ->where('location', $effectiveLocation->value)
            ->with(['rootItems.children' => fn ($q) => $q->orderBy('order')])
            ->first();

        return view('components.menu', [
            'menu' => $menu,
            'location' => $effectiveLocation,
            'class' => $this->class,
            'itemClass' => $this->itemClass,
            'linkClass' => $this->linkClass,
        ]);
    }
}
