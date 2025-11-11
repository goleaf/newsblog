<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\PostView;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PostController extends Controller
{
    public function show($slug)
    {
        $post = Cache::remember("post.{$slug}", 3600, function () use ($slug) {
            return Post::where('slug', $slug)
                ->published()
                ->with(['user', 'category', 'tags', 'reactions', 'comments' => function ($query) {
                    $query->where('status', 'approved')->orderBy('created_at', 'desc');
                }])
                ->firstOrFail();
        });

        // Track view (don't cache this)
        PostView::create([
            'post_id' => $post->id,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'viewed_at' => now(),
        ]);

        $post->incrementViewCount();

        $relatedPosts = Cache::remember("post.{$post->id}.related", 3600, function () use ($post) {
            return Post::published()
                ->where('category_id', $post->category_id)
                ->where('id', '!=', $post->id)
                ->take(4)
                ->get();
        });

        return view('posts.show', compact('post', 'relatedPosts'));
    }

    public function category($slug)
    {
        $category = Cache::remember("category.{$slug}", 3600, function () use ($slug) {
            return Category::where('slug', $slug)->active()->firstOrFail();
        });

        $posts = Post::published()
            ->where('category_id', $category->id)
            ->latest()
            ->paginate(12);

        return view('categories.show', compact('category', 'posts'));
    }

    public function tag($slug)
    {
        $tag = Cache::remember("tag.{$slug}", 3600, function () use ($slug) {
            return Tag::where('slug', $slug)->firstOrFail();
        });

        $posts = $tag->posts()->published()->latest()->paginate(12);

        return view('tags.show', compact('tag', 'posts'));
    }

    public function search(Request $request)
    {
        $query = $request->get('q');

        if (empty($query)) {
            return view('search', ['posts' => collect([]), 'query' => '']);
        }

        $posts = Post::published()
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('content', 'like', "%{$query}%")
                    ->orWhere('excerpt', 'like', "%{$query}%");
            })
            ->latest()
            ->paginate(12);

        return view('search', compact('posts', 'query'));
    }
}
