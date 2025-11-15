<?php

namespace Tests\Feature;

use App\Mail\NewsletterConfirmationMail;
use App\Mail\NewsletterVerificationMail;
use App\Models\Newsletter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_subscribe_to_newsletter(): void
    {
        Mail::fake();

        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
            'gdpr_consent' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('newsletters', [
            'email' => 'test@example.com',
            'status' => 'pending',
        ]);

        $newsletter = Newsletter::where('email', 'test@example.com')->first();
        $this->assertNotNull($newsletter->verification_token);
        $this->assertNotNull($newsletter->verification_token_expires_at);
        $this->assertNotNull($newsletter->unsubscribe_token);

        Mail::assertSent(NewsletterVerificationMail::class, function ($mail) use ($newsletter) {
            return $mail->newsletter->id === $newsletter->id;
        });
    }

    public function test_subscription_requires_gdpr_consent(): void
    {
        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
        ]);

        $response->assertSessionHasErrors('gdpr_consent');
        $this->assertDatabaseMissing('newsletters', [
            'email' => 'test@example.com',
        ]);
    }

    public function test_subscription_validates_email_format(): void
    {
        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'invalid-email',
            'gdpr_consent' => '1',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_subscription_via_json_returns_json_response(): void
    {
        Mail::fake();

        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
            'gdpr_consent' => true,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Please check your email to verify your subscription.',
        ]);

        $this->assertDatabaseHas('newsletters', [
            'email' => 'test@example.com',
            'status' => 'pending',
        ]);
    }

    public function test_duplicate_subscription_via_json_returns_error(): void
    {
        Newsletter::factory()->create([
            'email' => 'test@example.com',
            'status' => 'subscribed',
            'verified_at' => now(),
        ]);

        $response = $this->postJson(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
            'gdpr_consent' => true,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => false,
            'message' => 'This email is already subscribed to our newsletter.',
        ]);
    }

    public function test_duplicate_subscription_shows_info_message(): void
    {
        $newsletter = Newsletter::factory()->create([
            'email' => 'test@example.com',
            'status' => 'subscribed',
            'verified_at' => now(),
        ]);

        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
            'gdpr_consent' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('info');
    }

    public function test_unverified_subscription_resends_verification_email(): void
    {
        Mail::fake();

        $newsletter = Newsletter::factory()->create([
            'email' => 'test@example.com',
            'status' => 'pending',
            'verified_at' => null,
            'verification_token' => Newsletter::generateVerificationToken(),
            'verification_token_expires_at' => now()->addDays(7),
        ]);

        $oldToken = $newsletter->verification_token;

        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
            'gdpr_consent' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $newsletter->refresh();
        $this->assertNotEquals($oldToken, $newsletter->verification_token);

        Mail::assertSent(NewsletterVerificationMail::class);
    }

    public function test_unsubscribed_email_cannot_resubscribe(): void
    {
        Newsletter::factory()->create([
            'email' => 'test@example.com',
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);

        $response = $this->post(route('newsletter.subscribe'), [
            'email' => 'test@example.com',
            'gdpr_consent' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('info');
        $this->assertStringContainsString('unsubscribed', session('info'));
    }

    public function test_user_can_verify_subscription(): void
    {
        Mail::fake();

        $newsletter = Newsletter::factory()->create([
            'email' => 'test@example.com',
            'status' => 'pending',
            'verified_at' => null,
            'verification_token' => Newsletter::generateVerificationToken(),
            'verification_token_expires_at' => now()->addDays(7),
            'unsubscribe_token' => Newsletter::generateUnsubscribeToken(),
        ]);

        $response = $this->get(route('newsletter.verify', $newsletter->verification_token));

        $response->assertOk();
        $response->assertViewIs('newsletter.verified');

        $newsletter->refresh();
        $this->assertEquals('subscribed', $newsletter->status);
        $this->assertNotNull($newsletter->verified_at);
        $this->assertNull($newsletter->verification_token);

        Mail::assertSent(NewsletterConfirmationMail::class, function ($mail) use ($newsletter) {
            return $mail->newsletter->id === $newsletter->id;
        });
    }

    public function test_expired_verification_token_shows_error(): void
    {
        $newsletter = Newsletter::factory()->create([
            'email' => 'test@example.com',
            'status' => 'pending',
            'verification_token' => Newsletter::generateVerificationToken(),
            'verification_token_expires_at' => now()->subDay(),
        ]);

        $response = $this->get(route('newsletter.verify', $newsletter->verification_token));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    }

    public function test_invalid_verification_token_shows_error(): void
    {
        $response = $this->get(route('newsletter.verify', 'invalid-token'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    }

    public function test_user_can_unsubscribe(): void
    {
        $newsletter = Newsletter::factory()->create([
            'email' => 'test@example.com',
            'status' => 'subscribed',
            'verified_at' => now(),
            'unsubscribe_token' => Newsletter::generateUnsubscribeToken(),
        ]);

        $response = $this->get(route('newsletter.unsubscribe', $newsletter->unsubscribe_token));

        $response->assertOk();
        $response->assertViewIs('newsletter.unsubscribed');

        $newsletter->refresh();
        $this->assertEquals('unsubscribed', $newsletter->status);
        $this->assertNotNull($newsletter->unsubscribed_at);
    }

    public function test_invalid_unsubscribe_token_shows_error(): void
    {
        $response = $this->get(route('newsletter.unsubscribe', 'invalid-token'));

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('error');
    }

    public function test_admin_can_view_newsletter_subscribers(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);
        Newsletter::factory()->count(5)->create();

        $response = $this->actingAs($admin)->get(route('admin.newsletters.index'));

        $response->assertOk();
        $response->assertViewIs('admin.newsletters.index');
        $response->assertViewHas('newsletters');
        $response->assertViewHas('stats');
    }

    public function test_admin_can_export_verified_subscribers(): void
    {
        $admin = \App\Models\User::factory()->create(['role' => 'admin']);

        Newsletter::factory()->create([
            'email' => 'verified@example.com',
            'status' => 'subscribed',
            'verified_at' => now(),
        ]);

        Newsletter::factory()->create([
            'email' => 'pending@example.com',
            'status' => 'pending',
            'verified_at' => null,
        ]);

        $response = $this->actingAs($admin)->get(route('admin.newsletters.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('verified@example.com', $response->getContent());
        $this->assertStringNotContainsString('pending@example.com', $response->getContent());
    }

    public function test_verification_token_expires_after_seven_days(): void
    {
        $newsletter = Newsletter::factory()->create([
            'verification_token' => Newsletter::generateVerificationToken(),
            'verification_token_expires_at' => now()->addDays(7),
        ]);

        $this->assertTrue($newsletter->isVerificationTokenValid());

        $newsletter->update([
            'verification_token_expires_at' => now()->subDay(),
        ]);

        $this->assertFalse($newsletter->isVerificationTokenValid());
    }
}
