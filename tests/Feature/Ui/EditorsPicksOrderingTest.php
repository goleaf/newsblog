<?php

namespace Tests\Feature\Ui;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorsPicksOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_only_published_picks(): void
    {
        $published = Post::factory()->create([
            'title' => 'Published Pick',
            'is_editors_pick' => true,
            'editors_pick_order' => 1,
            'status' => PostStatus::Published->value,
            'published_at' => now()->subDay(),
        ]);

        $draft = Post::factory()->create([
            'title' => 'Draft Pick',
            'is_editors_pick' => true,
            'editors_pick_order' => 2,
            'status' => PostStatus::Draft->value,
        ]);

        $response = $this->get(route('editors-picks.index'));
        $response->assertOk();
        $response->assertSeeText('Published Pick');
        $response->assertDontSeeText('Draft Pick');
    }

    public function test_update_order_persists_new_order_and_sets_pick_flag(): void
    {
        $posts = Post::factory()->count(3)->create([
            'status' => PostStatus::Published->value,
            'published_at' => now()->subDay(),
            'is_editors_pick' => false,
            'editors_pick_order' => null,
        ]);

        $response = $this->post(route('editors-picks.order'), [
            'order' => [$posts[2]->id, $posts[0]->id, $posts[1]->id],
        ]);

        $response->assertRedirect(route('editors-picks.index'));
        $this->assertDatabaseHas('posts', [
            'id' => $posts[2]->id,
            'is_editors_pick' => true,
            'editors_pick_order' => 1,
        ]);
        $this->assertDatabaseHas('posts', [
            'id' => $posts[0]->id,
            'is_editors_pick' => true,
            'editors_pick_order' => 2,
        ]);
        $this->assertDatabaseHas('posts', [
            'id' => $posts[1]->id,
            'is_editors_pick' => true,
            'editors_pick_order' => 3,
        ]);
    }
}



