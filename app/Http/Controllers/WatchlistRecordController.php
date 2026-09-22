<?php

namespace App\Http\Controllers;

use App\Services\WatchlistService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WatchlistRecordController extends Controller
{
    public function __construct(
        protected WatchlistService $watchlist,
    ) {
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        $record = $this->watchlist->getWatchRecord($user);

        if ($request->wantsJson()) {
            return response()->json(['record' => $record]);
        }

        return Inertia::render('WatchlistRecord', [
            'record' => $record,
        ]);
    }
}