<?php

namespace Tests\Feature\Admin;

use App\Models\Post;
use App\Models\PostRevision;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class PostRevisionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('cache.default', 'array');
    }

    public function test_admin_can_view_post_revisions_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create(['user_id' => $admin->id]);
        PostRevision::factory()->count(2)->create([
            'post_id' => $post->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.posts.revisions.index', $post));

        $response->assertOk()
            ->assertViewIs('admin.posts.revisions.index')
            ->assertViewHas('revisions', function ($revisions) {
                return $revisions->count() === 2;
            });
    }

    public function test_admin_can_view_single_revision(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create(['user_id' => $admin->id]);
        $revision = PostRevision::factory()->create([
            'post_id' => $post->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.posts.revisions.show', [$post, $revision]));

        $response->assertOk()
            ->assertViewIs('admin.posts.revisions.show')
            ->assertViewHas('revision', fn ($viewRevision) => $viewRevision->id === $revision->id);
    }

    public function test_compare_view_displays_diff_between_revisions(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create(['user_id' => $admin->id]);
        $oldRevision = PostRevision::factory()->create([
            'post_id' => $post->id,
            'user_id' => $admin->id,
            'title' => 'Old title',
            'content' => 'Old content',
        ]);
        $newRevision = PostRevision::factory()->create([
            'post_id' => $post->id,
            'user_id' => $admin->id,
            'title' => 'New title',
            'content' => 'New content',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.posts.revisions.compare', [
            'post' => $post->id,
            'old_revision_id' => $oldRevision->id,
            'new_revision_id' => $newRevision->id,
        ]));

        $response->assertOk()
            ->assertViewIs('admin.posts.revisions.compare')
            ->assertViewHas('diff', function ($diff) {
                return isset($diff['title'], $diff['content']);
            });
    }

    public function test_compare_returns_not_found_for_mismatched_post(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create(['user_id' => $admin->id]);
        $otherPost = Post::factory()->create(['user_id' => $admin->id]);
        $oldRevision = PostRevision::factory()->create([
            'post_id' => $post->id,
            'user_id' => $admin->id,
        ]);
        $newRevision = PostRevision::factory()->create([
            'post_id' => $otherPost->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.posts.revisions.compare', [
            'post' => $post->id,
            'old_revision_id' => $oldRevision->id,
            'new_revision_id' => $newRevision->id,
        ]));

        $response->assertNotFound();
    }

    public function test_admin_can_restore_revision(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create(['user_id' => $admin->id, 'title' => 'Original title']);
        $revision = PostRevision::factory()->create([
            'post_id' => $post->id,
            'user_id' => $admin->id,
            'title' => 'Restored title',
            'content' => 'Restored content',
            'meta_data' => [
                'slug' => 'restored-title',
                'status' => 'published',
                'featured_image' => null,
                'meta_title' => 'Restored meta title',
                'meta_description' => 'Restored meta description',
                'meta_keywords' => 'restored',
            ],
        ]);

        $response = $this->actingAs($admin)->post(route('admin.posts.revisions.restore', [$post, $revision]));

        $response->assertRedirect(route('admin.posts.revisions.index', $post))
            ->assertSessionHas('success');

        $this->assertEquals('Restored title', $post->fresh()->title);
    }

    public function test_admin_can_delete_revision(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $post = Post::factory()->create(['user_id' => $admin->id]);
        $revision = PostRevision::factory()->create([
            'post_id' => $post->id,
            'user_id' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.posts.revisions.destroy', [$post, $revision]));

        $response->assertRedirect(route('admin.posts.revisions.index', $post))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('post_revisions', ['id' => $revision->id]);
    }

    public function test_non_admin_cannot_manage_post_revisions(): void
    {
        $user = User::factory()->create(['role' => 'author']);
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->get(route('admin.posts.revisions.index', $post));

        $response->assertStatus(403);
    }
}
