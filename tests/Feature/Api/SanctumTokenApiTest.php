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
            'abilities' => ['articles:read', 'comments:create'],
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

    public function test_user_can_list_available_abilities(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tokens/abilities');
        $res->assertOk();
        $res->assertJsonStructure(['abilities']);

        $abilities = $res->json('abilities');
        $this->assertIsArray($abilities);
        $this->assertArrayHasKey('*', $abilities);
        $this->assertArrayHasKey('articles:read', $abilities);
        $this->assertArrayHasKey('comments:create', $abilities);
    }

    public function test_user_can_create_token_with_expiration(): void
    {
        $user = User::factory()->create();
        $expiresAt = now()->addDays(30)->toISOString();

        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tokens', [
            'name' => 'Temporary Token',
            'abilities' => ['articles:read'],
            'expires_at' => $expiresAt,
        ]);

        $res->assertCreated();
        $res->assertJsonStructure(['token', 'token_id', 'name', 'abilities', 'expires_at']);
        $this->assertNotNull($res->json('expires_at'));
    }

    public function test_user_can_update_token_abilities(): void
    {
        $user = User::factory()->create();

        // Create token with initial abilities
        $token = $user->createToken('Test Token', ['articles:read']);
        $tokenId = $token->accessToken->id;

        // Update abilities
        $res = $this->actingAs($user, 'sanctum')->putJson('/api/v1/tokens/'.$tokenId, [
            'abilities' => ['articles:read', 'articles:create', 'comments:read'],
        ]);

        $res->assertOk();
        $res->assertJson([
            'id' => $tokenId,
            'abilities' => ['articles:read', 'articles:create', 'comments:read'],
        ]);

        // Verify in database
        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $tokenId,
        ]);
    }

    public function test_user_cannot_update_someone_elses_token(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();

        $token = $alice->createToken('Alice Token', ['articles:read']);
        $tokenId = $token->accessToken->id;

        $res = $this->actingAs($bob, 'sanctum')->putJson('/api/v1/tokens/'.$tokenId, [
            'abilities' => ['*'],
        ]);

        $res->assertStatus(403);
    }

    public function test_token_creation_validates_abilities(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tokens', [
            'name' => 'Invalid Token',
            'abilities' => ['invalid:ability', 'another:invalid'],
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['abilities.0', 'abilities.1']);
    }

    public function test_token_creation_validates_expiration_date(): void
    {
        $user = User::factory()->create();

        // Test with past date
        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tokens', [
            'name' => 'Expired Token',
            'expires_at' => now()->subDay()->toISOString(),
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['expires_at']);
    }

    public function test_token_creation_requires_name(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user, 'sanctum')->postJson('/api/v1/tokens', [
            'abilities' => ['articles:read'],
        ]);

        $res->assertStatus(422);
        $res->assertJsonValidationErrors(['name']);
    }

    public function test_api_responses_include_rate_limit_headers(): void
    {
        $user = User::factory()->create();

        $res = $this->actingAs($user, 'sanctum')->getJson('/api/v1/tokens');

        $res->assertOk();
        $res->assertHeader('X-RateLimit-Limit');
        $res->assertHeader('X-RateLimit-Remaining');
    }
}
