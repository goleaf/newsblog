<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostRevisionOnUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_revision_is_created_when_post_is_updated(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'title' => 'Before',
            'content' => 'Before content',
            'excerpt' => 'Before excerpt',
        ]);

        $this->actingAs($user);

        $this->assertEquals(0, $post->revisions()->count());

        $post->update([
            'title' => 'After',
            'content' => 'After content',
            'excerpt' => 'After excerpt',
        ]);

        $post->refresh();

        $this->assertEquals(1, $post->revisions()->count());
        $latest = $post->revisions()->first();
        $this->assertEquals('Before', $latest->title);
        $this->assertEquals('Before content', $latest->content);
        $this->assertEquals('Before excerpt', $latest->excerpt);
    }
}
