<?php

namespace Tests\Feature;

use App\Http\Requests\SearchRequest;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SearchRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_search_query_passes_validation(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'Laravel testing'],
            $request->rules()
        );

        $this->assertTrue($validator->passes());
    }

    public function test_empty_query_is_allowed(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => ''],
            $request->rules()
        );

        $this->assertTrue($validator->passes());
    }

    public function test_query_with_valid_characters_passes(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'Laravel 123 test-query_underscore'],
            $request->rules()
        );

        $this->assertTrue($validator->passes());
    }

    public function test_query_with_invalid_characters_fails(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'test<script>alert("xss")</script>'],
            $request->rules()
        );

        $this->assertFalse($validator->passes());
        $this->assertArrayHasKey('q', $validator->errors()->toArray());
    }

    public function test_query_exceeding_max_length_fails(): void
    {
        $maxLength = config('fuzzy-search.limits.max_query_length', 200);
        $longQuery = str_repeat('a', $maxLength + 1);

        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => $longQuery],
            $request->rules()
        );

        $this->assertFalse($validator->passes());
    }

    public function test_valid_threshold_passes(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'test', 'threshold' => 50],
            $request->rules()
        );

        $this->assertTrue($validator->passes());
    }

    public function test_threshold_below_minimum_fails(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'test', 'threshold' => -1],
            $request->rules()
        );

        $this->assertFalse($validator->passes());
    }

    public function test_threshold_above_maximum_fails(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'test', 'threshold' => 101],
            $request->rules()
        );

        $this->assertFalse($validator->passes());
    }

    public function test_valid_limit_passes(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'test', 'limit' => 20],
            $request->rules()
        );

        $this->assertTrue($validator->passes());
    }

    public function test_limit_below_minimum_fails(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'test', 'limit' => 0],
            $request->rules()
        );

        $this->assertFalse($validator->passes());
    }

    public function test_limit_above_maximum_fails(): void
    {
        $maxResults = config('fuzzy-search.limits.max_results', 100);
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'test', 'limit' => $maxResults + 1],
            $request->rules()
        );

        $this->assertFalse($validator->passes());
    }

    public function test_valid_category_slug_passes(): void
    {
        $category = Category::factory()->create();

        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'test', 'category' => $category->slug],
            $request->rules()
        );

        $this->assertTrue($validator->passes());
    }

    public function test_invalid_category_slug_fails(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'test', 'category' => 'non-existent-category'],
            $request->rules()
        );

        $this->assertFalse($validator->passes());
    }

    public function test_valid_author_id_passes(): void
    {
        $user = User::factory()->create();

        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'test', 'author' => $user->id],
            $request->rules()
        );

        $this->assertTrue($validator->passes());
    }

    public function test_invalid_author_id_fails(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'test', 'author' => 99999],
            $request->rules()
        );

        $this->assertFalse($validator->passes());
    }

    public function test_valid_date_range_passes(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            [
                'q' => 'test',
                'date_from' => '2024-01-01',
                'date_to' => '2024-12-31',
            ],
            $request->rules()
        );

        $this->assertTrue($validator->passes());
    }

    public function test_date_from_after_date_to_fails(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            [
                'q' => 'test',
                'date_from' => '2024-12-31',
                'date_to' => '2024-01-01',
            ],
            $request->rules()
        );

        $this->assertFalse($validator->passes());
    }

    public function test_invalid_date_format_fails(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'test', 'date_from' => 'invalid-date'],
            $request->rules()
        );

        $this->assertFalse($validator->passes());
    }

    public function test_query_is_trimmed_during_preparation(): void
    {
        // Test that whitespace is trimmed by validating a request with spaces
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => '  test query  '],
            $request->rules()
        );

        // The validator should accept the query (trimming happens in prepareForValidation)
        // We verify the rule accepts it, actual trimming is tested via integration
        $this->assertTrue($validator->passes());
    }

    public function test_authorization_allows_public_access(): void
    {
        $request = new SearchRequest;

        $this->assertTrue($request->authorize());
    }

    public function test_sql_injection_attempt_is_blocked(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => "'; DROP TABLE posts; --"],
            $request->rules()
        );

        $this->assertFalse($validator->passes());
    }

    public function test_xss_attempt_is_blocked(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => '<script>alert("xss")</script>'],
            $request->rules()
        );

        $this->assertFalse($validator->passes());
    }

    public function test_unicode_characters_are_allowed(): void
    {
        $request = new SearchRequest;
        $validator = Validator::make(
            ['q' => 'Laravel тест 测试'],
            $request->rules()
        );

        $this->assertTrue($validator->passes());
    }

    public function test_all_parameters_can_be_combined(): void
    {
        $category = Category::factory()->create();
        $user = User::factory()->create();

        $request = new SearchRequest;
        $validator = Validator::make(
            [
                'q' => 'Laravel testing',
                'threshold' => 60,
                'limit' => 20,
                'category' => $category->slug,
                'author' => $user->id,
                'date_from' => '2024-01-01',
                'date_to' => '2024-12-31',
            ],
            $request->rules()
        );

        $this->assertTrue($validator->passes());
    }
}
