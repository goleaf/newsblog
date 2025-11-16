<?php

namespace Tests\Feature\Api;

use App\Models\BookmarkCollection;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingListSharingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_share_and_access_shared_list(): void
    {
        $user = User::factory()->create();
        $list = BookmarkCollection::factory()->create(['user_id' => $user->id, 'is_public' => false]);

        $post = Post::factory()->create(['status' => 'published', 'published_at' => now()->subDay()]);
        \App\Models\Bookmark::factory()->create(['user_id' => $user->id, 'post_id' => $post->id, 'collection_id' => $list->id]);

        // Generate share link
        $share = $this->actingAs($user, 'sanctum')->postJson('/api/v1/reading-lists/'.$list->id.'/share');
        $share->assertOk();
        $token = $share->json('share_token');
        $this->assertNotEmpty($token);

        // Access via public shared endpoint (no auth)
        $public = $this->getJson('/api/v1/reading-lists/shared/'.$token);
        $public->assertOk();
        $returnedId = $public->json('data.id') ?? $public->json('id');
        $this->assertEquals($list->id, $returnedId);

        // Revoke share
        $revoke = $this->actingAs($user, 'sanctum')->deleteJson('/api/v1/reading-lists/'.$list->id.'/share');
        $revoke->assertNoContent();

        $this->getJson('/api/v1/reading-lists/shared/'.$token)->assertStatus(404);
    }
}
