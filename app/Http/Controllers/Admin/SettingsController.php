<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendTestEmailRequest;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(public SettingsService $settingsService) {}

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
    public function update(UpdateSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $group = $validated['group'];

        foreach ($validated['settings'] as $key => $value) {
            $this->settingsService->set($key, $value, $group);
        }

        // Clear all settings cache
        $this->settingsService->clearAllCache();

        return redirect()
            ->route('admin.settings.index')
            ->with('success', __('settings.updated'));
    }

    /**
     * Send a test email.
     */
    public function sendTestEmail(SendTestEmailRequest $request): RedirectResponse
    {
        $validated = $request->validated();

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
                ->with('error', __('settings.test_email_failed', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Clear settings cache.
     */
    public function clearCache(): RedirectResponse
    {
        $this->settingsService->clearAllCache();

        return redirect()
            ->route('admin.settings.index')
            ->with('success', __('settings.cache_cleared'));
    }

    /**
     * Get feature flag status.
     */
    public function getFeatureFlag(string $flag): bool
    {
        return (bool) $this->settingsService->get("feature_{$flag}", false);
    }

    /**
     * Toggle a feature flag.
     */
    public function toggleFeatureFlag(string $flag): RedirectResponse
    {
        $currentValue = $this->getFeatureFlag($flag);
        $this->settingsService->set("feature_{$flag}", ! $currentValue, 'features');

        return redirect()
            ->route('admin.settings.index')
            ->with('success', __('settings.feature_flag_updated'));
    }
}
