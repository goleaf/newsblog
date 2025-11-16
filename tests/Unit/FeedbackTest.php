<?php

namespace Tests\Unit;

use App\Models\Feedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_casts_reviewed_at_to_carbon_instance(): void
    {
        $user = User::factory()->create();
        $reviewer = User::factory()->create(['role' => 'admin']);

        $feedback = Feedback::create([
            'user_id' => $user->id,
            'type' => 'bug',
            'subject' => 'Sample feedback',
            'message' => 'Feature request details',
            'status' => 'open',
            'admin_notes' => 'Investigating',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->assertInstanceOf(Carbon::class, $feedback->reviewed_at);
    }

    public function test_relationships_resolve_associated_users(): void
    {
        $user = User::factory()->create();
        $reviewer = User::factory()->create(['role' => 'editor']);

        $feedback = Feedback::create([
            'user_id' => $user->id,
            'type' => 'feature',
            'subject' => 'Need new option',
            'message' => 'Please add option',
            'status' => 'reviewed',
            'admin_notes' => 'Planned',
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
        ]);

        $this->assertTrue($feedback->user->is($user));
        $this->assertTrue($feedback->reviewer->is($reviewer));
    }
}


