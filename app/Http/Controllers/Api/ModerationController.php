<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CommentFlag;
use Illuminate\Http\Request;

class ModerationController extends Controller
{
    public function flagsIndex(Request $request)
    {
        $status = $request->query('status', 'open');

        $flags = CommentFlag::query()
            ->where('status', $status)
            ->latest()
            ->with(['comment:id,post_id,author_name,author_email,content,status', 'user:id,name,email'])
            ->paginate(15);

        return response()->json($flags);
    }

    public function review(Request $request, CommentFlag $flag)
    {
        $request->validate([
            'status' => ['required', 'in:reviewed,resolved,rejected'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        $flag->update([
            'status' => $request->input('status'),
            'notes' => $request->input('notes', $flag->notes),
        ]);

        return response()->json([
            'success' => true,
            'status' => $flag->status,
        ]);
    }

    public function bulkReview(Request $request)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:comment_flags,id'],
            'status' => ['required', 'in:reviewed,resolved,rejected'],
        ]);

        $updated = CommentFlag::whereIn('id', $data['ids'])
            ->update([
                'status' => $data['status'],
            ]);

        return response()->json([
            'success' => true,
            'updated' => $updated,
            'status' => $data['status'],
        ]);
    }
}
