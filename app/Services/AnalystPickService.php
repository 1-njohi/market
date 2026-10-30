<?php

namespace App\Services;

use App\Models\Betslip;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalystPickService
{
    /**
     * Called from BetslipController@store after the betslip and its pivot
     * rows have been written, still inside the same DB::transaction.
     * Validates the one-per-day rule and stamps the analyst fields.
     *
     * Does NOT create the betslip — that is BetslipController's job.
     */
    public function publishOnCreate(Betslip $betslip, User $analyst): void
    {
        if (!$analyst->isAnalyst()) {
            throw new \DomainException('Only analysts can publish analyst picks.');
        }

        if ($this->hasPublishedToday($analyst->id)) {
            throw new \DomainException('You have already published an analyst pick today.');
        }

        // These four fields are absent from BetslipObserver::IMMUTABLE,
        // so this update is allowed. caption/price/total_odds/code/user_id
        // stay untouched.
        $betslip->update([
            'is_analyst_slip' => true,
            'analyst_id' => $analyst->id,
            'analyst_published_at' => now(),
        ]);
    }

    public function hasPublishedToday(int $analystId): bool
    {
        return Betslip::analystSlips()
            ->where('analyst_id', $analystId)
            ->whereDate('analyst_published_at', today())
            ->whereNull('analyst_unpublished_reason')
            ->exists();
    }

    /**
     * Edit an existing analyst pick. Snapshots the pre-edit editorial
     * state into betslip_revisions, then applies the new state.
     *
     * $data shape:
     *   ['analyst_title' => ?string, 'reasoning' => ?string,
     *    'legs' => ?array<{odd_id:int, comment:string, confidence:int}>,
     *    'edit_reason' => ?string]
     */
    public function update(Betslip $betslip, array $data, User $editor): Betslip
    {
        if (!$betslip->isEditableAsAnalyst()) {
            throw new \DomainException('This pick cannot be edited.');
        }

        DB::transaction(function () use ($betslip, $data, $editor) {
            $betslip->revisions()->create([
                'edited_by' => $editor->id,
                'caption' => $betslip->caption,
                'reasoning' => $betslip->reasoning,
                'legs' => $this->legEditorialState($betslip),
                'edit_reason' => $data['edit_reason'] ?? null,
            ]);

            $betslip->update([
                'analyst_title' => $data['analyst_title'] ?? $betslip->analyst_title,
                'reasoning' => $data['reasoning'] ?? $betslip->reasoning,
                'is_edited' => true,
                'revision_count' => $betslip->revision_count + 1,
            ]);

            if (!empty($data['legs'])) {
                foreach ($data['legs'] as $leg) {
                    $betslip->odds()->updateExistingPivot($leg['odd_id'], [
                        'comment' => $leg['comment'],
                        'confidence' => $leg['confidence'],
                    ]);
                }
            }
        });

        return $betslip->fresh();
    }

    public function unpublish(Betslip $betslip, string $reason): void
    {
        if (in_array($betslip->status, ['settled', 'voided'], true)) {
            throw new \DomainException('Settled picks cannot be unpublished.');
        }

        $betslip->update([
            'analyst_published_at' => null,
            'analyst_unpublished_reason' => $reason,
        ]);
    }

    public function attachPostSettlementNote(Betslip $betslip, string $note): void
    {
        if ($betslip->settled_at === null) {
            throw new \DomainException('Notes can only be attached after settlement.');
        }

        $betslip->update([
            'post_settlement_note' => $note,
            'post_settlement_note_at' => now(),
        ]);
    }

    /**
     * Snapshot of the editable per-leg fields, keyed by odd_id.
     * Stored verbatim into betslip_revisions.legs.
     */
    protected function legEditorialState(Betslip $betslip): array
    {
        return $betslip->odds->map(fn($odd) => [
            'odd_id' => $odd->id,
            'comment' => $odd->pivot->comment,
            'confidence' => $odd->pivot->confidence,
        ])->values()->all();
    }
    public function updateByAnalyst(Betslip $pick, array $data, User $analyst): Betslip
    {
        if ($pick->analyst_id !== $analyst->id) {
            throw new \DomainException('You can only edit your own picks.');
        }
        return $this->update($pick, $data, $analyst);
    }

    public function unpublishByAnalyst(Betslip $pick, User $analyst): void
    {
        if ($pick->analyst_id !== $analyst->id) {
            throw new \DomainException('You can only unpublish your own picks.');
        }
        $this->unpublish($pick, 'Unpublished by analyst');
    }
}