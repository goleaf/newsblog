<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_page_displays_header_with_tag_name(): void
    {
        $tag = Tag::factory()->create(['name' => 'Laravel']);
        
        Post::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ])->tags()->attach($tag);

        $response = $this->get(route('tag.show', $tag->slug));

        $response->assertStatus(200);
        $response->assertSee('#Laravel');
    }

    public function test_tag_page_displays_related_tags(): void
    {
        $mainTag = Tag::factory()->create(['name' => 'PHP']);
        $relatedTag1 = Tag::factory()->create(['name' => 'Laravel']);
        $relatedTag2 = Tag::factory()->create(['name' => 'Symfony']);

        // Create posts that share tags
        $post1 = Post::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post1->tags()->attach([$mainTag->id, $relatedTag1->id]);

        $post2 = Post::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ]);
        $post2->tags()->attach([$mainTag->id, $relatedTag2->id]);

        $response = $this->get(route('tag.show', $mainTag->slug));

        $response->assertStatus(200);
        $response->assertSee('Related Tags');
        $response->assertSee('#Laravel');
        $response->assertSee('#Symfony');
    }

    public function test_tag_page_filters_posts_by_date(): void
    {
        $tag = Tag::factory()->create();

        // Create posts with different dates
        $todayPost = Post::factory()->create([
            'title' => 'Today Article About Technology',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $todayPost->tags()->attach($tag);

        $oldPost = Post::factory()->create([
            'title' => 'Ancient Article From Two Months Ago',
            'status' => 'published',
            'published_at' => now()->subMonths(2),
        ]);
        $oldPost->tags()->attach($tag);

        // Test "today" filter
        $response = $this->get(route('tag.show', $tag->slug) . '?date_filter=today');
        $response->assertStatus(200);
        $response->assertSee('Today Article About Technology');
        $response->assertDontSee('Ancient Article From Two Months Ago');

        // Test "month" filter
        $response = $this->get(route('tag.show', $tag->slug) . '?date_filter=month');
        $response->assertStatus(200);
        $response->assertSee('Today Article About Technology');
        $response->assertDontSee('Ancient Article From Two Months Ago');
    }

    public function test_tag_page_sorts_posts_correctly(): void
    {
        $tag = Tag::factory()->create();

        $popularPost = Post::factory()->create([
            'title' => 'Popular Post',
            'status' => 'published',
            'published_at' => now()->subDays(5),
            'view_count' => 1000,
        ]);
        $popularPost->tags()->attach($tag);

        $recentPost = Post::factory()->create([
            'title' => 'Recent Post',
            'status' => 'published',
            'published_at' => now(),
            'view_count' => 10,
        ]);
        $recentPost->tags()->attach($tag);

        // Test default (latest) sort
        $response = $this->get(route('tag.show', $tag->slug));
        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Popular Post'),
            strpos($content, 'Recent Post'),
            'Recent post should appear before popular post in latest sort'
        );

        // Test popular sort
        $response = $this->get(route('tag.show', $tag->slug) . '?sort=popular');
        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Recent Post'),
            strpos($content, 'Popular Post'),
            'Popular post should appear before recent post in popular sort'
        );
    }

    public function test_tag_page_displays_empty_state_when_no_posts(): void
    {
        $tag = Tag::factory()->create();

        $response = $this->get(route('tag.show', $tag->slug));

        $response->assertStatus(200);
        $response->assertSee('No articles found');
        $response->assertSee('Explore other tags');
    }

    public function test_tag_page_displays_post_count(): void
    {
        $tag = Tag::factory()->create();

        Post::factory()->count(5)->create([
            'status' => 'published',
            'published_at' => now(),
        ])->each(function ($post) use ($tag) {
            $post->tags()->attach($tag);
        });

        $response = $this->get(route('tag.show', $tag->slug));

        $response->assertStatus(200);
        $response->assertSee('5 articles');
    }

    public function test_tag_page_displays_breadcrumbs(): void
    {
        $tag = Tag::factory()->create(['name' => 'Testing']);

        Post::factory()->create([
            'status' => 'published',
            'published_at' => now(),
        ])->tags()->attach($tag);

        $response = $this->get(route('tag.show', $tag->slug));

        $response->assertStatus(200);
        $response->assertSee('Home');
        $response->assertSee('Tags');
        $response->assertSee('#Testing');
    }
}
