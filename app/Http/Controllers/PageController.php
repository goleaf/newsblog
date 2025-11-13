<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function show(string $slug)
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'published')
            ->with('parent', 'children')
            ->firstOrFail();

        // Determine which view to use based on template
        $view = match ($page->template) {
            'full-width' => 'pages.full-width',
            'contact' => 'pages.contact',
            'about' => 'pages.about',
            default => 'pages.default',
        };

        return view($view, compact('page'));
    }

    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|max:255',
            'message' => 'required|max:5000',
        ]);

        \App\Models\ContactMessage::create([
            ...$validated,
            'status' => 'new',
        ]);

        return back()->with('success', 'Thank you for your message. We will get back to you soon!');
    }
}
