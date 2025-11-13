<?php

namespace Tests\Feature;

use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use App\Services\GdprService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GdprComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_cookie_consent_component_exists(): void
    {
        $this->assertTrue(file_exists(resource_path('views/components/cookie-consent.blade.php')));
    }

    public function test_user_can_accept_cookie_consent(): void
    {
        $response = $this->postJson(route('gdpr.accept-consent'));

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertCookie('gdpr_consent', 'accepted');
    }

    public function test_user_can_decline_cookie_consent(): void
    {
        $response = $this->postJson(route('gdpr.decline-consent'));

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertCookie('gdpr_consent', 'declined');
    }

    public function test_authenticated_user_can_export_their_data(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->get(route('gdpr.export-data'));

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonStructure([
                'user' => ['id', 'name', 'email', 'role'],
                'posts',
                'comments',
                'bookmarks',
                'reactions',
                'media',
            ]);

        $data = $response->json();
        $this->assertEquals($user->id, $data['user']['id']);
        $this->assertCount(1, $data['posts']);
        $this->assertCount(1, $data['comments']);
    }

    public function test_guest_cannot_export_data(): void
    {
        $response = $this->get(route('gdpr.export-data'));

        $response->assertStatus(302)
            ->assertRedirect(route('login'));
    }

    public function test_user_can_view_delete_account_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('gdpr.show-delete-account'));

        $response->assertStatus(200)
            ->assertViewIs('gdpr.delete-account');
    }

    public function test_user_can_delete_account_with_valid_password(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($user)
            ->delete(route('gdpr.delete-account'), [
                'password' => 'password123',
                'confirm_deletion' => '1',
            ]);

        $response->assertStatus(302)
            ->assertRedirect(route('home'))
            ->assertSessionHas('success');

        // User should be anonymized
        $user->refresh();
        $this->assertEquals('Deleted User', $user->name);
        $this->assertEquals('deleted', $user->status);
        $this->assertStringStartsWith('deleted_', $user->email);
    }

    public function test_account_deletion_requires_password_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($user)
            ->delete(route('gdpr.delete-account'), [
                'password' => 'wrongpassword',
                'confirm_deletion' => '1',
            ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('password');
    }

    public function test_account_deletion_requires_confirmation_checkbox(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('password123'),
        ]);

        $response = $this->actingAs($user)
            ->delete(route('gdpr.delete-account'), [
                'password' => 'password123',
                'confirm_deletion' => '0',
            ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('confirm_deletion');
    }

    public function test_gdpr_service_exports_all_user_data(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['user_id' => $user->id]);
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $gdprService = new GdprService;
        $data = $gdprService->exportUserData($user);

        $this->assertArrayHasKey('user', $data);
        $this->assertArrayHasKey('posts', $data);
        $this->assertArrayHasKey('comments', $data);
        $this->assertArrayHasKey('bookmarks', $data);
        $this->assertArrayHasKey('reactions', $data);
        $this->assertArrayHasKey('media', $data);

        $this->assertEquals($user->id, $data['user']['id']);
        $this->assertCount(1, $data['posts']);
        $this->assertCount(1, $data['comments']);
    }

    public function test_gdpr_service_anonymizes_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'bio' => 'Test bio',
        ]);

        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
        ]);

        $gdprService = new GdprService;
        $gdprService->anonymizeUser($user);

        $user->refresh();
        $this->assertEquals('Deleted User', $user->name);
        $this->assertEquals('deleted', $user->status);
        $this->assertStringStartsWith('deleted_', $user->email);
        $this->assertNull($user->bio);

        $comment->refresh();
        $this->assertEquals('Deleted User', $comment->author_name);
        $this->assertEquals('deleted@deleted.local', $comment->author_email);
    }

    public function test_gdpr_service_deletes_user_bookmarks_and_reactions(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        // Create bookmark manually
        $bookmark = \DB::table('bookmarks')->insertGetId([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create reaction manually
        $reaction = \DB::table('reactions')->insertGetId([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'type' => 'like',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $gdprService = new GdprService;
        $gdprService->anonymizeUser($user);

        $this->assertDatabaseMissing('bookmarks', ['id' => $bookmark]);
        $this->assertDatabaseMissing('reactions', ['id' => $reaction]);
    }

    public function test_user_can_withdraw_consent(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('gdpr.withdraw-consent'));

        $response->assertStatus(302)
            ->assertSessionHas('success');
    }

    public function test_privacy_policy_page_is_accessible(): void
    {
        $response = $this->get(route('gdpr.privacy-policy'));

        $response->assertStatus(200)
            ->assertViewIs('gdpr.privacy-policy');
    }
}
