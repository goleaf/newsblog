<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function index(Request $request)
    {
        $query = Comment::query()->with('post');

        if ($search = $request->query('search')) {
            $query->where('content', 'like', "%{$search}%");
        }

        $comments = $query->latest()->limit(100)->get();

        return view('admin.comments.index', compact('comments'));
    }
}
