<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $tokens = $request->user()->tokens()->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'data' => $tokens->map(function ($token) {
                return [
                    'id' => $token->id,
                    'name' => $token->name,
                    'abilities' => $token->abilities,
                    'last_used_at' => $token->last_used_at?->toISOString(),
                    'expires_at' => $token->expires_at?->toISOString(),
                    'created_at' => $token->created_at?->toISOString(),
                ];
            }),
            'total' => $tokens->total(),
            'links' => [
                'next' => $tokens->nextPageUrl(),
                'prev' => $tokens->previousPageUrl(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => ['string'],
            'expires_at' => ['nullable', 'date'],
        ]);

        $abilities = $validated['abilities'] ?? ['*'];
        $expiresAt = isset($validated['expires_at']) ? Carbon::parse($validated['expires_at']) : null;

        $token = $request->user()->createToken($validated['name'], $abilities, $expiresAt);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_id' => $token->accessToken->id,
            'name' => $token->accessToken->name,
            'abilities' => $token->accessToken->abilities,
            'expires_at' => $token->accessToken->expires_at?->toISOString(),
        ], 201);
    }

    public function destroy(Request $request, int $tokenId): JsonResponse
    {
        $token = PersonalAccessToken::findOrFail($tokenId);

        if ($token->tokenable_id !== $request->user()->getKey() || $token->tokenable_type !== get_class($request->user())) {
            abort(403);
        }

        $token->delete();

        return response()->json(null, 204);
    }
}
