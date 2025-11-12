<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_displays_posts(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertViewIs('home');
    }

    public function test_post_page_displays_post(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get("/post/{$post->slug}");

        $response->assertStatus(200);
        $response->assertViewIs('posts.show');
        $response->assertSee($post->title);
    }

    public function test_category_page_displays_posts(): void
    {
        $category = Category::factory()->create(['status' => 'active']);

        $response = $this->get("/category/{$category->slug}");

        $response->assertStatus(200);
        $response->assertViewIs('categories.show');
    }

    public function test_search_returns_results(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Test Search Post',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/search?q=Test');

        $response->assertStatus(200);
        $response->assertViewIs('search');
        $response->assertSee('Test Search Post');
    }

    public function test_scope_without_content_filters_posts_with_null_content(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $postWithEmptyContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with empty content',
            'content' => '',
        ]);

        $postWithContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with content',
            'content' => 'This post has content',
        ]);

        $results = Post::withoutContent()->get();

        $this->assertTrue($results->contains('id', $postWithEmptyContent->id));
        $this->assertFalse($results->contains('id', $postWithContent->id));
    }

    public function test_scope_without_content_filters_posts_with_empty_content(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $postWithEmptyContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with empty content',
            'content' => '',
        ]);

        $postWithContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with content',
            'content' => 'This post has content',
        ]);

        $results = Post::withoutContent()->get();

        $this->assertTrue($results->contains('id', $postWithEmptyContent->id));
        $this->assertFalse($results->contains('id', $postWithContent->id));
    }

    public function test_scope_without_content_filters_posts_with_whitespace_only_content(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $postWithWhitespaceContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with whitespace content',
            'content' => '   ',
        ]);

        $postWithContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with content',
            'content' => 'This post has content',
        ]);

        $results = Post::withoutContent()->get();

        $this->assertTrue($results->contains('id', $postWithWhitespaceContent->id));
        $this->assertFalse($results->contains('id', $postWithContent->id));
    }

    public function test_scope_without_content_excludes_posts_with_content(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $postWithTitle = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with title',
            'content' => '',
        ]);

        $postWithContent = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Post with content',
            'content' => 'This post has content',
        ]);

        $results = Post::withoutContent()->get();

        $this->assertTrue($results->contains('id', $postWithTitle->id));
        $this->assertFalse($results->contains('id', $postWithContent->id));
    }
}
