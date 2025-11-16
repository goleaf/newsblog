<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class TokenController extends Controller
{
    /**
     * List API tokens for the authenticated user.
     *
     * @group Auth Tokens
     *
     * @authenticated
     *
     * @response 200 {"data": [{"id": 1, "name": "CLI", "abilities": ["*"], "last_used_at": null, "expires_at": null, "created_at": "2025-01-01T00:00:00Z"}], "total": 1, "links": {"next": null, "prev": null}}
     */
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

    /**
     * Create a new API token.
     *
     * @group Auth Tokens
     *
     * @authenticated
     *
     * @bodyParam name string required A descriptive token name. Example: CLI
     * @bodyParam abilities string[] The token abilities. Defaults to ["*"]. Example: ["articles:read"]
     * @bodyParam expires_at date Token expiration ISO string. Example: 2025-12-31T23:59:59Z
     *
     * @response 201 {"token":"...","token_id":1,"name":"CLI","abilities":["*"],"expires_at":null}
     */
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

    /**
     * Revoke an API token by id.
     *
     * @group Auth Tokens
     *
     * @authenticated
     *
     * @urlParam tokenId integer required Token id to revoke. Example: 1
     *
     * @response 204 {}
     */
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
