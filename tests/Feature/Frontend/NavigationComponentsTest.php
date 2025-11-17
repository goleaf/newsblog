<?php

namespace Tests\Feature\Frontend;

use Tests\TestCase;

class NavigationComponentsTest extends TestCase
{
    public function test_navigation_renders_header_component(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        // Basic smoke check for presence of the header nav landmark
        $response->assertSee('<nav', false);
    }
}
