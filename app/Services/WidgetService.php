<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Widget;
use App\Models\WidgetArea;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class WidgetService
{
    public function render(Widget $widget): string
    {
        if (! $widget->active) {
            return '';
        }

        // Per-widget TTL (defaults to 10 minutes). Most-commented requires 60 minutes.
        $ttlMinutes = match ($widget->type) {
            'most-commented' => 60,
            default => 10,
        };

        // If the widget is not persisted (no ID), avoid caching to prevent collisions on key "widget."
        if ($widget->getKey() === null) {
            return $this->renderWidget($widget);
        }

        return Cache::remember("widget.{$widget->id}", now()->addMinutes($ttlMinutes), fn (): string => $this->renderWidget($widget));
    }

    public function renderArea(string $slug): string
    {
        $area = WidgetArea::where('slug', $slug)->first();

        if (! $area) {
            return '';
        }

        $output = '';
        foreach ($area->activeWidgets as $widget) {
            $output .= $this->render($widget);
        }

        return $output;
    }

    /**
     * Get widgets for an area by slug or model.
     *
     * @return \Illuminate\Support\Collection<int, \App\Models\Widget>
     */
    public function getWidgetsForArea(WidgetArea|string $areaOrSlug, bool $onlyActive = true): Collection
    {
        $area = $areaOrSlug instanceof WidgetArea
            ? $areaOrSlug
            : WidgetArea::where('slug', $areaOrSlug)->first();

        if (! $area) {
            return collect();
        }

        $relation = $onlyActive ? 'activeWidgets' : 'widgets';

        return $area->{$relation}()->get();
    }

    /**
     * Update ordering (and optionally area) for a set of widgets.
     *
     * Expects array of arrays with keys: id, order, widget_area_id
     *
     * @param  array<int, array{id:int, order:int, widget_area_id:int}>  $widgetsData
     */
    public function updateWidgetOrder(array $widgetsData): void
    {
        if ($widgetsData === []) {
            return;
        }

        DB::transaction(function () use ($widgetsData): void {
            foreach ($widgetsData as $data) {
                Widget::where('id', $data['id'])->update([
                    'order' => $data['order'],
                    'widget_area_id' => $data['widget_area_id'],
                ]);
            }
        });

        // Clear caches for affected areas
        collect($widgetsData)
            ->pluck('widget_area_id')
            ->unique()
            ->each(function (int $areaId): void {
                $area = WidgetArea::find($areaId);
                if ($area) {
                    $this->clearAreaCache($area);
                }
            });
    }

    protected function renderWidget(Widget $widget): string
    {
        return match ($widget->type) {
            'recent-posts' => $this->renderRecentPosts($widget),
            'popular-posts' => $this->renderPopularPosts($widget),
            'most-commented' => $this->renderMostCommented($widget),
            'categories' => $this->renderCategories($widget),
            'tags-cloud' => $this->renderTagsCloud($widget),
            'newsletter' => $this->renderNewsletter($widget),
            'search' => $this->renderSearch($widget),
            'custom-html' => $this->renderCustomHtml($widget),
            'weather' => $this->renderWeather($widget),
            'stock-ticker' => $this->renderStockTicker($widget),
            'countdown' => $this->renderCountdown($widget),
            'who-to-follow' => $this->renderWhoToFollow($widget),
            default => '',
        };
    }

    protected function renderWhoToFollow(Widget $widget): string
    {
        $user = auth()->user();
        if (! $user) {
            return '';
        }

        $limit = (int) ($widget->settings['count'] ?? 5);

        $cacheKey = "widget.who-to-follow.user:{$user->id}.limit:{$limit}";
        $suggestions = Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user, $limit) {
            $followingIds = \App\Models\Follow::query()
                ->where('follower_id', $user->id)
                ->pluck('followed_id')
                ->all();

            return \App\Models\User::query()
                ->where('id', '!=', $user->id)
                ->whereNotIn('id', $followingIds)
                ->withCount(['posts' => function ($q) {
                    $q->where('status', 'published');
                }])
                ->orderByDesc('posts_count')
                ->limit($limit)
                ->get(['id', 'name', 'email']);
        });

        if ($suggestions->isEmpty()) {
            return '';
        }

        return View::make('components.widgets.who-to-follow', [
            'widget' => $widget,
            'users' => $suggestions,
        ])->render();
    }

    protected function renderRecentPosts(Widget $widget): string
    {
        $count = $widget->settings['count'] ?? 5;
        $posts = Post::where('status', 'published')
            ->latest('published_at')
            ->take($count)
            ->get();

        return View::make('components.widgets.recent-posts', [
            'widget' => $widget,
            'posts' => $posts,
        ])->render();
    }

    protected function renderPopularPosts(Widget $widget): string
    {
        $count = $widget->settings['count'] ?? 5;
        $posts = Post::where('status', 'published')
            ->orderByDesc('view_count')
            ->take($count)
            ->get();

        return View::make('components.widgets.popular-posts', [
            'widget' => $widget,
            'posts' => $posts,
        ])->render();
    }

    protected function renderCategories(Widget $widget): string
    {
        $showCount = $widget->settings['show_count'] ?? true;
        $categories = Category::withCount('posts')
            ->orderBy('name')
            ->get();

        return View::make('components.widgets.categories-list', [
            'widget' => $widget,
            'categories' => $categories,
            'showCount' => $showCount,
        ])->render();
    }

    protected function renderTagsCloud(Widget $widget): string
    {
        $limit = $widget->settings['limit'] ?? 20;
        $tags = Tag::withCount('posts')
            ->orderByDesc('posts_count')
            ->take($limit)
            ->get();

        return View::make('components.widgets.tags-cloud', [
            'widget' => $widget,
            'tags' => $tags,
        ])->render();
    }

    protected function renderNewsletter(Widget $widget): string
    {
        return View::make('components.widgets.newsletter-form', [
            'widget' => $widget,
        ])->render();
    }

    protected function renderSearch(Widget $widget): string
    {
        return View::make('components.widgets.search', [
            'widget' => $widget,
        ])->render();
    }

    protected function renderCustomHtml(Widget $widget): string
    {
        $content = $widget->settings['content'] ?? '';

        return View::make('components.widgets.custom-html', [
            'widget' => $widget,
            'content' => $content,
        ])->render();
    }

    protected function renderWeather(Widget $widget): string
    {
        $default = config('services.weather.default_location', [
            'lat' => 51.5074,
            'lon' => -0.1278,
            'label' => 'London',
        ]);

        return View::make('components.widgets.weather', [
            'widget' => $widget,
            'defaultLat' => (float) ($widget->settings['lat'] ?? $default['lat']),
            'defaultLon' => (float) ($widget->settings['lon'] ?? $default['lon']),
            'defaultLabel' => (string) ($widget->settings['label'] ?? $default['label']),
        ])->render();
    }

    protected function renderStockTicker(Widget $widget): string
    {
        $symbols = (string) ($widget->settings['symbols'] ?? 'AAPL,MSFT,GOOG');

        return View::make('components.widgets.stock-ticker', [
            'widget' => $widget,
            'symbols' => $symbols,
        ])->render();
    }

    protected function renderCountdown(Widget $widget): string
    {
        $target = (string) ($widget->settings['target'] ?? now()->addDays(7)->toIso8601String());
        $labels = $widget->settings['labels'] ?? [
            'days' => __('Days'),
            'hours' => __('Hours'),
            'minutes' => __('Minutes'),
            'seconds' => __('Seconds'),
            'done' => __('Completed'),
        ];

        return View::make('components.widgets.countdown', [
            'widget' => $widget,
            'target' => $target,
            'labels' => $labels,
        ])->render();
    }

    protected function renderMostCommented(Widget $widget): string
    {
        $count = $widget->settings['count'] ?? 5;
        $since = now()->subDays(30);

        // Top posts by approved comment count in last 30 days, excluding older posts.
        $posts = Post::published()
            ->where('published_at', '>=', $since)
            ->withCount(['comments' => function ($q) use ($since) {
                $q->where('created_at', '>=', $since);
            }])
            ->orderByDesc('comments_count')
            ->take($count)
            ->get();

        return View::make('components.widgets.most-commented', [
            'widget' => $widget,
            'posts' => $posts,
        ])->render();
    }

    public function clearCache(Widget $widget): void
    {
        Cache::forget("widget.{$widget->id}");
    }

    public function clearAreaCache(WidgetArea $area): void
    {
        foreach ($area->widgets as $widget) {
            $this->clearCache($widget);
        }
    }
}
