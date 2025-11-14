<?php

namespace Tests\Feature\Frontend;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchFeaturesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $this->user = User::factory()->create();
        $this->category = Category::factory()->create(['name' => 'Technology']);
        $this->tag = Tag::factory()->create(['name' => 'Laravel']);
    }

    /** @test */
    public function it_displays_search_page()
    {
        $response = $this->get(route('search'));

        $response->assertOk();
        $response->assertViewIs('search');
        $response->assertSee('Search');
    }

    /** @test */
    public function it_searches_posts_by_query()
    {
        $post = Post::factory()->published()->create([
            'title' => 'Laravel Testing Guide',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->get(route('search', ['q' => 'Laravel']));

        $response->assertOk();
        $response->assertSee($post->title);
        $response->assertSee('Found 1 result');
    }

    /** @test */
    public function it_returns_autocomplete_suggestions()
    {
        Post::factory()->published()->create([
            'title' => 'Laravel Testing Guide',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->getJson(route('search.suggestions', ['q' => 'Lar']));

        $response->assertOk();
        $response->assertJsonStructure([]);
    }

    /** @test */
    public function it_filters_by_category()
    {
        $post1 = Post::factory()->published()->create([
            'title' => 'Tech Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $otherCategory = Category::factory()->create(['name' => 'Design']);
        $post2 = Post::factory()->published()->create([
            'title' => 'Design Post',
            'user_id' => $this->user->id,
            'category_id' => $otherCategory->id,
        ]);

        $response = $this->get(route('search', [
            'q' => 'Post',
            'category' => $this->category->id,
        ]));

        $response->assertOk();
        $response->assertSee($post1->title);
        $response->assertDontSee($post2->title);
    }

    /** @test */
    public function it_filters_by_author()
    {
        $author1 = User::factory()->create(['name' => 'John Doe']);
        $author2 = User::factory()->create(['name' => 'Jane Smith']);

        $post1 = Post::factory()->published()->create([
            'title' => 'Post by John',
            'user_id' => $author1->id,
            'category_id' => $this->category->id,
        ]);

        $post2 = Post::factory()->published()->create([
            'title' => 'Post by Jane',
            'user_id' => $author2->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->get(route('search', [
            'q' => 'Post',
            'author' => $author1->id,
        ]));

        $response->assertOk();
        $response->assertSee($post1->title);
        $response->assertDontSee($post2->title);
    }

    /** @test */
    public function it_filters_by_date_range()
    {
        $oldPost = Post::factory()->published()->create([
            'title' => 'Old Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'published_at' => now()->subMonths(6),
        ]);

        $newPost = Post::factory()->published()->create([
            'title' => 'New Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'published_at' => now()->subDays(7),
        ]);

        $response = $this->get(route('search', [
            'q' => 'Post',
            'date_from' => now()->subMonth()->format('Y-m-d'),
        ]));

        $response->assertOk();
        $response->assertSee($newPost->title);
        $response->assertDontSee($oldPost->title);
    }

    /** @test */
    public function it_filters_by_tags()
    {
        $tag1 = Tag::factory()->create(['name' => 'PHP']);
        $tag2 = Tag::factory()->create(['name' => 'JavaScript']);

        $post1 = Post::factory()->published()->create([
            'title' => 'PHP Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);
        $post1->tags()->attach($tag1);

        $post2 = Post::factory()->published()->create([
            'title' => 'JS Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);
        $post2->tags()->attach($tag2);

        $response = $this->get(route('search', [
            'q' => 'Post',
            'tags' => [$tag1->id],
        ]));

        $response->assertOk();
        $response->assertSee($post1->title);
        $response->assertDontSee($post2->title);
    }

    /** @test */
    public function it_sorts_by_newest()
    {
        $oldPost = Post::factory()->published()->create([
            'title' => 'Old Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'published_at' => now()->subDays(7),
        ]);

        $newPost = Post::factory()->published()->create([
            'title' => 'New Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'published_at' => now()->subDays(1),
        ]);

        $response = $this->get(route('search', [
            'q' => 'Post',
            'sort' => 'newest',
        ]));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, $oldPost->title),
            strpos($content, $newPost->title)
        );
    }

    /** @test */
    public function it_sorts_by_popular()
    {
        $unpopularPost = Post::factory()->published()->create([
            'title' => 'Unpopular Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'view_count' => 10,
        ]);

        $popularPost = Post::factory()->published()->create([
            'title' => 'Popular Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
            'view_count' => 1000,
        ]);

        $response = $this->get(route('search', [
            'q' => 'Post',
            'sort' => 'popular',
        ]));

        $response->assertOk();
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, $unpopularPost->title),
            strpos($content, $popularPost->title)
        );
    }

    /** @test */
    public function it_displays_category_page_with_posts()
    {
        $post = Post::factory()->published()->create([
            'title' => 'Category Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->get(route('category.show', $this->category->slug));

        $response->assertOk();
        $response->assertSee($this->category->name);
        $response->assertSee($post->title);
    }

    /** @test */
    public function it_displays_tag_page_with_posts()
    {
        $post = Post::factory()->published()->create([
            'title' => 'Tagged Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);
        $post->tags()->attach($this->tag);

        $response = $this->get(route('tag.show', $this->tag->slug));

        $response->assertOk();
        $response->assertSee($this->tag->name);
        $response->assertSee($post->title);
    }

    /** @test */
    public function it_shows_empty_state_when_no_results()
    {
        $response = $this->get(route('search', ['q' => 'NonexistentQuery']));

        $response->assertOk();
        $response->assertSee('No results found');
    }

    /** @test */
    public function it_highlights_search_terms_in_results()
    {
        $post = Post::factory()->published()->create([
            'title' => 'Laravel Testing Guide',
            'excerpt' => 'Learn how to test Laravel applications',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->get(route('search', ['q' => 'Laravel']));

        $response->assertOk();
        $response->assertSee('search-highlight', false);
    }

    /** @test */
    public function it_displays_filter_panel_component()
    {
        $response = $this->get(route('search', ['q' => 'test']));

        $response->assertOk();
        $response->assertSee('Filters');
        $response->assertSee('Date Range');
        $response->assertSee('Author');
        $response->assertSee('Category');
    }

    /** @test */
    public function it_displays_sort_dropdown_component()
    {
        Post::factory()->published()->create([
            'title' => 'Test Post',
            'user_id' => $this->user->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->get(route('search', ['q' => 'Test']));

        $response->assertOk();
        $response->assertSee('Sort:');
    }

    /** @test */
    public function it_shows_active_filter_badges()
    {
        $response = $this->get(route('search', [
            'q' => 'test',
            'category' => $this->category->id,
        ]));

        $response->assertOk();
        $response->assertSee($this->category->name);
        $response->assertSee('Clear all filters');
    }
}
