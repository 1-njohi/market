<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ReferralTerm;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class ReferralTermsController extends Controller
{
    /**
     * Grant custom referral terms to a user.
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        if ((int) $user->id === (int) Auth::id()) {
            throw ValidationException::withMessages([
                'reward_percentage' => 'You cannot grant referral terms to yourself.',
            ]);
        }

        $validated = $request->validate([
            'reward_percentage'    => ['required', 'numeric', 'min:0.10', 'max:0.25'],
            'max_transactions'     => ['required', 'integer', 'min:5', 'max:100'],
            'window_months'        => ['required', 'integer', 'min:3', 'max:36'],
            'referee_discount_pct' => ['required', 'numeric', 'min:0', 'max:0.40'],
            'referee_discount_cap' => ['required', 'numeric', 'min:0', 'max:100'],
            'notes'                => ['nullable', 'string', 'max:1000'],
        ]);

        ReferralTerm::updateOrCreate(
            ['user_id' => $user->id],
            [
                'reward_percentage'    => $validated['reward_percentage'],
                'max_transactions'     => $validated['max_transactions'],
                'window_months'        => $validated['window_months'],
                'referee_discount_pct' => $validated['referee_discount_pct'],
                'referee_discount_cap' => $validated['referee_discount_cap'],
                'notes'                => $validated['notes'] ?? null,
                'granted_by'           => Auth::id(),
                'granted_at'           => now(),
            ]
        );

        return back()->with(
            'success',
            "Custom referral terms granted to {$user->name}."
        );
    }

    /**
     * Revoke custom referral terms, reverting the user to platform defaults.
     */
    public function destroy(User $user): RedirectResponse
    {
        $deleted = ReferralTerm::where('user_id', $user->id)->delete();

        if (!$deleted) {
            return back()->with('error', 'No custom terms to revoke.');
        }

        return back()->with(
            'success',
            "Custom referral terms revoked for {$user->name}."
        );
    }
}