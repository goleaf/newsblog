<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SanctumTokenApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_list_and_revoke_tokens(): void
    {
        $user = User::factory()->create();

        // Create token
        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tokens', [
            'name' => 'CLI',
            'abilities' => ['articles:read', 'comments:write'],
        ]);
        $res->assertCreated();
        $tokenId = $res->json('token_id');
        $this->assertNotEmpty($res->json('token'));

        // List tokens
        $list = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tokens');
        $list->assertOk();
        $this->assertGreaterThanOrEqual(1, $list->json('total'));

        // Revoke token
        $del = $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/tokens/'.$tokenId);
        $del->assertNoContent();

        $this->assertDatabaseMissing('personal_access_tokens', [
            'id' => $tokenId,
        ]);
    }

    public function test_user_cannot_delete_someone_elses_token(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $token = $alice->createToken('X');
        $tokenId = $token->accessToken->id;

        $res = $this->actingAs($bob, 'sanctum')->deleteJson('/api/v1/tokens/'.$tokenId);
        $res->assertStatus(403);
    }
}
