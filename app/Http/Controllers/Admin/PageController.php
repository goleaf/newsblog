<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        $pages = Page::with('parent')
            ->ordered()
            ->paginate(20);

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        $page = new Page;
        $parentPages = Page::whereNull('parent_id')->ordered()->get();
        $templates = $page->getAvailableTemplates();

        return view('admin.pages.create', compact('page', 'parentPages', 'templates'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:pages,slug',
            'content' => 'required',
            'meta_title' => 'nullable|max:255',
            'meta_description' => 'nullable|max:500',
            'status' => 'required|in:draft,published',
            'template' => 'required|in:default,full-width,contact,about',
            'parent_id' => 'nullable|exists:pages,id',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $page = Page::create($validated);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', 'Page created successfully.');
    }

    public function edit(Page $page)
    {
        $parentPages = Page::whereNull('parent_id')
            ->where('id', '!=', $page->id)
            ->ordered()
            ->get();
        $templates = $page->getAvailableTemplates();

        return view('admin.pages.edit', compact('page', 'parentPages', 'templates'));
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'slug' => 'nullable|max:255|unique:pages,slug,'.$page->id,
            'content' => 'required',
            'meta_title' => 'nullable|max:255',
            'meta_description' => 'nullable|max:500',
            'status' => 'required|in:draft,published',
            'template' => 'required|in:default,full-width,contact,about',
            'parent_id' => 'nullable|exists:pages,id',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $page->update($validated);

        return redirect()
            ->route('admin.pages.edit', $page)
            ->with('success', 'Page updated successfully.');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()
            ->route('admin.pages.index')
            ->with('success', 'Page deleted successfully.');
    }

    public function updateOrder(Request $request)
    {
        $validated = $request->validate([
            'pages' => 'required|array',
            'pages.*.id' => 'required|exists:pages,id',
            'pages.*.display_order' => 'required|integer|min:0',
        ]);

        foreach ($validated['pages'] as $pageData) {
            Page::where('id', $pageData['id'])
                ->update(['display_order' => $pageData['display_order']]);
        }

        return response()->json(['success' => true]);
    }
}
