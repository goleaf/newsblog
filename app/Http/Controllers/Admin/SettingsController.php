<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * Display the settings page.
     */
    public function index(): View
    {
        $groups = Setting::GROUPS;
        $settings = [];

        foreach (array_keys($groups) as $group) {
            $settings[$group] = Setting::getByGroup($group);
        }

        return view('admin.settings.index', compact('groups', 'settings'));
    }

    /**
     * Update settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => 'required|array',
            'settings.*' => 'nullable|string|max:65535',
            'group' => 'required|string|in:'.implode(',', array_keys(Setting::GROUPS)),
        ]);

        $group = $validated['group'];

        foreach ($validated['settings'] as $key => $value) {
            Setting::set($key, $value, $group);
        }

        // Clear all settings cache
        Setting::clearAllCache();

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Settings updated successfully.');
    }

    /**
     * Send a test email.
     */
    public function sendTestEmail(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);

        try {
            Mail::raw('This is a test email from TechNewsHub. If you received this, your email settings are configured correctly.', function ($message) use ($validated) {
                $message->to($validated['email'])
                    ->subject('Test Email from '.config('app.name'));
            });

            return redirect()
                ->route('admin.settings.index')
                ->with('success', 'Test email sent successfully to '.$validated['email']);
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.settings.index')
                ->with('error', 'Failed to send test email: '.$e->getMessage());
        }
    }

    /**
     * Clear settings cache.
     */
    public function clearCache(): RedirectResponse
    {
        Setting::clearAllCache();

        return redirect()
            ->route('admin.settings.index')
            ->with('success', 'Settings cache cleared successfully.');
    }
}
