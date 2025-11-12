<?php

namespace Database\Factories;

use App\Models\Newsletter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @template TModel of \App\Models\Newsletter
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<TModel>
 */
class NewsletterFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var class-string<TModel>
     */
    protected $model = Newsletter::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['pending', 'subscribed', 'unsubscribed']);
        $verifiedAt = $status === 'subscribed' ? fake()->dateTimeBetween('-1 year', 'now') : null;

        return [
            'email' => fake()->unique()->safeEmail(),
            'status' => $status,
            'verified_at' => $verifiedAt,
            'token' => \Illuminate\Support\Str::random(32),
            'verification_token' => $status === 'pending' ? Newsletter::generateVerificationToken() : null,
            'verification_token_expires_at' => $status === 'pending' ? now()->addDays(7) : null,
            'unsubscribe_token' => Newsletter::generateUnsubscribeToken(),
            'unsubscribed_at' => $status === 'unsubscribed' ? fake()->dateTimeBetween('-1 year', 'now') : null,
        ];
    }

    /**
     * Indicate that the newsletter subscription is verified.
     */
    public function verified(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'subscribed',
            'verified_at' => now(),
            'verification_token' => null,
            'verification_token_expires_at' => null,
        ]);
    }

    /**
     * Indicate that the newsletter subscription is pending verification.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'verified_at' => null,
            'verification_token' => Newsletter::generateVerificationToken(),
            'verification_token_expires_at' => now()->addDays(7),
        ]);
    }

    /**
     * Indicate that the newsletter subscription is unsubscribed.
     */
    public function unsubscribed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'unsubscribed',
            'unsubscribed_at' => now(),
        ]);
    }
}
