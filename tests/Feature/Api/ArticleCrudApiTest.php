<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleCrudApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_author_can_create_article(): void
    {
        $author = User::factory()->author()->create();
        $category = Category::factory()->create();

        $payload = [
            'title' => 'New Post',
            'excerpt' => 'Short',
            'content' => 'Body',
            'status' => 'draft',
            'category_id' => $category->id,
        ];

        $res = $this->actingAs($author, 'sanctum')->postJson('/api/v1/articles', $payload);
        $res->assertCreated();
        $this->assertDatabaseHas('posts', ['title' => 'New Post', 'user_id' => $author->id]);
    }

    public function test_author_can_update_own_article(): void
    {
        $author = User::factory()->author()->create();
        $post = Post::factory()->for($author)->create(['status' => 'draft']);

        $res = $this->actingAs($author, 'sanctum')->putJson('/api/v1/articles/'.$post->id, [
            'title' => 'Updated',
            'excerpt' => 'Short',
            'content' => 'Body',
            'status' => 'draft',
            'category_id' => $post->category_id ?? Category::factory()->create()->id,
        ]);
        $res->assertOk();
        $this->assertDatabaseHas('posts', ['id' => $post->id, 'title' => 'Updated']);
    }

    public function test_admin_can_delete_article(): void
    {
        $admin = User::factory()->admin()->create();
        $post = Post::factory()->create();

        $res = $this->actingAs($admin, 'sanctum')->deleteJson('/api/v1/articles/'.$post->id);
        $res->assertNoContent();
        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }
}
