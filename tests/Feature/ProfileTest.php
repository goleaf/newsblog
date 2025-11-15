<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    // Profile Display Tests

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
        $response->assertSee($user->name);
        $response->assertSee(ucfirst($user->role));
    }

    public function test_profile_displays_user_stats(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
        $response->assertSee('Bookmarks');
        $response->assertSee('Comments');
        $response->assertSee('Reactions');
    }

    public function test_profile_displays_bio_when_present(): void
    {
        $user = User::factory()->create([
            'bio' => 'This is my test bio',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
        $response->assertSee('This is my test bio');
    }

    public function test_profile_displays_authored_posts_for_authors(): void
    {
        $user = User::factory()->create(['role' => 'author']);
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'status' => 'published',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
        $response->assertSee('Published Articles');
        $response->assertSee($post->title);
    }

    public function test_profile_displays_recent_comments(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['status' => 'published']);
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'This is my test comment',
        ]);

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
        $response->assertSee('Recent Comments');
        $response->assertSee('This is my test comment');
    }

    // Profile Edit Form Tests

    public function test_profile_edit_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile/edit');

        $response->assertOk();
        $response->assertSee('Profile Information');
        $response->assertSee('Email Preferences');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile/edit');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_profile_bio_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'bio' => 'This is my updated bio',
            ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertSame('This is my updated bio', $user->bio);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile/edit');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    // Avatar Upload Tests

    public function test_avatar_can_be_uploaded(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg');

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $file,
            ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();

        $this->assertNotNull($user->avatar);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_old_avatar_is_deleted_when_new_avatar_is_uploaded(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        
        // Upload first avatar
        $oldFile = UploadedFile::fake()->image('old-avatar.jpg');
        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $oldFile,
        ]);

        $user->refresh();
        $oldAvatarPath = $user->avatar;

        // Upload new avatar
        $newFile = UploadedFile::fake()->image('new-avatar.jpg');
        $this->actingAs($user)->patch('/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'avatar' => $newFile,
        ]);

        $user->refresh();

        Storage::disk('public')->assertMissing($oldAvatarPath);
        Storage::disk('public')->assertExists($user->avatar);
    }

    public function test_avatar_must_be_an_image(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $file,
            ]);

        $response->assertSessionHasErrors('avatar');
    }

    public function test_avatar_must_not_exceed_size_limit(): void
    {
        Storage::fake('public');

        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('avatar.jpg')->size(3000); // 3MB

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $file,
            ]);

        $response->assertSessionHasErrors('avatar');
    }

    // Email Preferences Tests

    public function test_email_preferences_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile/email-preferences', [
                'preferences' => [
                    'comment_replies' => '1',
                    'post_published' => '1',
                    'series_updated' => '1',
                    'frequency' => 'daily',
                ],
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect('/profile/edit');

        $user->refresh();
        $preferences = $user->getEmailPreferences();

        $this->assertTrue($preferences['comment_replies']);
        $this->assertTrue($preferences['post_published']);
        $this->assertFalse($preferences['comment_approved']);
        $this->assertTrue($preferences['series_updated']);
        $this->assertFalse($preferences['newsletter']);
        $this->assertSame('daily', $preferences['frequency']);
    }

    public function test_email_preferences_frequency_must_be_valid(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile/email-preferences', [
                'preferences' => [
                    'frequency' => 'invalid',
                ],
            ]);

        $response->assertSessionHasErrors('preferences.frequency');
    }

    public function test_email_preferences_can_be_set_to_weekly(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile/email-preferences', [
                'preferences' => [
                    'frequency' => 'weekly',
                ],
            ]);

        $response->assertSessionHasNoErrors();

        $user->refresh();
        $preferences = $user->getEmailPreferences();

        $this->assertSame('weekly', $preferences['frequency']);
    }

    // Validation Tests

    public function test_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => '',
                'email' => $user->email,
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_email_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => '',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_email_must_be_valid(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => 'invalid-email',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_email_must_be_unique(): void
    {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => 'existing@example.com',
            ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_bio_cannot_exceed_max_length(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => $user->name,
                'email' => $user->email,
                'bio' => str_repeat('a', 501), // 501 characters
            ]);

        $response->assertSessionHasErrors('bio');
    }

    // Account Deletion Tests

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
