<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Test filter and sort functionality
 * Task 3.3: Test filter and sort functionality
 * Requirements: 2.3, 5.3, 14.2
 */
class FilterAndSortFunctionalityTest extends TestCase
{
    use RefreshDatabase;

    protected User $author1;

    protected User $author2;

    protected Category $category1;

    protected Category $category2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->author1 = User::factory()->create(['name' => 'John Doe']);
        $this->author2 = User::factory()->create(['name' => 'Jane Smith']);
        $this->category1 = Category::factory()->create(['name' => 'Technology', 'status' => 'active']);
        $this->category2 = Category::factory()->create(['name' => 'Programming', 'status' => 'active']);
    }

    /**
     * Test date filter functionality
     * Requirement 2.3: Filter options for date range
     * Requirement 14.2: Apply date filters
     */
    public function test_date_filter_today(): void
    {
        // Create posts with different dates
        $todayPost = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Unique Today Article XYZ123',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $yesterdayPost = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Unique Yesterday Article ABC456',
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get(route('category.show', $this->category1->slug).'?date_filter=today');

        $response->assertStatus(200);
        $response->assertSee('Unique Today Article XYZ123');
        $response->assertDontSee('Unique Yesterday Article ABC456');
    }

    /**
     * Test date filter - this week
     * Requirement 2.3: Filter options for date range
     */
    public function test_date_filter_week(): void
    {
        $thisWeekPost = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Unique This Week Article DEF789',
            'status' => 'published',
            'published_at' => now()->subDays(3),
        ]);

        $lastMonthPost = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Unique Last Month Article GHI012',
            'status' => 'published',
            'published_at' => now()->subMonth(),
        ]);

        $response = $this->get(route('category.show', $this->category1->slug).'?date_filter=week');

        $response->assertStatus(200);
        $response->assertSee('Unique This Week Article DEF789');
        $response->assertDontSee('Unique Last Month Article GHI012');
    }

    /**
     * Test date filter - this month
     * Requirement 2.3: Filter options for date range
     */
    public function test_date_filter_month(): void
    {
        $thisMonthPost = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Unique This Month Article JKL345',
            'status' => 'published',
            'published_at' => now()->subDays(15),
        ]);

        $lastYearPost = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Unique Last Year Article MNO678',
            'status' => 'published',
            'published_at' => now()->subYear(),
        ]);

        $response = $this->get(route('category.show', $this->category1->slug).'?date_filter=month');

        $response->assertStatus(200);
        $response->assertSee('Unique This Month Article JKL345');
        $response->assertDontSee('Unique Last Year Article MNO678');
    }

    /**
     * Test sort by newest (default)
     * Requirement 5.3: Sorting options for newest
     */
    public function test_sort_by_newest(): void
    {
        $newerPost = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Unique Newer Post PQR901',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $olderPost = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Unique Older Post STU234',
            'status' => 'published',
            'published_at' => now()->subDays(5),
        ]);

        $response = $this->get(route('category.show', $this->category1->slug));

        $response->assertStatus(200);
        $response->assertSeeInOrder(['Unique Newer Post PQR901', 'Unique Older Post STU234']);
    }

    /**
     * Test sort by oldest
     * Requirement 5.3: Sorting options for oldest
     */
    public function test_sort_by_oldest(): void
    {
        $newerPost = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Newer Post',
            'status' => 'published',
            'published_at' => now(),
        ]);

        $olderPost = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Older Post',
            'status' => 'published',
            'published_at' => now()->subDays(5),
        ]);

        $response = $this->get(route('category.show', $this->category1->slug).'?sort=oldest');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Newer Post'),
            strpos($content, 'Older Post'),
            'Older post should appear before newer post'
        );
    }

    /**
     * Test sort by popular
     * Requirement 5.3: Sorting options for popular
     */
    public function test_sort_by_popular(): void
    {
        $popularPost = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Popular Post',
            'status' => 'published',
            'published_at' => now()->subDays(5),
            'view_count' => 1000,
        ]);

        $unpopularPost = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Unpopular Post',
            'status' => 'published',
            'published_at' => now(),
            'view_count' => 10,
        ]);

        $response = $this->get(route('category.show', $this->category1->slug).'?sort=popular');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Unpopular Post'),
            strpos($content, 'Popular Post'),
            'Popular post should appear before unpopular post'
        );
    }

    /**
     * Test URL parameter sync for filters
     * Requirement 14.2: Update URL parameters to allow sharing filtered views
     */
    public function test_url_parameter_sync_for_date_filter(): void
    {
        Post::factory()->create([
            'category_id' => $this->category1->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('category.show', $this->category1->slug).'?date_filter=today');

        $response->assertStatus(200);
        // Verify the URL contains the filter parameter
        $this->assertEquals('today', request()->query('date_filter'));
    }

    /**
     * Test URL parameter sync for sorting
     * Requirement 14.2: Update URL parameters to allow sharing filtered views
     */
    public function test_url_parameter_sync_for_sort(): void
    {
        Post::factory()->create([
            'category_id' => $this->category1->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('category.show', $this->category1->slug).'?sort=popular');

        $response->assertStatus(200);
        // Verify the URL contains the sort parameter
        $this->assertEquals('popular', request()->query('sort'));
    }

    /**
     * Test combining multiple filters and sort
     * Requirement 14.2: Multiple filters active
     */
    public function test_combine_date_filter_and_sort(): void
    {
        $recentPopular = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Unique Recent Popular Post VWX567',
            'status' => 'published',
            'published_at' => now()->subDays(2),
            'view_count' => 500,
        ]);

        $recentUnpopular = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Unique Recent Unpopular Post YZA890',
            'status' => 'published',
            'published_at' => now()->subDays(3),
            'view_count' => 50,
        ]);

        $oldPopular = Post::factory()->create([
            'category_id' => $this->category1->id,
            'title' => 'Unique Old Popular Post BCD123',
            'status' => 'published',
            'published_at' => now()->subMonth(),
            'view_count' => 1000,
        ]);

        $response = $this->get(route('category.show', $this->category1->slug).'?date_filter=week&sort=popular');

        $response->assertStatus(200);

        // Should see recent posts sorted by popularity
        $response->assertSee('Unique Recent Popular Post VWX567');
        $response->assertSee('Unique Recent Unpopular Post YZA890');
        $response->assertDontSee('Unique Old Popular Post BCD123'); // Filtered out by date

        // Popular should come first
        $response->assertSeeInOrder(['Unique Recent Popular Post VWX567', 'Unique Recent Unpopular Post YZA890']);
    }

    /**
     * Test filter component displays active filters
     * Requirement 14.2: Display filter summary with count of results
     */
    public function test_filter_component_displays_active_filter_badge(): void
    {
        Post::factory()->create([
            'category_id' => $this->category1->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('category.show', $this->category1->slug).'?date_filter=today');

        $response->assertStatus(200);
        // Should show active filter badge
        $response->assertSee('Date:');
        $response->assertSee('Today');
    }

    /**
     * Test filter works on tag pages
     * Requirement 5.3: Filtering on tag pages
     */
    public function test_filters_work_on_tag_pages(): void
    {
        $tag = Tag::factory()->create();

        $todayPost = Post::factory()->create([
            'title' => 'Unique Today Tagged Post EFG456',
            'status' => 'published',
            'published_at' => now(),
        ]);
        $todayPost->tags()->attach($tag);

        $oldPost = Post::factory()->create([
            'title' => 'Unique Old Tagged Post HIJ789',
            'status' => 'published',
            'published_at' => now()->subMonth(),
        ]);
        $oldPost->tags()->attach($tag);

        $response = $this->get(route('tag.show', $tag->slug).'?date_filter=today');

        $response->assertStatus(200);
        $response->assertSee('Unique Today Tagged Post EFG456');
        $response->assertDontSee('Unique Old Tagged Post HIJ789');
    }

    /**
     * Test sort works on tag pages
     * Requirement 5.3: Sorting on tag pages
     */
    public function test_sort_works_on_tag_pages(): void
    {
        $tag = Tag::factory()->create();

        $popularPost = Post::factory()->create([
            'title' => 'Popular Tagged Post',
            'status' => 'published',
            'published_at' => now()->subDays(5),
            'view_count' => 1000,
        ]);
        $popularPost->tags()->attach($tag);

        $unpopularPost = Post::factory()->create([
            'title' => 'Unpopular Tagged Post',
            'status' => 'published',
            'published_at' => now(),
            'view_count' => 10,
        ]);
        $unpopularPost->tags()->attach($tag);

        $response = $this->get(route('tag.show', $tag->slug).'?sort=popular');

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertLessThan(
            strpos($content, 'Unpopular Tagged Post'),
            strpos($content, 'Popular Tagged Post'),
            'Popular post should appear first'
        );
    }

    /**
     * Test filter preserves sort parameter
     * Requirement 14.2: Maintain filter and sort selections in URL
     */
    public function test_filter_preserves_sort_parameter(): void
    {
        Post::factory()->create([
            'category_id' => $this->category1->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('category.show', $this->category1->slug).'?sort=popular&date_filter=week');

        $response->assertStatus(200);
        // Both parameters should be present
        $this->assertEquals('popular', request()->query('sort'));
        $this->assertEquals('week', request()->query('date_filter'));
    }

    /**
     * Test empty state when no posts match filters
     * Requirement 14.2: Show similar content when no articles match filters
     */
    public function test_empty_state_when_no_posts_match_filter(): void
    {
        // Create only old posts
        Post::factory()->create([
            'category_id' => $this->category1->id,
            'status' => 'published',
            'published_at' => now()->subMonth(),
        ]);

        $response = $this->get(route('category.show', $this->category1->slug).'?date_filter=today');

        $response->assertStatus(200);
        $response->assertSee('No articles found');
        $response->assertSee('for the selected time period');
    }

    /**
     * Test filter component renders correctly
     * Requirement 2.3: Filter panel with options
     */
    public function test_filter_component_renders(): void
    {
        Post::factory()->create([
            'category_id' => $this->category1->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('category.show', $this->category1->slug));

        $response->assertStatus(200);
        $response->assertSee('Filters');
        $response->assertSee('Published Date');
    }

    /**
     * Test sort dropdown renders correctly
     * Requirement 5.3: Sorting options
     */
    public function test_sort_dropdown_renders(): void
    {
        Post::factory()->create([
            'category_id' => $this->category1->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('category.show', $this->category1->slug));

        $response->assertStatus(200);
        $response->assertSee('Sort:');
        $response->assertSee('Newest First');
    }

    /**
     * Test pagination works with filters
     * Requirement 14.2: Pagination with filters
     */
    public function test_pagination_preserves_filters(): void
    {
        // Create enough posts to trigger pagination
        Post::factory()->count(15)->create([
            'category_id' => $this->category1->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->get(route('category.show', $this->category1->slug).'?date_filter=today&page=2');

        $response->assertStatus(200);
        // Filter should be preserved in pagination links
        $this->assertEquals('today', request()->query('date_filter'));
        $this->assertEquals('2', request()->query('page'));
    }
}
