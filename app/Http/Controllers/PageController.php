<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubmitContactRequest;
use App\Mail\ContactMessageReceived;
use App\Models\ContactMessage;
use App\Models\Page;
use Illuminate\Support\Facades\Mail;

class PageController extends Controller
{
    public function show(string $slugPath)
    {
        $page = Page::findByPath($slugPath);
        if (! $page) {
            abort(404);
        }

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
