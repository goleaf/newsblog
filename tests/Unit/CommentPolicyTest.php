<?php

namespace Tests\Unit;

use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\User;
use App\Policies\CommentPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentPolicyTest extends TestCase
{
    use RefreshDatabase;

    private CommentPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new CommentPolicy;
    }

    public function test_authenticated_user_can_create_comment(): void
    {
        $user = User::factory()->create(['role' => UserRole::Reader]);

        $this->assertTrue($this->policy->create($user));
    }

    public function test_moderator_can_view_any_comment(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);

        $this->assertTrue($this->policy->viewAny($moderator));
    }

    public function test_admin_can_view_any_comment(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->assertTrue($this->policy->viewAny($admin));
    }

    public function test_reader_cannot_view_any_comments(): void
    {
        $reader = User::factory()->create(['role' => UserRole::Reader]);

        $this->assertFalse($this->policy->viewAny($reader));
    }

    public function test_user_can_view_own_comment(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->view($user, $comment));
    }

    public function test_moderator_can_view_any_comment_detail(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $comment = Comment::factory()->create();

        $this->assertTrue($this->policy->view($moderator, $comment));
    }

    public function test_user_can_update_own_comment(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->update($user, $comment));
    }

    public function test_user_cannot_update_others_comment(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->update($user, $comment));
    }

    public function test_moderator_can_update_any_comment(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $comment = Comment::factory()->create();

        $this->assertTrue($this->policy->update($moderator, $comment));
    }

    public function test_user_can_delete_own_comment(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($this->policy->delete($user, $comment));
    }

    public function test_user_cannot_delete_others_comment(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $otherUser->id]);

        $this->assertFalse($this->policy->delete($user, $comment));
    }

    public function test_moderator_can_delete_any_comment(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $comment = Comment::factory()->create();

        $this->assertTrue($this->policy->delete($moderator, $comment));
    }

    public function test_moderator_can_moderate_comment(): void
    {
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $comment = Comment::factory()->create();

        $this->assertTrue($this->policy->moderate($moderator, $comment));
    }

    public function test_admin_can_moderate_comment(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $comment = Comment::factory()->create();

        $this->assertTrue($this->policy->moderate($admin, $comment));
    }

    public function test_author_cannot_moderate_comment(): void
    {
        $author = User::factory()->create(['role' => UserRole::Author]);
        $comment = Comment::factory()->create();

        $this->assertFalse($this->policy->moderate($author, $comment));
    }

    public function test_only_admin_can_force_delete_comment(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $comment = Comment::factory()->create();

        $this->assertTrue($this->policy->forceDelete($admin, $comment));
        $this->assertFalse($this->policy->forceDelete($moderator, $comment));
    }
}
