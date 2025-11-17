<?php

namespace Tests\Unit\Services;

use App\Enums\CommentStatus;
use App\Enums\ModerationReason;
use App\Models\Comment;
use App\Models\User;
use App\Models\UserReputation;
use App\Services\AutoModerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AutoModerationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AutoModerationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AutoModerationService;
    }

    public function test_detects_prohibited_words(): void
    {
        $user = User::factory()->create();
        $content = 'Buy cheap viagra now!';

        $flags = $this->service->shouldFlag($content, $user);

        $this->assertContains(ModerationReason::ProhibitedContent, $flags);
    }

    public function test_detects_spam_patterns(): void
    {
        $user = User::factory()->create();
        $content = 'Check out these links: http://spam1.com http://spam2.com http://spam3.com';

        $flags = $this->service->shouldFlag($content, $user);

        $this->assertContains(ModerationReason::Spam, $flags);
    }

    public function test_flags_low_reputation_users(): void
    {
        $user = User::factory()->create();
        $content = 'This is a normal comment';

        $flags = $this->service->shouldFlag($content, $user);

        $this->assertContains(ModerationReason::LowQuality, $flags);
    }

    public function test_trusted_users_are_auto_approved(): void
    {
        $user = User::factory()->create();
        UserReputation::create([
            'user_id' => $user->id,
            'score' => 100,
            'level' => 'trusted',
        ]);

        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'This is a good comment',
        ]);

        $status = $this->service->moderateComment($comment);

        $this->assertEquals(CommentStatus::Approved, $status);
    }

    public function test_auto_rejects_severe_violations(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'content' => 'Buy cheap viagra now!',
        ]);

        $status = $this->service->moderateComment($comment);

        $this->assertEquals(CommentStatus::Rejected, $status);
    }

    public function test_calculates_spam_score(): void
    {
        $user = User::factory()->create();
        $content = 'Buy cheap products now! http://spam1.com http://spam2.com http://spam3.com';

        $score = $this->service->calculateSpamScore($content, $user);

        $this->assertGreaterThan(0, $score);
        $this->assertLessThanOrEqual(100, $score);
    }

    public function test_analyzes_content_comprehensively(): void
    {
        $user = User::factory()->create();
        $content = 'This is a test comment';

        $analysis = $this->service->analyzeContent($content, $user);

        $this->assertArrayHasKey('spam_score', $analysis);
        $this->assertArrayHasKey('flags', $analysis);
        $this->assertArrayHasKey('has_prohibited_words', $analysis);
        $this->assertArrayHasKey('user_trusted', $analysis);
    }
}
