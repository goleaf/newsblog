<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessibilityFormsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Avoid unrelated performance tracking middleware causing test failures
        $this->withoutMiddleware(\App\Http\Middleware\TrackPerformance::class);
    }

    public function test_login_form_has_describedby_and_labels(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();

        $content = $response->getContent();

        // Email input has aria-describedby linking to hint
        $this->assertStringContainsString('id="login-email-hint"', $content);
        $this->assertStringContainsString('aria-describedby="login-email-hint', $content);

        // Password input has aria-describedby linking to hint
        $this->assertStringContainsString('id="login-password-hint"', $content);
        $this->assertStringContainsString('aria-describedby="login-password-hint', $content);
    }

    public function test_newsletter_widget_has_label_and_hint(): void
    {
        // Render the newsletter view directly with a stub widget
        $widget = (object) [
            'title' => 'Newsletter',
        ];

        $html = view('widgets.newsletter', compact('widget'))->render();

        $this->assertStringContainsString('id="newsletter-email"', $html);
        $this->assertStringContainsString('<label for="newsletter-email"', $html);
        $this->assertStringContainsString('id="newsletter-hint"', $html);
        $this->assertStringContainsString('aria-describedby="newsletter-hint"', $html);
    }
}
