<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubscribeNewsletterRequest;
use App\Http\Requests\UnsubscribeNewsletterRequest;
use App\Http\Requests\VerifyNewsletterRequest;
use App\Mail\NewsletterConfirmationMail;
use App\Mail\NewsletterVerificationMail;
use App\Models\Newsletter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

use function __;

class NewsletterController extends Controller
{
    /**
     * Subscribe to the newsletter with double opt-in.
     */
    public function subscribe(SubscribeNewsletterRequest $request)
    {
        $validated = $request->validated();

        // Check if email already exists
        $existing = Newsletter::where('email', $validated['email'])->first();

        if ($existing) {
            $status = is_string($existing->status) ? $existing->status : ($existing->status?->value ?? null);
            if ($status === 'subscribed' && $existing->verified_at) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This email is already subscribed to our newsletter.',
                    ]);
                }

                return back()->with('info', __('newsletter.subscribe.already'));
            }

            if ($status === 'unsubscribed') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This email has previously unsubscribed. Please contact us to resubscribe.',
                    ]);
                }

                return back()->with('info', __('newsletter.subscribe.unsubscribed'));
            }

            // Resend verification if not verified
            if (! $existing->verified_at) {
                $existing->update([
                    'verification_token' => Newsletter::generateVerificationToken(),
                    'verification_token_expires_at' => now()->addDays(7),
                ]);

                Mail::to($existing->email)->send(new NewsletterVerificationMail($existing));

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Verification email resent. Please check your inbox.',
                    ]);
                }

                return back()->with('success', __('newsletter.subscribe.resent'));
            }
        }

        // Create new subscription
        $newsletter = Newsletter::create([
            'email' => $validated['email'],
            'status' => 'pending',
            'verification_token' => Newsletter::generateVerificationToken(),
            'verification_token_expires_at' => now()->addDays(7),
            'unsubscribe_token' => Newsletter::generateUnsubscribeToken(),
        ]);

        // Send verification email
        Mail::to($newsletter->email)->send(new NewsletterVerificationMail($newsletter));

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Please check your email to verify your subscription.',
            ]);
        }

        return back()->with('success', __('newsletter.subscribe.success'));
    }

    /**
     * Verify email confirmation via token.
     */
    public function verify(VerifyNewsletterRequest $request, string $token): View|RedirectResponse
    {
        $validated = $request->validated();
        $token = $validated['token'];

        $newsletter = Newsletter::where('verification_token', $token)->first();

        if (! $newsletter) {
            return redirect()->route('home')->with('error', __('newsletter.verify.invalid'));
        }

        if (! $newsletter->isVerificationTokenValid()) {
            return redirect()->route('home')->with('error', __('newsletter.verify.expired'));
        }

        if ($newsletter->verified_at) {
            return redirect()->route('home')->with('info', __('newsletter.verify.already'));
        }

        // Verify the subscription
        $newsletter->verify();

        // Send confirmation email
        Mail::to($newsletter->email)->send(new NewsletterConfirmationMail($newsletter));

        return view('newsletter.verified', compact('newsletter'));
    }

    /**
     * Unsubscribe from the newsletter via token.
     */
    public function unsubscribe(UnsubscribeNewsletterRequest $request, string $token): View|RedirectResponse
    {
        $validated = $request->validated();
        $token = $validated['token'];

        $newsletter = Newsletter::where('unsubscribe_token', $token)->first();

        if (! $newsletter) {
            return redirect()->route('home')->with('error', __('newsletter.verify.invalid'));
        }

        if ($newsletter->status === 'unsubscribed') {
            return view('newsletter.unsubscribed', ['alreadyUnsubscribed' => true]);
        }

        // Unsubscribe
        $newsletter->unsubscribe();

        return view('newsletter.unsubscribed', ['alreadyUnsubscribed' => false]);
    }
}
