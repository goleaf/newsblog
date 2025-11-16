<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Illuminate\Http\Request;
use App\Http\Requests\SubmitContactRequest;
use App\Models\ContactMessage;
use App\Mail\ContactMessageReceived;
use Illuminate\Support\Facades\Mail;

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

    public function submitContact(SubmitContactRequest $request)
    {
        $validated = $request->validated();

        $message = ContactMessage::create([
            ...$validated,
            'status' => 'new',
        ]);

        Mail::to(config('mail.from.address'))
            ->queue(new ContactMessageReceived($message));

        return back()->with('success', __('messages.contact.thanks'));
    }
}
