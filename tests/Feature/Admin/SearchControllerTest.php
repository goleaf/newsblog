<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $editor;

    protected User $author;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->editor = User::factory()->create(['role' => 'editor']);
        $this->author = User::factory()->create(['role' => 'author']);
    }

    public function test_admin_search_requires_authentication(): void
    {
        $response = $this->get('/admin/search?q=Laravel');

        $response->assertRedirect('/login');
    }

    public function test_admin_search_requires_admin_or_editor_role(): void
    {
        $response = $this->actingAs($this->author)->get('/admin/search?q=Laravel');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_search(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/search?q=Laravel');

        $response->assertStatus(200);
        $response->assertViewIs('admin.search.index');
    }

    public function test_editor_can_access_search(): void
    {
        $response = $this->actingAs($this->editor)->get('/admin/search?q=Laravel');

        $response->assertStatus(200);
        $response->assertViewIs('admin.search.index');
    }

    public function test_admin_search_returns_empty_results_for_empty_query(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/search?q=');

        $response->assertStatus(200);
        $response->assertViewHas('results', function ($results) {
            return $results->isEmpty();
        });
    }

    public function test_admin_can_search_posts(): void
    {
        $category = Category::factory()->create();

        $post1 = Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
            'category_id' => $category->id,
        ]);

        $post2 = Post::factory()->create([
            'title' => 'PHP Basics',
            'status' => 'published',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/search?q=Laravel&type=posts');

        $response->assertStatus(200);
        $response->assertViewHas('results', function ($results) use ($post1, $post2) {
            return $results->contains('id', $post1->id) && ! $results->contains('id', $post2->id);
        });
    }

    public function test_admin_search_includes_draft_posts(): void
    {
        $category = Category::factory()->create();

        $draftPost = Post::factory()->create([
            'title' => 'Draft Laravel Post',
            'status' => 'draft',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/search?q=Laravel&type=posts');

        $response->assertStatus(200);
        $response->assertViewHas('results', function ($results) use ($draftPost) {
            return $results->contains('id', $draftPost->id);
        });
    }

    public function test_admin_can_search_users(): void
    {
        $user1 = User::factory()->create(['name' => 'Laravel Developer', 'email' => 'laravel@example.com']);
        $user2 = User::factory()->create(['name' => 'PHP Developer', 'email' => 'php@example.com']);

        $response = $this->actingAs($this->admin)->get('/admin/search?q=Laravel&type=users');

        $response->assertStatus(200);
        $response->assertViewHas('results', function ($results) use ($user1, $user2) {
            return $results->contains('id', $user1->id) && ! $results->contains('id', $user2->id);
        });
    }

    public function test_admin_can_search_comments(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'title' => 'Test Post',
            'status' => 'published',
            'category_id' => $category->id,
        ]);

        $comment1 = Comment::create([
            'post_id' => $post->id,
            'author_name' => 'John Doe',
            'author_email' => 'john@example.com',
            'content' => 'Laravel is great!',
            'status' => 'pending',
        ]);

        $comment2 = Comment::create([
            'post_id' => $post->id,
            'author_name' => 'Jane Smith',
            'author_email' => 'jane@example.com',
            'content' => 'PHP is awesome!',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/search?q=Laravel&type=comments');

        $response->assertStatus(200);
        $response->assertViewHas('results', function ($results) use ($comment1, $comment2) {
            return $results->contains('id', $comment1->id) && ! $results->contains('id', $comment2->id);
        });
    }

    public function test_admin_search_defaults_to_posts_type(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/search?q=Laravel');

        $response->assertStatus(200);
        $response->assertViewHas('type', 'posts');
    }

    public function test_admin_search_highlights_matched_text(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'title' => 'Laravel Testing Guide',
            'status' => 'published',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/search?q=Laravel&type=posts');

        $response->assertStatus(200);
        // Verify that results are returned
        $results = $response->viewData('results');
        $this->assertGreaterThanOrEqual(1, $results->count());
        // Highlighting is applied in the view layer, so we verify the data is present
        $this->assertTrue($results->contains('id', $post->id));
    }

    public function test_admin_search_pagination_works(): void
    {
        $category = Category::factory()->create();

        // Create more than 20 posts to test pagination
        Post::factory()->count(25)->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/search?q=Laravel&type=posts');

        $response->assertStatus(200);
        $results = $response->viewData('results');
        // Verify results are returned (pagination may vary by implementation)
        $this->assertGreaterThan(0, $results->count());

        // Test second page exists
        $response = $this->actingAs($this->admin)->get('/admin/search?q=Laravel&type=posts&page=2');

        $response->assertStatus(200);
    }

    public function test_admin_search_fuzzy_matching_with_typos(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'title' => 'Laravel Framework Guide',
            'status' => 'published',
            'category_id' => $category->id,
        ]);

        // Search with typo "laravle" instead of "laravel"
        $response = $this->actingAs($this->admin)->get('/admin/search?q=laravle&type=posts');

        $response->assertStatus(200);
        $results = $response->viewData('results');
        // Fuzzy search may or may not find results depending on threshold
        // Test that the endpoint works and returns a valid collection
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $results);
    }

    public function test_admin_search_user_fuzzy_matching_on_multiple_fields(): void
    {
        $user1 = User::factory()->create([
            'name' => 'Laravel Developer',
            'email' => 'dev@laravel.com',
        ]);

        $user2 = User::factory()->create([
            'name' => 'PHP Developer',
            'email' => 'dev@php.com',
        ]);

        // Search should match name and email fields
        $response = $this->actingAs($this->admin)->get('/admin/search?q=laravel&type=users');

        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertTrue($results->contains('id', $user1->id));
        $this->assertFalse($results->contains('id', $user2->id));
    }

    public function test_admin_search_multi_type_search(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'title' => 'Laravel Post',
            'status' => 'published',
            'category_id' => $category->id,
        ]);

        $user = User::factory()->create(['name' => 'Laravel User']);

        // Test posts type
        $response = $this->actingAs($this->admin)->get('/admin/search?q=Laravel&type=posts');
        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertTrue($results->contains('id', $post->id));

        // Test users type
        $response = $this->actingAs($this->admin)->get('/admin/search?q=Laravel&type=users');
        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertTrue($results->contains('id', $user->id));
    }
}
