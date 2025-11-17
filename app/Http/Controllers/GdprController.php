<?php

namespace App\Http\Controllers;

use App\Http\Requests\DeleteAccountRequest;
use App\Mail\AccountDeletionConfirmation;
use App\Services\GdprService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class GdprController extends Controller
{
    public function __construct(private GdprService $gdprService) {}

    /**
     * Accept cookie consent
     */
    public function acceptConsent()
    {
        $cookie = $this->gdprService->storeConsent(true);

        return response()->json(['success' => true])
            ->cookie($cookie);
    }

    /**
     * Decline cookie consent
     */
    public function declineConsent()
    {
        $cookie = $this->gdprService->storeConsent(false);

        return response()->json(['success' => true])
            ->cookie($cookie);
    }

    /**
     * Withdraw consent
     */
    public function withdrawConsent()
    {
        $cookies = $this->gdprService->withdrawConsent();

        $response = back()->with('success', 'Your consent has been withdrawn and non-essential cookies have been deleted.');

        foreach ($cookies as $cookie) {
            $response->cookie($cookie);
        }

        return $response;
    }

    /**
     * Export user data
     */
    public function exportData(Request $request)
    {
        $user = Auth::user();

        if (! $user) {
            abort(403, 'You must be logged in to export your data.');
        }

        $data = $this->gdprService->exportUserData($user);

        $filename = 'user_data_'.$user->id.'_'.now()->format('Y-m-d_His').'.json';

        return response()->json($data, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Show account deletion confirmation page
     */
    public function showDeleteAccount()
    {
        return view('gdpr.delete-account');
    }

    /**
     * Delete user account
     */
    public function deleteAccount(DeleteAccountRequest $request)
    {
        $user = Auth::user();

        // Send confirmation email before anonymization
        $originalEmail = $user->email;
        Mail::to($originalEmail)->queue(new AccountDeletionConfirmation($user));

        // Log out the user
        Auth::logout();

        // Anonymize user data
        $this->gdprService->anonymizeUser($user);

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Your account has been deleted and your data has been anonymized.');
    }

    /**
     * Show privacy policy page
     */
    public function privacyPolicy()
    {
        return view('gdpr.privacy-policy');
    }
}
