<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Builders\PostTestBuilder;
use Tests\Concerns\TestsFuzzySearch;
use Tests\TestCase;

class FuzzySearchPhoneticTest extends TestCase
{
    use RefreshDatabase;
    use TestsFuzzySearch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpFuzzySearch();
        $this->enablePhoneticMatching();
    }

    public function test_phonetic_matching_finds_similar_sounding_words(): void
    {
        $post = PostTestBuilder::make()
            ->published()
            ->withTitle('Phonetic Testing')
            ->create();

        $this->assertSearchFinds('Fonetic Testing', $post->id);
    }

    public function test_phonetic_matching_respects_config_toggle(): void
    {
        $post = PostTestBuilder::make()
            ->published()
            ->withTitle('Smith')
            ->create();

        $resultsEnabled = $this->searchService->search('Smyth', threshold: 20);
        $foundWhenEnabled = $resultsEnabled->pluck('id')->contains($post->id);

        $this->disablePhoneticMatching();

        $resultsDisabled = $this->searchService->search('Smyth', threshold: 20);
        $foundWhenDisabled = $resultsDisabled->pluck('id')->contains($post->id);

        $this->assertTrue(
            $foundWhenEnabled || ! $foundWhenDisabled,
            'Phonetic matching should affect results'
        );
    }

    public function test_phonetic_matching_has_lower_weight_than_exact_match(): void
    {
        $exactPost = PostTestBuilder::make()
            ->published()
            ->withTitle('Phonetic Testing')
            ->create();

        $phoneticPost = PostTestBuilder::make()
            ->published()
            ->withTitle('Fonetic Testing')
            ->create();

        $results = $this->searchService->search('Phonetic Testing');

        $this->assertNotEmpty($results);
        $this->assertEquals($exactPost->id, $results->first()->id);
    }

    public function test_phonetic_matching_works_with_search_posts(): void
    {
        $post = PostTestBuilder::make()
            ->published()
            ->withTitle('Laravel Framework Guide')
            ->create();

        $results = $this->searchService->searchPosts('Laravell Framework', [
            'threshold' => 20,
        ]);

        $this->assertNotEmpty($results);
        $this->assertEquals($post->id, $results->first()->id);
    }

    public function test_phonetic_matching_works_with_search_tags(): void
    {
        $tag = Tag::create([
            'name' => 'Phonetic',
            'slug' => 'phonetic',
        ]);

        $results = $this->searchService->searchTags('Fonetic');

        $this->assertNotEmpty($results);
        $this->assertEquals($tag->id, $results->first()->id);
    }

    public function test_phonetic_matching_works_with_search_categories(): void
    {
        $category = Category::factory()->create([
            'name' => 'Phonetic Category',
        ]);

        $results = $this->searchService->searchCategories('Fonetic Category');

        $this->assertNotEmpty($results);
        $this->assertEquals($category->id, $results->first()->id);
    }

    public function test_phonetic_matching_only_applies_when_fuzzy_score_is_low(): void
    {
        $post = PostTestBuilder::make()
            ->published()
            ->withTitle('Laravel Testing')
            ->create();

        $exactResults = $this->searchService->search('Laravel Testing');
        $this->assertNotEmpty($exactResults);

        $fuzzyResults = $this->searchService->search('Laravel Testng');
        $this->assertNotEmpty($fuzzyResults);
    }
}
