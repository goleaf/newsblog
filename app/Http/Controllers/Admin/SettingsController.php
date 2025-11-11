<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        $groups = [
            'general' => Setting::where('group', 'general')->get()->pluck('value', 'key'),
            'seo' => Setting::where('group', 'seo')->get()->pluck('value', 'key'),
            'social' => Setting::where('group', 'social')->get()->pluck('value', 'key'),
            'email' => Setting::where('group', 'email')->get()->pluck('value', 'key'),
        ];

        return view('admin.settings.index', compact('groups'));
    }

    public function update(Request $request)
    {
        foreach ($request->except(['_token', '_method']) as $key => $value) {
            $group = explode('_', $key)[0];
            Setting::set($key, $value, $group);
        }

        return redirect()->back()->with('success', 'Settings updated successfully.');
    }
}

