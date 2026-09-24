<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

class ReferralLinkController extends Controller
{
    /**
     * GET /r/{code}
     *
     * Stores the code in the session and redirects to the signup form.
     * Invalid codes still redirect — the user lands on register with no
     * attribution, which is more forgiving than a 404 for a shared link.
     */
    public function show(string $code): RedirectResponse
    {
        $normalized = strtoupper(trim($code));

        $referrer = User::where('referral_code', $normalized)->first();

        if ($referrer) {
            session(['referral_code' => $referrer->referral_code]);
        }

        return redirect('/register');
    }
}