<?php

namespace Tests\Feature\Admin;

use App\Models\BrokenLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrokenLinksReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_broken_links_report(): void
    {
        $admin = User::factory()->admin()->create();
        $broken = BrokenLink::factory()->broken()->create();
        $ok = BrokenLink::factory()->ok()->create();
        $ignored = BrokenLink::factory()->ignored()->create();

        $response = $this->actingAs($admin)->get(route('admin.broken-links.index'));

        $response->assertOk();
        $response->assertSee('Broken Links Report');
        $response->assertSee((string) $broken->url);
        $response->assertSee((string) $ok->url);
        $response->assertSee((string) $ignored->url);
    }
}
