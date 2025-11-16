<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Page;
use App\Models\Post;
use App\Models\Series;
use App\Models\Tag;
use Illuminate\Http\Request;

class BreadcrumbService
{
    /**
     * Generate breadcrumbs for the current request
     */
    public function generate(Request $request): array
    {
        $breadcrumbs = [
            ['title' => 'Home', 'url' => route('home')],
        ];

        $routeName = $request->route()?->getName();

        return match (true) {
            str_starts_with($routeName, 'post.show') => $this->generatePostBreadcrumbs($request, $breadcrumbs),
            str_starts_with($routeName, 'category.show') => $this->generateCategoryBreadcrumbs($request, $breadcrumbs),
            str_starts_with($routeName, 'tag.show') => $this->generateTagBreadcrumbs($request, $breadcrumbs),
            str_starts_with($routeName, 'series.show') => $this->generateSeriesBreadcrumbs($request, $breadcrumbs),
            str_starts_with($routeName, 'page.show') => $this->generatePageBreadcrumbs($request, $breadcrumbs),
            str_starts_with($routeName, 'search') => $this->generateSearchBreadcrumbs($request, $breadcrumbs),
            default => $breadcrumbs,
        };
    }

    /**
     * Generate breadcrumbs for post pages
     */
    private function generatePostBreadcrumbs(Request $request, array $breadcrumbs): array
    {
        $slug = $request->route('slug');

        if (! $slug) {
            return $breadcrumbs;
        }

        $post = Post::where('slug', $slug)->with('category.parent')->first();

        if (! $post) {
            return $breadcrumbs;
        }

        // Add category hierarchy
        if ($post->category) {
            $breadcrumbs = array_merge($breadcrumbs, $this->getCategoryHierarchy($post->category));
        }

        // Add post title (current page, no URL)
        $breadcrumbs[] = [
            'title' => $this->truncateTitle($post->title),
            'url' => null,
        ];

        return $breadcrumbs;
    }

    /**
     * Generate breadcrumbs for category pages
     */
    private function generateCategoryBreadcrumbs(Request $request, array $breadcrumbs): array
    {
        $slug = $request->route('slug');

        if (! $slug) {
            return $breadcrumbs;
        }

        $category = Category::where('slug', $slug)->with('parent')->first();

        if (! $category) {
            return $breadcrumbs;
        }

        // Add parent categories
        $hierarchy = $this->getCategoryHierarchy($category);

        // Remove the last item (current category) and add it without URL
        if (count($hierarchy) > 0) {
            $current = array_pop($hierarchy);
            $breadcrumbs = array_merge($breadcrumbs, $hierarchy);
            $breadcrumbs[] = [
                'title' => $current['title'],
                'url' => null,
            ];
        }

        return $breadcrumbs;
    }

    /**
     * Generate breadcrumbs for tag pages
     */
    private function generateTagBreadcrumbs(Request $request, array $breadcrumbs): array
    {
        $slug = $request->route('slug');

        if (! $slug) {
            return $breadcrumbs;
        }

        $tag = Tag::where('slug', $slug)->first();

        if (! $tag) {
            return $breadcrumbs;
        }

        $breadcrumbs[] = [
            'title' => $tag->name,
            'url' => null,
        ];

        return $breadcrumbs;
    }

    /**
     * Generate breadcrumbs for series pages
     */
    private function generateSeriesBreadcrumbs(Request $request, array $breadcrumbs): array
    {
        $slug = $request->route('slug');

        if (! $slug) {
            return $breadcrumbs;
        }

        $series = Series::where('slug', $slug)->first();

        if (! $series) {
            return $breadcrumbs;
        }

        $breadcrumbs[] = [
            'title' => 'Series',
            'url' => route('series.index'),
        ];

        $breadcrumbs[] = [
            'title' => $this->truncateTitle($series->name),
            'url' => null,
        ];

        return $breadcrumbs;
    }

    /**
     * Generate breadcrumbs for page pages
     */
    private function generatePageBreadcrumbs(Request $request, array $breadcrumbs): array
    {
        $slugPath = $request->route('slugPath') ?? $request->route('slug');

        if (! $slugPath) {
            return $breadcrumbs;
        }

        $page = Page::findByPath($slugPath);

        if (! $page) {
            return $breadcrumbs;
        }

        // Add parent pages if hierarchical
        if ($page->parent_id) {
            $parents = $this->getPageHierarchy($page);
            $breadcrumbs = array_merge($breadcrumbs, $parents);
        }

        $breadcrumbs[] = [
            'title' => $this->truncateTitle($page->title),
            'url' => null,
        ];

        return $breadcrumbs;
    }

    /**
     * Generate breadcrumbs for search pages
     */
    private function generateSearchBreadcrumbs(Request $request, array $breadcrumbs): array
    {
        $breadcrumbs[] = [
            'title' => 'Search Results',
            'url' => null,
        ];

        return $breadcrumbs;
    }

    /**
     * Get category hierarchy from root to current
     */
    private function getCategoryHierarchy(Category $category): array
    {
        $hierarchy = [];
        $current = $category;

        // Build hierarchy from current to root
        while ($current) {
            array_unshift($hierarchy, [
                'title' => $current->name,
                'url' => route('category.show', $current->slug),
            ]);
            $current = $current->parent;
        }

        return $hierarchy;
    }

    /**
     * Get page hierarchy from root to current
     */
    private function getPageHierarchy(Page $page): array
    {
        $hierarchy = [];
        $current = $page->parent;

        // Build hierarchy from current to root
        while ($current) {
            array_unshift($hierarchy, [
                'title' => $this->truncateTitle($current->title),
                'url' => route('page.show', $current->slug_path),
            ]);
            $current = $current->parent;
        }

        return $hierarchy;
    }

    /**
     * Truncate title for mobile display
     */
    private function truncateTitle(string $title, int $maxLength = 30): string
    {
        if (strlen($title) <= $maxLength) {
            return $title;
        }

        return substr($title, 0, $maxLength - 3).'...';
    }

    /**
     * Generate Schema.org BreadcrumbList structured data
     */
    public function generateStructuredData(array $breadcrumbs): string
    {
        $items = [];

        foreach ($breadcrumbs as $index => $crumb) {
            $item = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $crumb['title'],
            ];

            // Only add item URL if it's not the current page
            if ($crumb['url']) {
                $item['item'] = $crumb['url'];
            }

            $items[] = $item;
        }

        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];

        return json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}
