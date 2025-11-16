<?php

namespace Tests\Feature;

use Tests\TestCase;

class UiComponentsTest extends TestCase
{
    // No middleware overrides

    /** @test */
    public function it_renders_ui_demo_page()
    {
        $response = $this->get(route('ui.demo'));
        $response->assertOk();
        $response->assertSee('Advanced UI Components Demo', false);
    }

    /** @test */
    public function gallery_component_renders_structure()
    {
        $response = $this->get(route('ui.demo'));
        $response->assertOk();
        $response->assertSee('Play', false);
        $response->assertSee('Full screen', false);
        // Prev/next controls and x-for template present
        $response->assertSee('‹', false);
        $response->assertSee('›', false);
        $response->assertSee('x-for="(img, idx) in images"', false);
        // Swipe gesture handlers present on container
        $response->assertSee('@touchstart', false);
    }

    /** @test */
    public function pull_quote_component_renders_text_and_attribution()
    {
        $response = $this->get(route('ui.demo'));
        $response->assertOk();
        $response->assertSee('Design is not just what it looks like', false);
        $response->assertSee('Steve Jobs', false);
    }

    /** @test */
    public function pull_quote_supports_left_and_right_alignment()
    {
        // Render component directly to verify class application
        $right = view('components.pull-quote', [
            'text' => 'Quote',
            'attribution' => 'Author',
            'align' => 'right',
        ])->render();
        $this->assertStringContainsString('md:float-right md:ml-6', $right);

        $left = view('components.pull-quote', [
            'text' => 'Quote',
            'attribution' => 'Author',
            'align' => 'left',
        ])->render();
        $this->assertStringContainsString('md:float-left md:mr-6', $left);
    }

    /** @test */
    public function social_embed_component_renders_fallback_card()
    {
        $response = $this->get(route('ui.demo'));
        $response->assertOk();
        // Server renders placeholder card with link; runtime text is hydrated via Alpine
        $response->assertSee('Open', false);
        $response->assertSee('twitter.com/jack/status/20', false);
    }

    /** @test */
    public function chart_component_renders_canvas()
    {
        $response = $this->get(route('ui.demo'));
        $response->assertOk();
        $response->assertSee('<canvas', false);
        // CSV support implies labels in markup via Alpine x-data config
        $response->assertSee('chartComponent', false);
    }
}
