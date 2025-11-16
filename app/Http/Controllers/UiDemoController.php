<?php

namespace App\Http\Controllers;

use App\Http\Requests\UiDemoRequest;
use Illuminate\Contracts\View\View;

class UiDemoController extends Controller
{
    public function show(UiDemoRequest $request): View
    {
        $galleryImages = [
            ['src' => '/img/sample1.jpg', 'thumb' => '/img/sample1.jpg', 'alt' => 'Sample 1'],
            ['src' => '/img/sample2.jpg', 'thumb' => '/img/sample2.jpg', 'alt' => 'Sample 2'],
            ['src' => '/img/sample3.jpg', 'thumb' => '/img/sample3.jpg', 'alt' => 'Sample 3'],
        ];

        $chartCsv = "label,value\nA,10\nB,20\nC,15\nD,30";

        return view('ui.demo', [
            'galleryImages' => $galleryImages,
            'chartCsv' => $chartCsv,
        ]);
    }
}


