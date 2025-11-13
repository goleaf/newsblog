<?php

namespace Tests\Feature\Feature\Services;

use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use App\Services\GdprService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GdprServiceTest extends TestCase
{
    use RefreshDatabase;

    protected GdprService $gdprService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gdprService = app(GdprService::class);
    }

    public function test_exports_user_data_correctly(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);
        $bookmark = Bookmark::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);

        $data = $this->gdprService->exportUserData($user);

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('posts', $data);
        $this->assertArrayHasKey('comments', $data);
        $this->assertArrayHasKey('bookmarks', $data);
        $this->assertEquals($user->email, $data['user']['email']);
        $this->assertCount(1, $data['posts']);
        $this->assertCount(1, $data['comments']);
        $this->assertCount(1, $data['bookmarks']);
    }

    public function test_anonymizes_user_data(): void
    {
        $user = User::factory()->create(['email' => 'test@example.com']);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'author_email' => 'test@example.com',
        ]);

        $this->gdprService->anonymizeUser($user);

        $user->refresh();
        $comment->refresh();

        $this->assertEquals('Deleted User', $user->name);
        $this->assertStringContainsString('deleted_', $user->email);
        $this->assertEquals('deleted', $user->status);
        $this->assertEquals('Deleted User', $comment->author_name);
    }

    public function test_deletes_user_account_and_data(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create(['user_id' => $user->id]);
        $bookmark = Bookmark::factory()->create(['user_id' => $user->id]);

        $userId = $user->id;

        $this->gdprService->deleteUserAccount($user);

        $this->assertDatabaseMissing('users', ['id' => $userId]);
        $this->assertDatabaseMissing('posts', ['user_id' => $userId]);
        $this->assertDatabaseMissing('comments', ['user_id' => $userId]);
        $this->assertDatabaseMissing('bookmarks', ['user_id' => $userId]);
    }

    public function test_checks_cookie_consent(): void
    {
        $this->assertFalse($this->gdprService->hasConsent());

        $this->withCookie('gdpr_consent', 'accepted');
        $this->assertTrue($this->gdprService->hasConsent());
    }

    public function test_stores_consent_cookie(): void
    {
        $cookie = $this->gdprService->storeConsent(true);

        $this->assertEquals('gdpr_consent', $cookie->getName());
        $this->assertEquals('accepted', $cookie->getValue());
    }

    public function test_withdraws_consent(): void
    {
        $cookies = $this->gdprService->withdrawConsent();

        $this->assertIsArray($cookies);
        $this->assertNotEmpty($cookies);
    }
}
