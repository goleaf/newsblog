<?php

namespace App\Http\Controllers;

use App\Http\Requests\UiDemoRequest;
use Illuminate\Contracts\View\View;

class UiDemoController extends Controller
{
    public function show(UiDemoRequest $request): View
    {
        $galleryImages = [
            // Use an existing asset to avoid missing files during e2e runs
            ['src' => '/img/nova-logo.svg', 'thumb' => '/img/nova-logo.svg', 'alt' => 'Sample 1'],
            ['src' => '/img/nova-logo.svg', 'thumb' => '/img/nova-logo.svg', 'alt' => 'Sample 2'],
            ['src' => '/img/nova-logo.svg', 'thumb' => '/img/nova-logo.svg', 'alt' => 'Sample 3'],
        ];

        $chartCsv = "label,value\nA,10\nB,20\nC,15\nD,30";

        return view('ui.demo', [
            'galleryImages' => $galleryImages,
            'chartCsv' => $chartCsv,
        ]);
    }
}
