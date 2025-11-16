<?php

namespace Tests\Feature;

use App\Models\Newsletter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SendNewslettersCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_uses_service_when_content_not_provided(): void
    {
        Newsletter::factory()->verified()->create();

        // Create at least one published post to include in digest
        \App\Models\Post::factory()->create([
            'title' => 'Digest Post',
            'status' => 'published',
            'published_at' => now()->subHour(),
            'view_count' => 50,
        ]);

        $this->artisan('newsletters:send --period=daily')
            ->assertExitCode(0);

        $send = \App\Models\NewsletterSend::first();
        $this->assertNotNull($send);
        $this->assertStringContainsString('Digest Post', $send->content);
    }
}
