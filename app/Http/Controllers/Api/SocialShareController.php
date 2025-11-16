<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\SocialShare;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocialShareController extends Controller
{
    /**
     * Track a social share event.
     *
     * @group Social
     *
     * @bodyParam post_id integer required The post being shared. Example: 1
     * @bodyParam provider string required One of twitter, facebook, linkedin, reddit. Example: twitter
     * @bodyParam share_url string The full share URL. Example: https://twitter.com/intent/tweet?text=...
     *
     * @response 201 {"post_id":1,"provider":"twitter","total_shares":5}
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'provider' => ['required', 'string', 'in:twitter,facebook,linkedin,reddit'],
            'share_url' => ['nullable', 'url', 'max:500'],
        ]);

        $post = Post::findOrFail($validated['post_id']);

        SocialShare::create([
            'post_id' => $post->id,
            'user_id' => $request->user()?->id,
            'provider' => $validated['provider'],
            'share_url' => $validated['share_url'] ?? null,
            'shared_at' => now(),
        ]);

        $total = SocialShare::where('post_id', $post->id)->count();

        return response()->json([
            'post_id' => $post->id,
            'provider' => $validated['provider'],
            'total_shares' => $total,
        ], 201);
    }
}
