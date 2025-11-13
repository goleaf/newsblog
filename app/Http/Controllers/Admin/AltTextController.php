<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\Post;
use App\Services\AltTextValidator;
use Illuminate\Http\Request;

class AltTextController extends Controller
{
    public function __construct(
        private AltTextValidator $validator
    ) {}

    /**
     * Display accessibility report
     */
    public function report()
    {
        $report = $this->validator->generateAccessibilityReport();

        return view('admin.alt-text.report', [
            'summary' => $report['summary'],
            'postsWithIssues' => $report['posts_with_issues'],
            'mediaWithoutAlt' => $report['media_without_alt'],
        ]);
    }

    /**
     * Display bulk edit interface for media alt text
     */
    public function bulkEdit()
    {
        $mediaItems = $this->validator->getMediaWithoutAltText();

        return view('admin.alt-text.bulk-edit', [
            'mediaItems' => $mediaItems,
        ]);
    }

    /**
     * Update alt text for multiple media items
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'alt_texts' => 'required|array',
            'alt_texts.*' => 'nullable|string|max:255',
        ]);

        $updated = 0;
        foreach ($validated['alt_texts'] as $mediaId => $altText) {
            if (! empty($altText)) {
                Media::where('id', $mediaId)->update(['alt_text' => $altText]);
                $updated++;
            }
        }

        return redirect()
            ->route('admin.alt-text.bulk-edit')
            ->with('success', "Updated alt text for {$updated} image(s)");
    }

    /**
     * Validate a specific post for alt text issues
     */
    public function validatePost(Post $post)
    {
        $issues = $this->validator->validatePost($post);

        return response()->json([
            'has_issues' => ! empty($issues),
            'issues' => $issues,
        ]);
    }
}
