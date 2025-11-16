<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TagRelationshipsTest extends TestCase
{
    use RefreshDatabase;

    public function test_tag_belongs_to_many_posts(): void
    {
        $tag = Tag::factory()->create(['name' => 'Technology']);
        $posts = Post::factory()->count(5)->create();

        $tag->posts()->sync($posts->pluck('id')->all());

        $this->assertCount(5, $tag->posts);
        $this->assertEqualsCanonicalizing(
            $posts->pluck('id')->all(),
            $tag->posts->pluck('id')->all()
        );
    }

    public function test_get_posts_count_uses_published_scope_on_posts(): void
    {
        $tag = Tag::factory()->create();

        $publishedPosts = Post::factory()->count(3)->published()->create();
        $draftPosts = Post::factory()->count(2)->draft()->create();

        $tag->posts()->sync(
            $publishedPosts->pluck('id')
                ->merge($draftPosts->pluck('id'))
                ->all()
        );

        $this->assertSame(3, $tag->getPostsCount());
    }

    public function test_tag_can_have_description(): void
    {
        $tag = Tag::factory()->create([
            'name' => 'Laravel',
            'description' => 'The Laravel PHP framework',
        ]);

        $this->assertEquals('Laravel', $tag->name);
        $this->assertEquals('The Laravel PHP framework', $tag->description);
    }

    public function test_tag_description_is_optional(): void
    {
        $tag = Tag::factory()->create([
            'name' => 'PHP',
            'description' => null,
        ]);

        $this->assertNull($tag->description);
    }
}
