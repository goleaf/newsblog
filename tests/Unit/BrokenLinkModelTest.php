<?php

namespace Tests\Unit;

use App\Models\BrokenLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrokenLinkModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_scopes_and_mark_methods(): void
    {
        $broken = BrokenLink::factory()->broken()->create();
        $ok = BrokenLink::factory()->ok()->create();
        $ignored = BrokenLink::factory()->ignored()->create();

        $this->assertTrue(BrokenLink::pending()->get()->contains($broken));
        $this->assertFalse(BrokenLink::pending()->get()->contains($ok));
        $this->assertFalse(BrokenLink::pending()->get()->contains($ignored));

        $this->assertTrue(BrokenLink::fixed()->get()->contains($ok));
        $this->assertTrue(BrokenLink::ignored()->get()->contains($ignored));

        $broken->markAsFixed();
        $this->assertEquals('ok', $broken->fresh()->status);
        $this->assertNotNull($broken->fresh()->checked_at);

        $ok->markAsIgnored();
        $this->assertEquals('ignored', $ok->fresh()->status);
        $this->assertNotNull($ok->fresh()->checked_at);
    }
}
