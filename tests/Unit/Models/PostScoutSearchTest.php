<?php

namespace Tests\Unit\Models;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostScoutSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_should_be_searchable_only_when_published(): void
    {
        $published = Post::factory()->published()->create();
        $draft = Post::factory()->draft()->create();

        $this->assertTrue($published->shouldBeSearchable());
        $this->assertFalse($draft->shouldBeSearchable());
    }

    public function test_to_searchable_array_contains_expected_fields(): void
    {
        $post = Post::factory()->published()->create([
            'title' => 'Searchable Title',
            'excerpt' => '<p>Excerpt with <strong>HTML</strong></p>',
            'content' => '<div>Content with <em>HTML</em> tags</div>',
        ]);

        $tag = Tag::factory()->create(['name' => 'Laravel']);
        $post->tags()->attach($tag->id);

        $data = $post->fresh()->toSearchableArray();

        $this->assertSame('Searchable Title', $data['title']);
        $this->assertIsString($data['excerpt']);
        $this->assertStringNotContainsString('<', $data['excerpt']);
        $this->assertIsString($data['content']);
        $this->assertStringNotContainsString('<', $data['content']);
        $this->assertEquals($post->user->name, $data['author']);
        $this->assertEquals($post->category->name, $data['category']);
        $this->assertContains('Laravel', $data['tags']);
        $this->assertIsInt($data['view_count']);
        $this->assertIsInt($data['reading_time']);
    }
}
