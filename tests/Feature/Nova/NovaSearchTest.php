<?php

namespace Tests\Feature\Nova;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Media;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NovaSearchTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    public function test_can_search_posts_by_title(): void
    {
        Post::factory()->create(['title' => 'Laravel Nova Integration']);
        Post::factory()->create(['title' => 'React Development']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/posts?search=Laravel');

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Laravel Nova Integration'])
            ->assertJsonMissing(['title' => 'React Development']);
    }

    public function test_can_search_posts_by_excerpt(): void
    {
        Post::factory()->create([
            'title' => 'First Post',
            'excerpt' => 'This is about Laravel framework',
        ]);
        Post::factory()->create([
            'title' => 'Second Post',
            'excerpt' => 'This is about React library',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/posts?search=framework');

        $response->assertOk()
            ->assertJsonFragment(['title' => 'First Post'])
            ->assertJsonMissing(['title' => 'Second Post']);
    }

    public function test_can_search_posts_by_content(): void
    {
        Post::factory()->create([
            'title' => 'First Post',
            'content' => 'Deep dive into Laravel Nova administration',
        ]);
        Post::factory()->create([
            'title' => 'Second Post',
            'content' => 'Understanding React hooks',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/posts?search=administration');

        $response->assertOk()
            ->assertJsonFragment(['title' => 'First Post'])
            ->assertJsonMissing(['title' => 'Second Post']);
    }

    public function test_can_search_users_by_name(): void
    {
        User::factory()->create(['name' => 'John Doe', 'role' => 'author']);
        User::factory()->create(['name' => 'Jane Smith', 'role' => 'author']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/users?search=John');

        $response->assertOk()
            ->assertJsonFragment(['name' => 'John Doe'])
            ->assertJsonMissing(['name' => 'Jane Smith']);
    }

    public function test_can_search_users_by_email(): void
    {
        User::factory()->create(['email' => 'john@example.com', 'role' => 'author']);
        User::factory()->create(['email' => 'jane@example.com', 'role' => 'author']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/users?search=john@example');

        $response->assertOk()
            ->assertJsonFragment(['email' => 'john@example.com'])
            ->assertJsonMissing(['email' => 'jane@example.com']);
    }

    public function test_can_search_categories_by_name(): void
    {
        Category::factory()->create(['name' => 'Technology']);
        Category::factory()->create(['name' => 'Business']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/categories?search=Technology');

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Technology'])
            ->assertJsonMissing(['name' => 'Business']);
    }

    public function test_can_search_categories_by_description(): void
    {
        Category::factory()->create([
            'name' => 'Tech',
            'description' => 'All about software development',
        ]);
        Category::factory()->create([
            'name' => 'Business',
            'description' => 'Business and finance topics',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/categories?search=software');

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Tech'])
            ->assertJsonMissing(['name' => 'Business']);
    }

    public function test_can_search_tags_by_name(): void
    {
        Tag::factory()->create(['name' => 'Laravel']);
        Tag::factory()->create(['name' => 'React']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/tags?search=Laravel');

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Laravel'])
            ->assertJsonMissing(['name' => 'React']);
    }

    public function test_can_search_comments_by_content(): void
    {
        $post = Post::factory()->create();

        Comment::factory()->create([
            'post_id' => $post->id,
            'content' => 'Great article about Laravel',
        ]);
        Comment::factory()->create([
            'post_id' => $post->id,
            'content' => 'Nice tutorial on React',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/comments?search=Laravel');

        $response->assertOk()
            ->assertJsonFragment(['content' => 'Great article about Laravel'])
            ->assertJsonMissing(['content' => 'Nice tutorial on React']);
    }

    public function test_can_search_comments_by_author_name(): void
    {
        $post = Post::factory()->create();

        Comment::factory()->create([
            'post_id' => $post->id,
            'author_name' => 'John Doe',
            'content' => 'First comment',
        ]);
        Comment::factory()->create([
            'post_id' => $post->id,
            'author_name' => 'Jane Smith',
            'content' => 'Second comment',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/comments?search=John');

        $response->assertOk()
            ->assertJsonFragment(['author_name' => 'John Doe'])
            ->assertJsonMissing(['author_name' => 'Jane Smith']);
    }

    public function test_can_search_comments_by_author_email(): void
    {
        $post = Post::factory()->create();

        Comment::factory()->create([
            'post_id' => $post->id,
            'author_email' => 'john@example.com',
            'content' => 'First comment',
        ]);
        Comment::factory()->create([
            'post_id' => $post->id,
            'author_email' => 'jane@example.com',
            'content' => 'Second comment',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/comments?search=john@example');

        $response->assertOk()
            ->assertJsonFragment(['author_email' => 'john@example.com'])
            ->assertJsonMissing(['author_email' => 'jane@example.com']);
    }

    public function test_can_search_media_by_file_name(): void
    {
        Media::factory()->create([
            'file_name' => 'laravel-logo.png',
            'user_id' => $this->admin->id,
        ]);
        Media::factory()->create([
            'file_name' => 'react-icon.svg',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/media?search=laravel');

        $response->assertOk()
            ->assertJsonFragment(['file_name' => 'laravel-logo.png'])
            ->assertJsonMissing(['file_name' => 'react-icon.svg']);
    }

    public function test_can_search_media_by_title(): void
    {
        Media::factory()->create([
            'file_name' => 'image1.png',
            'title' => 'Laravel Framework Logo',
            'user_id' => $this->admin->id,
        ]);
        Media::factory()->create([
            'file_name' => 'image2.png',
            'title' => 'React Library Icon',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/media?search=Framework');

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Laravel Framework Logo'])
            ->assertJsonMissing(['title' => 'React Library Icon']);
    }

    public function test_can_search_media_by_alt_text(): void
    {
        Media::factory()->create([
            'file_name' => 'image1.png',
            'alt_text' => 'Laravel logo for documentation',
            'user_id' => $this->admin->id,
        ]);
        Media::factory()->create([
            'file_name' => 'image2.png',
            'alt_text' => 'React icon for tutorial',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/media?search=documentation');

        $response->assertOk()
            ->assertJsonFragment(['alt_text' => 'Laravel logo for documentation'])
            ->assertJsonMissing(['alt_text' => 'React icon for tutorial']);
    }

    public function test_search_returns_empty_when_no_matches(): void
    {
        Post::factory()->create(['title' => 'Laravel Nova']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/posts?search=NonExistentTerm');

        $response->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_unauthorized_user_cannot_search_resources(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        Post::factory()->create(['title' => 'Test Post']);

        $response = $this->actingAs($user)
            ->getJson('/nova-api/posts?search=Test');

        $response->assertForbidden();
    }
}
