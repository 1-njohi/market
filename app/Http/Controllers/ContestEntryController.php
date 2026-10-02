<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use App\Notifications\ContestEntryStatusNotification;

class ContestEntryController extends Controller
{
    public function accept(Contest $contest, ContestEntry $entry): RedirectResponse
    {
        $this->authorize($contest, $entry);
    
        $entry->update(['status' => 'accepted']);
    
        $entry->user->notify(new ContestEntryStatusNotification(
            $contest,
            Auth::user(),
            'accepted',
        ));
    
        return back();
    }
    
    public function reject(Contest $contest, ContestEntry $entry): RedirectResponse
    {
        $this->authorize($contest, $entry);
    
        $entry->update(['status' => 'rejected']);
    
        $entry->user->notify(new ContestEntryStatusNotification(
            $contest,
            Auth::user(),
            'rejected',
        ));
    
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