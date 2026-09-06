<?php

namespace App\Http\Controllers;

use App\Models\Betslip;
use App\Services\BetslipPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BetslipPurchaseController extends Controller
{
    protected BetslipPurchaseService $purchaseService;

    public function __construct(BetslipPurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    public function purchase(Request $request)
    {
        // dd($request->all());
        $user = Auth::user();

        $betslip = Betslip::find($request->betslip_id);

        // Check if user can purchase
        // $message = $this->purchaseService->getPurchaseAvailabilityMessage($user, $betslip);

        \Log::info("sdfgdsa");
        // if ($message) {

        //     dd($message);
        //     return back()->with('error', $message);
        // }

        try {
            $purchase = $this->purchaseService->purchase($user, $betslip);

            \Log::info("Purchase: ");

            return back()->with('success', 'Betslip purchased successfully!');
        } catch (\Exception $e) {
            \Log::error($e);
            return back()->with('error', $e->getMessage());
        }
    }
}