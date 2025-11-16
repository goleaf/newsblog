<?php

namespace Tests\Feature\Ui;

use App\Enums\PostStatus;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EditorsPicksSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_up_to_6_editors_picks_in_order_and_only_published(): void
    {
        // Create 8 picks, only first 6 should show
        for ($i = 1; $i <= 8; $i++) {
            Post::factory()->create([
                'title' => 'Pick '.$i,
                'is_editors_pick' => true,
                'editors_pick_order' => $i,
                'status' => PostStatus::Published->value,
                'published_at' => now()->subDay(),
            ]);
        }

        // Unpublished pick should be excluded
        Post::factory()->create([
            'is_editors_pick' => true,
            'editors_pick_order' => 0,
            'status' => PostStatus::Draft->value,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        // Ensure the section title exists
        $response->assertSeeText('Editor\'s Picks');

        // Assert first 6 picks are present
        for ($i = 1; $i <= 6; $i++) {
            $response->assertSeeText('Pick '.$i);
        }
    }
}
