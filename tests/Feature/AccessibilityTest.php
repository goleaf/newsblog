<?php

namespace Tests\Feature;

use Tests\TestCase;

class AccessibilityTest extends TestCase
{
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


