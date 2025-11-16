<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AccessibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite.database', ':memory:');
    }

    public function test_homepage_contains_landmarks_and_skip_link(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        $response->assertSee('href="#main-content"', false);
        $response->assertSee('<main id="main-content"', false);
        $response->assertSee('<header', false);
        $response->assertSee('<footer', false);
        $response->assertSee('role="main"', false);
        $response->assertSee('role="banner"', false);
        $response->assertSee('role="contentinfo"', false);
    }
}
