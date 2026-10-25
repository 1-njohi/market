<?php

namespace App\Http\Controllers;

use App\Models\Contest;
use App\Models\ContestEntry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Notifications\ContestEntryStatusNotification;

class ContestEntryController extends Controller
{
    /**
     * Statuses in which the host may still approve a pending entry.
     *
     * `open`    — deadline not yet passed; ordinary approval.
     * `locked`  — deadline passed but no fixture has kicked off. Pending
     *             requests represent intent declared before the deadline,
     *             and the host needs a window to process them.
     *
     * Anything else (`underway`, `settled`, `cancelled`) closes the window:
     * approving an entry once play has started would let it score against
     * fixtures already played.
     */
    private const ACCEPTABLE_STATUSES = ['open', 'locked'];

    public function accept(Contest $contest, ContestEntry $entry): RedirectResponse
    {
        $this->authorize($contest, $entry);

        if (! in_array($contest->status, self::ACCEPTABLE_STATUSES, true)) {
            throw ValidationException::withMessages([
                'entry' => 'This contest is no longer accepting new entries.',
            ]);
        }

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