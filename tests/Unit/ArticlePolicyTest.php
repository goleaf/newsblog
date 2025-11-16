<?php

namespace Tests\Unit;

use App\Enums\PostStatus;
use App\Enums\UserRole;
use App\Models\Post;
use App\Models\User;
use App\Policies\ArticlePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticlePolicyTest extends TestCase
{
    use RefreshDatabase;

    private ArticlePolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ArticlePolicy;
    }

    public function test_admin_can_view_any_articles(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->assertTrue($this->policy->viewAny($admin));
    }

    public function test_editor_can_view_any_articles(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);

        $this->assertTrue($this->policy->viewAny($editor));
    }

    public function test_author_can_view_any_articles(): void
    {
        $author = User::factory()->create(['role' => UserRole::Author]);

        $this->assertTrue($this->policy->viewAny($author));
    }

    public function test_reader_cannot_view_any_articles(): void
    {
        $reader = User::factory()->create(['role' => UserRole::Reader]);

        $this->assertFalse($this->policy->viewAny($reader));
    }

    public function test_anyone_can_view_published_article(): void
    {
        $article = Post::factory()->create(['status' => PostStatus::Published]);

        $this->assertTrue($this->policy->view(null, $article));
    }

    public function test_guest_cannot_view_draft_article(): void
    {
        $article = Post::factory()->create(['status' => PostStatus::Draft]);

        $this->assertFalse($this->policy->view(null, $article));
    }

    public function test_admin_can_view_any_article(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $article = Post::factory()->create(['status' => PostStatus::Draft]);

        $this->assertTrue($this->policy->view($admin, $article));
    }

    public function test_author_can_view_own_draft_article(): void
    {
        $author = User::factory()->create(['role' => UserRole::Author]);
        $article = Post::factory()->create([
            'user_id' => $author->id,
            'status' => PostStatus::Draft,
        ]);

        $this->assertTrue($this->policy->view($author, $article));
    }

    public function test_author_cannot_view_others_draft_article(): void
    {
        $author = User::factory()->create(['role' => UserRole::Author]);
        $otherAuthor = User::factory()->create(['role' => UserRole::Author]);
        $article = Post::factory()->create([
            'user_id' => $otherAuthor->id,
            'status' => PostStatus::Draft,
        ]);

        $this->assertFalse($this->policy->view($author, $article));
    }

    public function test_author_can_create_articles(): void
    {
        $author = User::factory()->create(['role' => UserRole::Author]);

        $this->assertTrue($this->policy->create($author));
    }

    public function test_reader_cannot_create_articles(): void
    {
        $reader = User::factory()->create(['role' => UserRole::Reader]);

        $this->assertFalse($this->policy->create($reader));
    }

    public function test_admin_can_update_any_article(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $article = Post::factory()->create();

        $this->assertTrue($this->policy->update($admin, $article));
    }

    public function test_author_can_update_own_article(): void
    {
        $author = User::factory()->create(['role' => UserRole::Author]);
        $article = Post::factory()->create(['user_id' => $author->id]);

        $this->assertTrue($this->policy->update($author, $article));
    }

    public function test_author_cannot_update_others_article(): void
    {
        $author = User::factory()->create(['role' => UserRole::Author]);
        $otherAuthor = User::factory()->create(['role' => UserRole::Author]);
        $article = Post::factory()->create(['user_id' => $otherAuthor->id]);

        $this->assertFalse($this->policy->update($author, $article));
    }

    public function test_admin_can_delete_any_article(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $article = Post::factory()->create();

        $this->assertTrue($this->policy->delete($admin, $article));
    }

    public function test_author_can_delete_own_article(): void
    {
        $author = User::factory()->create(['role' => UserRole::Author]);
        $article = Post::factory()->create(['user_id' => $author->id]);

        $this->assertTrue($this->policy->delete($author, $article));
    }

    public function test_author_cannot_delete_others_article(): void
    {
        $author = User::factory()->create(['role' => UserRole::Author]);
        $otherAuthor = User::factory()->create(['role' => UserRole::Author]);
        $article = Post::factory()->create(['user_id' => $otherAuthor->id]);

        $this->assertFalse($this->policy->delete($author, $article));
    }

    public function test_editor_can_publish_article(): void
    {
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $article = Post::factory()->create();

        $this->assertTrue($this->policy->publish($editor, $article));
    }

    public function test_admin_can_publish_article(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $article = Post::factory()->create();

        $this->assertTrue($this->policy->publish($admin, $article));
    }

    public function test_author_cannot_publish_article(): void
    {
        $author = User::factory()->create(['role' => UserRole::Author]);
        $article = Post::factory()->create(['user_id' => $author->id]);

        $this->assertFalse($this->policy->publish($author, $article));
    }

    public function test_only_admin_can_force_delete_article(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $editor = User::factory()->create(['role' => UserRole::Editor]);
        $article = Post::factory()->create();

        $this->assertTrue($this->policy->forceDelete($admin, $article));
        $this->assertFalse($this->policy->forceDelete($editor, $article));
    }
}
