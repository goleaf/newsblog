<?php

namespace Tests\Feature\Frontend;

use App\Models\Bookmark;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Reaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EngagementComponentsTest extends TestCase
{
    use RefreshDatabase;

    // Reaction Buttons Component Tests

    public function test_reaction_buttons_component_renders_correctly(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('x-data')
            ->assertSee('reactions:')
            ->assertSee('userReaction:');
    }

    public function test_reaction_buttons_show_correct_counts(): void
    {
        $post = Post::factory()->create(['status' => 'published']);
        $users = User::factory()->count(3)->create();

        // Create reactions
        Reaction::create(['post_id' => $post->id, 'user_id' => $users[0]->id, 'type' => 'like']);
        Reaction::create(['post_id' => $post->id, 'user_id' => $users[1]->id, 'type' => 'like']);
        Reaction::create(['post_id' => $post->id, 'user_id' => $users[2]->id, 'type' => 'love']);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200);

        // Check that reaction counts are present in the component data
        $this->assertTrue(str_contains($response->content(), '"like":2'));
        $this->assertTrue(str_contains($response->content(), '"love":1'));
    }

    public function test_reaction_buttons_show_user_reaction_when_authenticated(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['status' => 'published']);

        Reaction::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'type' => 'like',
        ]);

        $response = $this->actingAs($user)->get(route('post.show', $post->slug));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->content(), 'userReaction: "like"'));
    }

    // Bookmark Button Component Tests

    public function test_bookmark_button_component_renders_correctly(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('bookmarked:')
            ->assertSee('toggle');
    }

    public function test_bookmark_button_shows_correct_state_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create(['status' => 'published']);

        // Not bookmarked
        $response = $this->actingAs($user)->get(route('post.show', $post->slug));
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->content(), 'bookmarked: false'));

        // Bookmarked
        Bookmark::create(['user_id' => $user->id, 'post_id' => $post->id]);

        $response = $this->actingAs($user)->get(route('post.show', $post->slug));
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->content(), 'bookmarked: true'));
    }

    public function test_bookmark_button_shows_correct_count(): void
    {
        $post = Post::factory()->create(['status' => 'published']);
        $users = User::factory()->count(5)->create();

        foreach ($users as $user) {
            Bookmark::create(['user_id' => $user->id, 'post_id' => $post->id]);
        }

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->content(), 'count: 5'));
    }

    // Share Modal Component Tests

    public function test_share_modal_component_renders_with_correct_data(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'title' => 'Test Article Title',
            'excerpt' => 'Test excerpt for sharing',
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('Share Article')
            ->assertSee($post->title)
            ->assertSee($post->excerpt)
            ->assertSee('twitter.com/intent/tweet')
            ->assertSee('facebook.com/sharer')
            ->assertSee('linkedin.com/sharing')
            ->assertSee('reddit.com/submit');
    }

    public function test_share_modal_includes_copy_link_functionality(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('copyLink')
            ->assertSee('Or copy link');
    }

    // Comment Form Component Tests

    public function test_comment_form_component_renders_correctly(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('Leave a Comment')
            ->assertSee('author_name')
            ->assertSee('author_email')
            ->assertSee('content');
    }

    public function test_comment_form_shows_user_info_when_authenticated(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->actingAs($user)->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('Commenting as')
            ->assertSee('John Doe');
    }

    public function test_comment_form_includes_spam_protection(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('honeypot')
            ->assertSee('page_load_time');
    }

    // Comment Thread Component Tests

    public function test_comment_thread_component_renders_correctly(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('Comments')
            ->assertSee('Sort by:');
    }

    public function test_comment_thread_displays_approved_comments(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $approvedComment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => 'approved',
            'content' => 'This is an approved comment',
        ]);

        $pendingComment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => 'pending',
            'content' => 'This is a pending comment',
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('This is an approved comment')
            ->assertDontSee('This is a pending comment');
    }

    public function test_comment_thread_shows_empty_state_when_no_comments(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('No comments yet')
            ->assertSee('Be the first to share your thoughts!');
    }

    // Comment Item Component Tests

    public function test_comment_item_component_displays_comment_data(): void
    {
        $user = User::factory()->create(['name' => 'Jane Smith']);
        $post = Post::factory()->create(['status' => 'published']);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $user->id,
            'status' => 'approved',
            'content' => 'Great article!',
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('Jane Smith')
            ->assertSee('Great article!');
    }

    public function test_comment_item_shows_author_badge_for_post_author(): void
    {
        $author = User::factory()->create();
        $post = Post::factory()->create([
            'status' => 'published',
            'author_id' => $author->id,
        ]);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'user_id' => $author->id,
            'status' => 'approved',
            'content' => 'Thanks for reading!',
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('Author');
    }

    public function test_comment_item_displays_nested_replies(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $parentComment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => 'approved',
            'content' => 'Parent comment',
        ]);

        $replyComment = Comment::factory()->create([
            'post_id' => $post->id,
            'parent_id' => $parentComment->id,
            'status' => 'approved',
            'content' => 'Reply to parent',
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('Parent comment')
            ->assertSee('Reply to parent');
    }

    public function test_comment_item_shows_reply_button_for_allowed_levels(): void
    {
        $post = Post::factory()->create(['status' => 'published']);

        $comment = Comment::factory()->create([
            'post_id' => $post->id,
            'status' => 'approved',
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('Reply');
    }

    // Social Proof Component Tests

    public function test_social_proof_component_displays_all_metrics(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'view_count' => 1500,
        ]);

        // Add comments
        Comment::factory()->count(10)->create([
            'post_id' => $post->id,
            'status' => 'approved',
        ]);

        // Add reactions
        $users = User::factory()->count(25)->create();
        foreach ($users as $user) {
            Reaction::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
                'type' => 'like',
            ]);
        }

        // Add bookmarks
        foreach ($users->take(8) as $user) {
            Bookmark::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]);
        }

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200);

        // Check for view count
        $this->assertTrue(str_contains($response->content(), '1.5k') || str_contains($response->content(), '1500'));

        // Check for comment count
        $this->assertTrue(str_contains($response->content(), '10'));

        // Check for reaction count
        $this->assertTrue(str_contains($response->content(), '25'));

        // Check for bookmark count
        $this->assertTrue(str_contains($response->content(), '8'));
    }

    public function test_social_proof_component_shows_trending_badge(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'is_trending' => true,
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('Trending');
    }

    public function test_social_proof_component_shows_new_badge_for_posts_without_metrics(): void
    {
        $post = Post::factory()->create([
            'status' => 'published',
            'view_count' => 0,
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertStatus(200)
            ->assertSee('New');
    }
}
