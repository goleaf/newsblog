<?php

namespace Tests\Feature\Nova;

use App\Models\Category;
use App\Models\Comment;
use App\Models\Media;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NovaFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    public function test_can_filter_posts_by_status_draft(): void
    {
        Post::factory()->create(['status' => 'draft', 'title' => 'Draft Post']);
        Post::factory()->create(['status' => 'published', 'title' => 'Published Post']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/posts?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\PostStatus', 'value' => 'draft'],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Draft Post'])
            ->assertJsonMissing(['title' => 'Published Post']);
    }

    public function test_can_filter_posts_by_status_published(): void
    {
        Post::factory()->create(['status' => 'draft', 'title' => 'Draft Post']);
        Post::factory()->create(['status' => 'published', 'title' => 'Published Post']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/posts?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\PostStatus', 'value' => 'published'],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Published Post'])
            ->assertJsonMissing(['title' => 'Draft Post']);
    }

    public function test_can_filter_posts_by_status_scheduled(): void
    {
        Post::factory()->create(['status' => 'draft', 'title' => 'Draft Post']);
        Post::factory()->create(['status' => 'scheduled', 'title' => 'Scheduled Post']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/posts?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\PostStatus', 'value' => 'scheduled'],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Scheduled Post'])
            ->assertJsonMissing(['title' => 'Draft Post']);
    }

    public function test_can_filter_posts_by_category(): void
    {
        $techCategory = Category::factory()->create(['name' => 'Technology']);
        $businessCategory = Category::factory()->create(['name' => 'Business']);

        Post::factory()->create(['category_id' => $techCategory->id, 'title' => 'Tech Post']);
        Post::factory()->create(['category_id' => $businessCategory->id, 'title' => 'Business Post']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/posts?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\PostCategory', 'value' => $techCategory->id],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Tech Post'])
            ->assertJsonMissing(['title' => 'Business Post']);
    }

    public function test_can_filter_posts_by_author(): void
    {
        $author1 = User::factory()->create(['name' => 'Author One']);
        $author2 = User::factory()->create(['name' => 'Author Two']);

        Post::factory()->create(['user_id' => $author1->id, 'title' => 'Post by Author One']);
        Post::factory()->create(['user_id' => $author2->id, 'title' => 'Post by Author Two']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/posts?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\PostAuthor', 'value' => $author1->id],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Post by Author One'])
            ->assertJsonMissing(['title' => 'Post by Author Two']);
    }

    public function test_can_filter_posts_by_featured_status(): void
    {
        Post::factory()->create(['is_featured' => true, 'title' => 'Featured Post']);
        Post::factory()->create(['is_featured' => false, 'title' => 'Regular Post']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/posts?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\PostFeatured', 'value' => true],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Featured Post'])
            ->assertJsonMissing(['title' => 'Regular Post']);
    }

    public function test_can_filter_comments_by_status_pending(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending', 'content' => 'Pending Comment']);
        Comment::factory()->create(['post_id' => $post->id, 'status' => 'approved', 'content' => 'Approved Comment']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/comments?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\CommentStatus', 'value' => 'pending'],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['content' => 'Pending Comment'])
            ->assertJsonMissing(['content' => 'Approved Comment']);
    }

    public function test_can_filter_comments_by_status_approved(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending', 'content' => 'Pending Comment']);
        Comment::factory()->create(['post_id' => $post->id, 'status' => 'approved', 'content' => 'Approved Comment']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/comments?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\CommentStatus', 'value' => 'approved'],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['content' => 'Approved Comment'])
            ->assertJsonMissing(['content' => 'Pending Comment']);
    }

    public function test_can_filter_comments_by_status_spam(): void
    {
        $post = Post::factory()->create();
        Comment::factory()->create(['post_id' => $post->id, 'status' => 'approved', 'content' => 'Good Comment']);
        Comment::factory()->create(['post_id' => $post->id, 'status' => 'spam', 'content' => 'Spam Comment']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/comments?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\CommentStatus', 'value' => 'spam'],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['content' => 'Spam Comment'])
            ->assertJsonMissing(['content' => 'Good Comment']);
    }

    public function test_can_filter_users_by_role_admin(): void
    {
        User::factory()->create(['role' => 'admin', 'name' => 'Admin User']);
        User::factory()->create(['role' => 'editor', 'name' => 'Editor User']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/users?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\UserRole', 'value' => 'admin'],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Admin User'])
            ->assertJsonMissing(['name' => 'Editor User']);
    }

    public function test_can_filter_users_by_role_editor(): void
    {
        User::factory()->create(['role' => 'admin', 'name' => 'Admin User']);
        User::factory()->create(['role' => 'editor', 'name' => 'Editor User']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/users?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\UserRole', 'value' => 'editor'],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Editor User'])
            ->assertJsonMissing(['name' => 'Admin User']);
    }

    public function test_can_filter_users_by_role_author(): void
    {
        User::factory()->create(['role' => 'editor', 'name' => 'Editor User']);
        User::factory()->create(['role' => 'author', 'name' => 'Author User']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/users?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\UserRole', 'value' => 'author'],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Author User'])
            ->assertJsonMissing(['name' => 'Editor User']);
    }

    public function test_can_filter_categories_by_status_active(): void
    {
        Category::factory()->create(['status' => 'active', 'name' => 'Active Category']);
        Category::factory()->create(['status' => 'inactive', 'name' => 'Inactive Category']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/categories?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\CategoryStatus', 'value' => 'active'],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Active Category'])
            ->assertJsonMissing(['name' => 'Inactive Category']);
    }

    public function test_can_filter_categories_by_status_inactive(): void
    {
        Category::factory()->create(['status' => 'active', 'name' => 'Active Category']);
        Category::factory()->create(['status' => 'inactive', 'name' => 'Inactive Category']);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/categories?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\CategoryStatus', 'value' => 'inactive'],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['name' => 'Inactive Category'])
            ->assertJsonMissing(['name' => 'Active Category']);
    }

    public function test_can_filter_media_by_type_image(): void
    {
        Media::factory()->create([
            'file_type' => 'image',
            'file_name' => 'photo.jpg',
            'user_id' => $this->admin->id,
        ]);
        Media::factory()->create([
            'file_type' => 'document',
            'file_name' => 'document.pdf',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/media?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\MediaType', 'value' => 'image'],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['file_name' => 'photo.jpg'])
            ->assertJsonMissing(['file_name' => 'document.pdf']);
    }

    public function test_can_filter_media_by_type_document(): void
    {
        Media::factory()->create([
            'file_type' => 'image',
            'file_name' => 'photo.jpg',
            'user_id' => $this->admin->id,
        ]);
        Media::factory()->create([
            'file_type' => 'document',
            'file_name' => 'document.pdf',
            'user_id' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/media?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\MediaType', 'value' => 'document'],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['file_name' => 'document.pdf'])
            ->assertJsonMissing(['file_name' => 'photo.jpg']);
    }

    public function test_can_apply_multiple_filters_simultaneously(): void
    {
        $techCategory = Category::factory()->create(['name' => 'Technology']);
        $businessCategory = Category::factory()->create(['name' => 'Business']);

        Post::factory()->create([
            'category_id' => $techCategory->id,
            'status' => 'published',
            'title' => 'Published Tech Post',
        ]);
        Post::factory()->create([
            'category_id' => $techCategory->id,
            'status' => 'draft',
            'title' => 'Draft Tech Post',
        ]);
        Post::factory()->create([
            'category_id' => $businessCategory->id,
            'status' => 'published',
            'title' => 'Published Business Post',
        ]);

        $response = $this->actingAs($this->admin)
            ->getJson('/nova-api/posts?filters='.base64_encode(json_encode([
                ['class' => 'App\\Nova\\Filters\\PostCategory', 'value' => $techCategory->id],
                ['class' => 'App\\Nova\\Filters\\PostStatus', 'value' => 'published'],
            ])));

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Published Tech Post'])
            ->assertJsonMissing(['title' => 'Draft Tech Post'])
            ->assertJsonMissing(['title' => 'Published Business Post']);
    }
}
