<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class ContestEntryController extends Controller
{
    public function accept(Contest $contest, ContestEntry $entry): RedirectResponse
    {
        $this->authorize($contest, $entry);

        $entry->update(['status' => 'accepted']);

        return back();
    }

    public function reject(Contest $contest, ContestEntry $entry): RedirectResponse
    {
        $this->authorize($contest, $entry);

        $entry->update(['status' => 'rejected']);

        return back();
    }

    private function authorize(Contest $contest, ContestEntry $entry): void
    {
        if ((int) $contest->id !== (int) $entry->contest_id) {
            abort(404);
        }

        if ((int) $contest->host_id !== (int) Auth::id()) {
            abort(403);
        }
    }
}