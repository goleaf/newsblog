<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class MaintenanceController extends Controller
{
    public function index()
    {
        $isEnabled = File::exists(storage_path('framework/down'));
        $downFile = $isEnabled ? json_decode(File::get(storage_path('framework/down')), true) : null;

        return view('admin.maintenance.index', compact('isEnabled', 'downFile'));
    }

    public function toggle(Request $request)
    {
        $file = storage_path('framework/down');

        if ($request->action === 'enable') {
            File::put($file, json_encode([
                'time' => now()->timestamp,
                'retry' => 60,
                'secret' => bin2hex(random_bytes(16)),
                'message' => $request->message ?? 'We are currently performing maintenance. Please check back soon.',
            ], JSON_PRETTY_PRINT));

            return redirect()->route('admin.maintenance.index')
                ->with('success', 'Maintenance mode enabled.');
        } else {
            if (File::exists($file)) {
                File::delete($file);
            }

            return redirect()->route('admin.maintenance.index')
                ->with('success', 'Maintenance mode disabled.');
        }
    }
}
