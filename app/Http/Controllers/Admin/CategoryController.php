<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display a listing of categories with hierarchical structure.
     */
    public function index(Request $request): \Illuminate\Contracts\View\View
    {
        $query = Category::query()
            ->parent()
            ->with([
                'children' => function ($query) {
                    $query->withCount(['posts' => function ($q) {
                        $q->published();
                    }])
                        ->ordered();
                },
            ])
            ->withCount(['posts' => function ($q) {
                $q->published();
            }]);

        // Apply search filter if provided
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Apply status filter if provided
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        $categories = $query->ordered()->get();

        return view('admin.categories.index', compact('categories'));
    }

    /**
     * Show the form for creating a new category.
     */
    public function create(): \Illuminate\Contracts\View\View
    {
        // Get all active categories for parent selection
        $parentCategories = Category::active()
            ->parent()
            ->ordered()
            ->get();

        return view('admin.categories.create', compact('parentCategories'));
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Auto-generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        // Set default display order if not provided
        if (! isset($data['display_order'])) {
            $data['display_order'] = Category::max('display_order') + 1;
        }

        $category = Category::create($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' created successfully.");
    }

    /**
     * Display the specified category.
     */
    public function show(Category $category): \Illuminate\Contracts\View\View
    {
        $category->load([
            'parent',
            'children' => function ($query) {
                $query->withCount(['posts' => function ($q) {
                    $q->published();
                }])
                    ->ordered();
            },
        ]);

        $category->loadCount(['posts' => function ($q) {
            $q->published();
        }]);

        // Get recent posts in this category
        $recentPosts = $category->posts()
            ->published()
            ->with('user:id,name')
            ->latest('published_at')
            ->limit(10)
            ->get();

        return view('admin.categories.show', compact('category', 'recentPosts'));
    }

    /**
     * Show the form for editing the specified category.
     */
    public function edit(Category $category): \Illuminate\Contracts\View\View
    {
        // Get all active categories for parent selection, excluding current category and its descendants
        $descendantIds = $category->getAllDescendantIds();
        $parentCategories = Category::active()
            ->parent()
            ->whereNotIn('id', $descendantIds)
            ->ordered()
            ->get();

        return view('admin.categories.edit', compact('category', 'parentCategories'));
    }

    /**
     * Update the specified category in storage.
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $data = $request->validated();

        // Auto-generate slug if not provided and name changed
        if (empty($data['slug']) && $request->has('name')) {
            $data['slug'] = Str::slug($data['name']);
        }

        $category->update($data);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Category '{$category->name}' updated successfully.");
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Category $category): RedirectResponse
    {
        // Check if category has posts
        $postsCount = $category->posts()->count();

        if ($postsCount > 0) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', "Cannot delete category '{$category->name}' because it has {$postsCount} post(s). Please reassign or delete the posts first.");
        }

        // Check if category has children
        $childrenCount = $category->children()->count();

        if ($childrenCount > 0) {
            return redirect()
                ->route('admin.categories.index')
                ->with('error', "Cannot delete category '{$category->name}' because it has {$childrenCount} subcategory(ies). Please delete or reassign the subcategories first.");
        }

        $categoryName = $category->name;
        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', "Category '{$categoryName}' deleted successfully.");
    }
}
