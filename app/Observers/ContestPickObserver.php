<?php

namespace App\Observers;

use App\Models\ContestEntry;
use App\Models\ContestPick;

class ContestPickObserver
{
    /**
     * Host picks are locked the moment the contest is published. Nobody,
     * including the host, can edit them afterwards. Non-host picks remain
     * editable until the deadline.
     */
    public function updating(ContestPick $pick): void
    {
        $entry = $pick->entry;

        if (!$entry) {
            return;
        }

        $contest = $entry->contest;

        if (!$contest) {
            return;
        }

        if ((int) $entry->user_id === (int) $contest->host_id) {
            throw new \RuntimeException(
                'Host picks are locked once the contest is published.'
            );
        }
    }
}