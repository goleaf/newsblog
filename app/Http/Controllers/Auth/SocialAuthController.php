<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the OAuth provider authentication page.
     */
    public function redirect(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from the OAuth provider.
     */
    public function callback(string $provider): RedirectResponse
    {
        $this->validateProvider($provider);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            return redirect()->route('login')
                ->withErrors(['email' => __('Unable to authenticate with :provider. Please try again.', ['provider' => ucfirst($provider)])]);
        }

        // Find or create user
        $user = $this->findOrCreateUser($socialUser, $provider);

        // Log the user in
        Auth::login($user, true);

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Find or create a user based on the social provider data.
     */
    protected function findOrCreateUser(object $socialUser, string $provider): User
    {
        // Check if user already exists with this social account
        $existingUser = User::whereHas('socialAccounts', function ($query) use ($provider, $socialUser) {
            $query->where('provider', $provider)
                  ->where('provider_id', $socialUser->getId());
        })->first();

        if ($existingUser) {
            // Update the provider token
            $existingUser->socialAccounts()
                ->where('provider', $provider)
                ->update(['provider_token' => $socialUser->token]);

            return $existingUser;
        }

        // Check if user exists with this email
        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            // Link the social account to existing user
            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_token' => $socialUser->token,
            ]);

            return $user;
        }

        // Create new user
        $user = User::create([
            'name' => $socialUser->getName() ?? $socialUser->getNickname() ?? 'User',
            'email' => $socialUser->getEmail(),
            'password' => Hash::make(Str::random(32)), // Random password for social auth users
            'role' => UserRole::User->value,
            'status' => UserStatus::Active->value,
            'email_verified_at' => now(), // Social auth emails are pre-verified
            'avatar' => $socialUser->getAvatar(),
        ]);

        // Create social account record
        $user->socialAccounts()->create([
            'provider' => $provider,
            'provider_id' => $socialUser->getId(),
            'provider_token' => $socialUser->token,
        ]);

        event(new Registered($user));

        return $user;
    }

    /**
     * Validate the OAuth provider.
     */
    protected function validateProvider(string $provider): void
    {
        $allowedProviders = ['google', 'github', 'twitter'];

        if (! in_array($provider, $allowedProviders)) {
            abort(404, 'Invalid OAuth provider');
        }
    }
}
