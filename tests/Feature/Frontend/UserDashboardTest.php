<?php

namespace Tests\Feature\Frontend;

use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_displays_user_stats(): void
    {
        $user = User::factory()->create();
        
        // Create some activity
        $posts = Post::factory()->count(3)->create(['status' => 'published']);
        Bookmark::factory()->count(2)->create(['user_id' => $user->id, 'post_id' => $posts[0]->id]);
        Comment::factory()->count(3)->create(['user_id' => $user->id, 'post_id' => $posts[1]->id]);
        Reaction::factory()->count(4)->create(['user_id' => $user->id, 'post_id' => $posts[2]->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Welcome back, ' . $user->name);
        $response->assertViewHas('stats', function ($stats) {
            return $stats['bookmarks_count'] === 2 &&
                   $stats['comments_count'] === 3 &&
                   $stats['reactions_count'] === 4;
        });
    }

    public function test_dashboard_displays_recent_activity(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['status' => 'published']);
        
        $bookmark = Bookmark::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);
        $comment = Comment::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);
        $reaction = Reaction::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk();
        $response->assertViewHas('recentBookmarks');
        $response->assertViewHas('recentComments');
        $response->assertViewHas('recentReactions');
    }

    public function test_bookmarks_page_displays_saved_articles(): void
    {
        $user = User::factory()->create();
        $posts = Post::factory()->count(3)->create(['status' => 'published']);
        
        foreach ($posts as $post) {
            Bookmark::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);
        }

        $response = $this->actingAs($user)->get(route('bookmarks.index'));

        $response->assertOk();
        $response->assertSee('My Reading List');
        foreach ($posts as $post) {
            $response->assertSee($post->title);
        }
    }

    public function test_bookmarks_page_can_filter_by_category(): void
    {
        $user = User::factory()->create();
        $posts = Post::factory()->count(3)->create(['status' => 'published']);
        
        foreach ($posts as $post) {
            Bookmark::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);
        }

        $categoryId = $posts[0]->category_id;

        $response = $this->actingAs($user)->get(route('bookmarks.index', ['category' => $categoryId]));

        $response->assertOk();
        $response->assertSee($posts[0]->title);
    }

    public function test_bookmarks_page_can_sort_by_title(): void
    {
        $user = User::factory()->create();
        $posts = Post::factory()->count(3)->create(['status' => 'published']);
        
        foreach ($posts as $post) {
            Bookmark::factory()->create(['user_id' => $user->id, 'post_id' => $post->id]);
        }

        $response = $this->actingAs($user)->get(route('bookmarks.index', ['sort' => 'title']));

        $response->assertOk();
    }

    public function test_profile_page_displays_user_information(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'bio' => 'Test bio',
        ]);

        $response = $this->actingAs($user)->get(route('profile.show'));

        $response->assertOk();
        $response->assertSee('John Doe');
        $response->assertSee('Test bio');
    }

    public function test_profile_page_displays_authored_posts_for_authors(): void
    {
        $author = User::factory()->create(['role' => 'author']);
        $posts = Post::factory()->count(3)->create([
            'user_id' => $author->id,
            'status' => 'published',
        ]);

        $response = $this->actingAs($author)->get(route('profile.show'));

        $response->assertOk();
        $response->assertSee('Published Articles');
        foreach ($posts as $post) {
            $response->assertSee($post->title);
        }
    }

    public function test_profile_edit_page_displays_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $response->assertOk();
        $response->assertSee('Profile Information');
        $response->assertSee($user->name);
        $response->assertSee($user->email);
    }

    public function test_user_can_update_profile_information(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('profile.update'), [
            'name' => 'Updated Name',
            'email' => $user->email,
            'bio' => 'Updated bio',
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('status', 'profile-updated');

        $user->refresh();
        $this->assertEquals('Updated Name', $user->name);
        $this->assertEquals('Updated bio', $user->bio);
    }
}
