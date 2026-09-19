<?php

namespace App\Http\Controllers;

use App\Models\Betslip;
use App\Services\BetslipPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Carbon\Carbon;

class BetslipPurchaseController extends Controller
{
    protected BetslipPurchaseService $purchaseService;

    public function __construct(BetslipPurchaseService $purchaseService)
    {
        $this->purchaseService = $purchaseService;
    }

    public function purchase(Request $request)
    {
        $user = Auth::user();

        $betslip = Betslip::find($request->betslip_id);

        try {
            $purchase = $this->purchaseService->purchase($user, $betslip);

            $now = Carbon::now();

            $betslip_code = $purchase->Betslip->code;

            $message =  $now->format('d-m-Y H:i:s') . ": 'Betslip " . $betslip_code . "purchased successfully!";

            return redirect()
                ->route('betslip.show_guest', ['code' => $betslip_code])
                ->with('success', $message);

        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}