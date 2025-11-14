<?php

namespace App\Http\Controllers;

use App\Mail\NewsletterConfirmationMail;
use App\Mail\NewsletterVerificationMail;
use App\Models\Newsletter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $request->validate([
            'email' => 'required|email|max:255',
            'gdpr_consent' => 'required|accepted',
        ]);

        // Check if email already exists
        $existing = Newsletter::where('email', $request->email)->first();

        if ($existing) {
            if ($existing->status === 'subscribed' && $existing->verified_at) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This email is already subscribed to our newsletter.',
                    ]);
                }
                return back()->with('info', 'This email is already subscribed to our newsletter.');
            }

            if ($existing->status === 'unsubscribed') {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'This email has previously unsubscribed. Please contact us to resubscribe.',
                    ]);
                }
                return back()->with('info', 'This email has previously unsubscribed. Please contact us to resubscribe.');
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
                return back()->with('success', 'Verification email resent. Please check your inbox.');
            }
        }

        // Create new subscription
        $newsletter = Newsletter::create([
            'email' => $request->email,
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
        return back()->with('success', 'Please check your email to verify your subscription.');
    }

    public function verify(Request $request, string $token): View|RedirectResponse
    {
        $newsletter = Newsletter::where('verification_token', $token)->first();

        if (! $newsletter) {
            return redirect()->route('home')->with('error', 'Invalid verification token.');
        }

        if (! $newsletter->isVerificationTokenValid()) {
            return redirect()->route('home')->with('error', 'Verification token has expired. Please subscribe again.');
        }

        if ($newsletter->verified_at) {
            return redirect()->route('home')->with('info', 'Your subscription is already verified.');
        }

        // Verify the subscription
        $newsletter->verify();

        // Send confirmation email
        Mail::to($newsletter->email)->send(new NewsletterConfirmationMail($newsletter));

        return view('newsletter.verified', compact('newsletter'));
    }

    public function unsubscribe(Request $request, string $token): View|RedirectResponse
    {
        $newsletter = Newsletter::where('unsubscribe_token', $token)->first();

        if (! $newsletter) {
            return redirect()->route('home')->with('error', 'Invalid unsubscribe token.');
        }

        if ($newsletter->status === 'unsubscribed') {
            return view('newsletter.unsubscribed', ['alreadyUnsubscribed' => true]);
        }

        // Unsubscribe
        $newsletter->unsubscribe();

        return view('newsletter.unsubscribed', ['alreadyUnsubscribed' => false]);
    }
}
