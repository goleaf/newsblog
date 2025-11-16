<?php

namespace Tests\Feature;

use App\Enums\PostStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostRelationshipsAndScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_belongs_to_author_and_primary_category(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();

        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $this->assertTrue($post->user->is($user));
        $this->assertTrue($post->category->is($category));
    }

    public function test_post_belongs_to_many_categories_and_tags(): void
    {
        $post = Post::factory()->create();
        $categories = Category::factory()->count(3)->create();
        $tags = Tag::factory()->count(4)->create();

        $post->categories()->sync($categories->pluck('id')->all());
        $post->tags()->sync($tags->pluck('id')->all());

        $this->assertCount(3, $post->categories);
        $this->assertEqualsCanonicalizing(
            $categories->pluck('id')->all(),
            $post->categories->pluck('id')->all()
        );

        $this->assertCount(4, $post->tags);
        $this->assertEqualsCanonicalizing(
            $tags->pluck('id')->all(),
            $post->tags->pluck('id')->all()
        );
    }

    public function test_published_scope_filters_by_status_and_published_at(): void
    {
        $published = Post::factory()->create([
            'status' => PostStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);

        Post::factory()->create([
            'status' => PostStatus::Draft->value,
            'published_at' => null,
        ]);

        $results = Post::published()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($published));
    }

    public function test_featured_and_trending_scopes_filter_by_flags(): void
    {
        $featured = Post::factory()->featured()->create();
        $trending = Post::factory()->trending()->create();

        $featuredResults = Post::featured()->get();
        $trendingResults = Post::trending()->get();

        $this->assertTrue($featuredResults->contains('id', $featured->id));
        $this->assertFalse($featuredResults->contains('id', $trending->id));

        $this->assertTrue($trendingResults->contains('id', $trending->id));
        $this->assertFalse($trendingResults->contains('id', $featured->id));
    }

    public function test_scheduled_scope_filters_by_status_and_scheduled_at(): void
    {
        $scheduledPost = Post::factory()->create([
            'status' => PostStatus::Scheduled->value,
            'scheduled_at' => now()->addDay(),
        ]);

        Post::factory()->create([
            'status' => PostStatus::Scheduled->value,
            'scheduled_at' => now()->subDay(),
        ]);

        $results = Post::scheduled()->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($scheduledPost));
    }

    public function test_filter_scopes_by_category_tag_and_author(): void
    {
        $author = User::factory()->create();
        $category = Category::factory()->create();
        $tag = Tag::factory()->create();

        $matchingPost = Post::factory()->create([
            'user_id' => $author->id,
            'category_id' => $category->id,
        ]);
        $matchingPost->tags()->sync([$tag->id]);

        Post::factory()->create(); // noise

        $results = Post::query()
            ->byAuthor($author->id)
            ->byCategory($category->id)
            ->byTag($tag->id)
            ->get();

        $this->assertCount(1, $results);
        $this->assertTrue($results->first()->is($matchingPost));
    }

    public function test_recent_and_popular_scopes_order_results(): void
    {
        $older = Post::factory()->create([
            'published_at' => now()->subDays(2),
            'view_count' => 10,
        ]);
        $newer = Post::factory()->create([
            'published_at' => now()->subDay(),
            'view_count' => 5,
        ]);

        $recent = Post::recent()->get();
        $popular = Post::popular()->get();

        $this->assertEquals(
            [$newer->id, $older->id],
            $recent->pluck('id')->all()
        );

        $this->assertEquals(
            [$older->id, $newer->id],
            $popular->pluck('id')->all()
        );
    }
}
