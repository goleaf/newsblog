<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Widget;
use App\Models\WidgetArea;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class WidgetService
{
    public function render(Widget $widget): string
    {
        if (! $widget->active) {
            return '';
        }

        return Cache::remember(
            "widget.{$widget->id}",
            now()->addMinutes(10),
            fn () => $this->renderWidget($widget)
        );
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

    protected function renderWidget(Widget $widget): string
    {
        return match ($widget->type) {
            'recent-posts' => $this->renderRecentPosts($widget),
            'popular-posts' => $this->renderPopularPosts($widget),
            'categories' => $this->renderCategories($widget),
            'tags-cloud' => $this->renderTagsCloud($widget),
            'newsletter' => $this->renderNewsletter($widget),
            'search' => $this->renderSearch($widget),
            'custom-html' => $this->renderCustomHtml($widget),
            default => '',
        };
    }

    protected function renderRecentPosts(Widget $widget): string
    {
        $count = $widget->settings['count'] ?? 5;
        $posts = Post::where('status', 'published')
            ->latest('published_at')
            ->take($count)
            ->get();

        return View::make('widgets.recent-posts', [
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

        return View::make('widgets.popular-posts', [
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

        return View::make('widgets.categories', [
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

        return View::make('widgets.tags-cloud', [
            'widget' => $widget,
            'tags' => $tags,
        ])->render();
    }

    protected function renderNewsletter(Widget $widget): string
    {
        return View::make('widgets.newsletter', [
            'widget' => $widget,
        ])->render();
    }

    protected function renderSearch(Widget $widget): string
    {
        return View::make('widgets.search', [
            'widget' => $widget,
        ])->render();
    }

    protected function renderCustomHtml(Widget $widget): string
    {
        $content = $widget->settings['content'] ?? '';

        return View::make('widgets.custom-html', [
            'widget' => $widget,
            'content' => $content,
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
