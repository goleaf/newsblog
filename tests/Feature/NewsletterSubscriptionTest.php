<?php

namespace Tests\Feature;

use App\Mail\NewsletterVerificationMail;
use App\Models\Newsletter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    public function test_newsletter_widget_validation_works(): void
    {
        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'invalid-email',
            'gdpr_consent' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    }

    public function test_gdpr_consent_is_required(): void
    {
        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['gdpr_consent']);
    }

    public function test_successful_newsletter_subscription(): void
    {
        Mail::fake();

        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
            'gdpr_consent' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Please check your email to verify your subscription.',
        ]);

        $this->assertDatabaseHas('newsletters', [
            'email' => 'test@example.com',
            'status' => 'pending',
        ]);

        Mail::assertSent(NewsletterVerificationMail::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    public function test_already_subscribed_email_returns_info_message(): void
    {
        $newsletter = Newsletter::factory()->create([
            'email' => 'test@example.com',
            'status' => 'subscribed',
            'verified_at' => now(),
        ]);

        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
            'gdpr_consent' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'message' => 'This email is already subscribed to our newsletter.',
        ]);
    }

    public function test_unsubscribed_email_cannot_resubscribe(): void
    {
        $newsletter = Newsletter::factory()->create([
            'email' => 'test@example.com',
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);

        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
            'gdpr_consent' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => false,
            'message' => 'This email has previously unsubscribed. Please contact us to resubscribe.',
        ]);
    }

    public function test_verification_email_can_be_resent(): void
    {
        Mail::fake();

        $newsletter = Newsletter::factory()->create([
            'email' => 'test@example.com',
            'status' => 'pending',
            'verified_at' => null,
        ]);

        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
            'gdpr_consent' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Verification email resent. Please check your inbox.',
        ]);

        Mail::assertSent(NewsletterVerificationMail::class);
    }

    public function test_newsletter_verification_flow(): void
    {
        Mail::fake();

        $newsletter = Newsletter::factory()->create([
            'email' => 'test@example.com',
            'status' => 'pending',
            'verified_at' => null,
            'verification_token' => Newsletter::generateVerificationToken(),
            'verification_token_expires_at' => now()->addDays(7),
        ]);

        $response = $this->get(route('newsletter.verify', $newsletter->verification_token));

        $response->assertStatus(200);
        $response->assertViewIs('newsletter.verified');

        $newsletter->refresh();
        $this->assertEquals('subscribed', $newsletter->status);
        $this->assertNotNull($newsletter->verified_at);
        $this->assertNull($newsletter->verification_token);

        Mail::assertSent(\App\Mail\NewsletterConfirmationMail::class);
    }

    public function test_invalid_verification_token_redirects_with_error(): void
    {
        $response = $this->get(route('newsletter.verify', 'invalid-token'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error', 'Invalid verification token.');
    }

    public function test_expired_verification_token_redirects_with_error(): void
    {
        $newsletter = Newsletter::factory()->create([
            'email' => 'test@example.com',
            'status' => 'pending',
            'verification_token' => Newsletter::generateVerificationToken(),
            'verification_token_expires_at' => now()->subDays(1),
        ]);

        $response = $this->get(route('newsletter.verify', $newsletter->verification_token));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error', 'Verification token has expired. Please subscribe again.');
    }

    public function test_unsubscribe_functionality(): void
    {
        $newsletter = Newsletter::factory()->create([
            'email' => 'test@example.com',
            'status' => 'subscribed',
            'verified_at' => now(),
            'unsubscribe_token' => Newsletter::generateUnsubscribeToken(),
        ]);

        $response = $this->get(route('newsletter.unsubscribe', $newsletter->unsubscribe_token));

        $response->assertStatus(200);
        $response->assertViewIs('newsletter.unsubscribed');
        $response->assertViewHas('alreadyUnsubscribed', false);

        $newsletter->refresh();
        $this->assertEquals('unsubscribed', $newsletter->status);
        $this->assertNotNull($newsletter->unsubscribed_at);
    }

    public function test_invalid_unsubscribe_token_redirects_with_error(): void
    {
        $response = $this->get(route('newsletter.unsubscribe', 'invalid-token'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error', 'Invalid unsubscribe token.');
    }

    public function test_already_unsubscribed_shows_appropriate_message(): void
    {
        $newsletter = Newsletter::factory()->create([
            'email' => 'test@example.com',
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
            'unsubscribe_token' => Newsletter::generateUnsubscribeToken(),
        ]);

        $response = $this->get(route('newsletter.unsubscribe', $newsletter->unsubscribe_token));

        $response->assertStatus(200);
        $response->assertViewIs('newsletter.unsubscribed');
        $response->assertViewHas('alreadyUnsubscribed', true);
    }
}
