<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\SocialShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialSharingTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_track_social_share(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'status' => PostStatus::Published,
            'category_id' => $category->id,
            'published_at' => now(),
        ]);

        $response = $this->actingAs($user)->postJson(route('posts.share.track', $post), [
            'platform' => 'twitter',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'share_count' => 1,
            ]);

        $this->assertDatabaseHas('social_shares', [
            'post_id' => $post->id,
            'user_id' => $user->id,
            'provider' => 'twitter',
        ]);
    }

    public function test_can_track_share_without_authentication(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'status' => PostStatus::Published,
            'category_id' => $category->id,
            'published_at' => now(),
        ]);

        $response = $this->postJson(route('posts.share.track', $post), [
            'platform' => 'facebook',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('social_shares', [
            'post_id' => $post->id,
            'user_id' => null,
            'provider' => 'facebook',
        ]);
    }

    public function test_validates_platform_when_tracking_share(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'status' => PostStatus::Published,
            'category_id' => $category->id,
            'published_at' => now(),
        ]);

        $response = $this->postJson(route('posts.share.track', $post), [
            'platform' => 'invalid-platform',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['platform']);
    }

    public function test_can_get_share_urls_for_post(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'status' => PostStatus::Published,
            'category_id' => $category->id,
            'published_at' => now(),
        ]);

        $response = $this->getJson(route('posts.share.urls', $post));

        $response->assertOk()
            ->assertJsonStructure([
                'urls' => [
                    'twitter',
                    'facebook',
                    'linkedin',
                    'reddit',
                    'hackernews',
                ],
                'share_count',
            ]);

        $urls = $response->json('urls');
        $this->assertStringContainsString('twitter.com', $urls['twitter']);
        $this->assertStringContainsString('facebook.com', $urls['facebook']);
        $this->assertStringContainsString('linkedin.com', $urls['linkedin']);
        $this->assertStringContainsString('reddit.com', $urls['reddit']);
        $this->assertStringContainsString('news.ycombinator.com', $urls['hackernews']);
    }

    public function test_share_count_increments_correctly(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'status' => PostStatus::Published,
            'category_id' => $category->id,
            'published_at' => now(),
        ]);

        // Create multiple shares
        SocialShare::factory()->count(3)->create([
            'post_id' => $post->id,
        ]);

        $response = $this->getJson(route('posts.share.urls', $post));

        $response->assertOk()
            ->assertJson([
                'share_count' => 3,
            ]);
    }

    public function test_post_has_open_graph_meta_tags(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'status' => PostStatus::Published,
            'category_id' => $category->id,
            'published_at' => now(),
            'title' => 'Test Article Title',
            'excerpt' => 'Test article excerpt for meta description',
        ]);

        $response = $this->get(route('post.show', $post->slug));

        $response->assertOk();

        $content = $response->getContent();

        // Check Open Graph tags
        $this->assertStringContainsString('og:title', $content);
        $this->assertStringContainsString('og:description', $content);
        $this->assertStringContainsString('og:image', $content);
        $this->assertStringContainsString('og:url', $content);
        $this->assertStringContainsString('og:type', $content);

        // Check Twitter Card tags
        $this->assertStringContainsString('twitter:card', $content);
        $this->assertStringContainsString('twitter:title', $content);
        $this->assertStringContainsString('twitter:description', $content);
        $this->assertStringContainsString('twitter:image', $content);

        // Check article-specific tags
        $this->assertStringContainsString('article:published_time', $content);
        $this->assertStringContainsString('article:author', $content);
    }

    public function test_post_meta_tags_method_returns_correct_structure(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'status' => PostStatus::Published,
            'category_id' => $category->id,
            'published_at' => now(),
        ]);

        $metaTags = $post->getMetaTags();

        $this->assertIsArray($metaTags);
        $this->assertArrayHasKey('title', $metaTags);
        $this->assertArrayHasKey('description', $metaTags);
        $this->assertArrayHasKey('og:title', $metaTags);
        $this->assertArrayHasKey('og:description', $metaTags);
        $this->assertArrayHasKey('og:image', $metaTags);
        $this->assertArrayHasKey('og:url', $metaTags);
        $this->assertArrayHasKey('og:type', $metaTags);
        $this->assertArrayHasKey('twitter:card', $metaTags);
        $this->assertArrayHasKey('article:published_time', $metaTags);
    }

    public function test_post_structured_data_method_returns_valid_json_ld(): void
    {
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'status' => PostStatus::Published,
            'category_id' => $category->id,
            'published_at' => now(),
        ]);

        $structuredData = $post->getStructuredData();

        $this->assertIsArray($structuredData);
        $this->assertEquals('https://schema.org', $structuredData['@context']);
        $this->assertEquals('Article', $structuredData['@type']);
        $this->assertArrayHasKey('headline', $structuredData);
        $this->assertArrayHasKey('description', $structuredData);
        $this->assertArrayHasKey('image', $structuredData);
        $this->assertArrayHasKey('datePublished', $structuredData);
        $this->assertArrayHasKey('author', $structuredData);
        $this->assertArrayHasKey('publisher', $structuredData);
    }
}
