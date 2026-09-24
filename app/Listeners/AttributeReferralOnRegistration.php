<?php

namespace App\Listeners;

use App\Services\ReferralService;
use Illuminate\Auth\Events\Registered;

class AttributeReferralOnRegistration
{
    public function __construct(
        protected ReferralService $referralService,
    ) {
    }

    public function handle(Registered $event): void
    {
        // Prefer the code submitted with the form (manual entry or the
        // prefilled value the user didn't clear). Fall back to the session
        // code set by /r/{code} when the field was left empty.
        $fromRequest = trim((string) request()->input('referral_code', ''));
        $fromSession = session('referral_code');

        // Always clear the session key, valid or not.
        session()->forget('referral_code');

        $code = $fromRequest !== '' ? $fromRequest : $fromSession;

        if (!is_string($code) || $code === '') {
            return;
        }

        $this->referralService->attribute($event->user, $code);
    }
}