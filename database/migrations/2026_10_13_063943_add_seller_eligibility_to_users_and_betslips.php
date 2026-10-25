<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Rolling betslip-pick win rate over the configured window.
            // Null until the user has at least min_settled_picks settled
            // picks — a rate on a tiny sample is noise, not signal.
            $table->decimal('pick_win_rate_rolling', 5, 4)
                ->nullable()
                ->after('is_admin');

            // Total settled betslip picks in the window. Denominator side
            // of the above; also drives the "3 more picks to qualify" UI.
            $table->unsignedInteger('picks_settled_rolling')
                ->default(0)
                ->after('pick_win_rate_rolling');

            // ineligible | sellable | warned | delisted
            $table->string('sellable_status')
                ->default('ineligible')
                ->after('picks_settled_rolling');

            $table->timestamp('sellable_status_changed_at')
                ->nullable()
                ->after('sellable_status');

            $table->timestamp('sellable_warned_at')
                ->nullable()
                ->after('sellable_status_changed_at');

            $table->index('pick_win_rate_rolling');
            $table->index('picks_settled_rolling');
            $table->index('sellable_status');
        });

        Schema::table('betslips', function (Blueprint $table) {
            // Set at creation time based on the creator's status then.
            // Never retroactively flipped: an upgrade to sellable does not
            // re-list historical betslips. The seller explicitly lists them.
            $table->boolean('is_sellable')
                ->default(false)
                ->after('is_winner');

            // Set by the recompute command when a seller is delisted.
            // Live betslips get is_delisted = true; the marketplace
            // excludes them. Recovering seller status does not clear this
            // flag — the seller must explicitly re-list.
            $table->boolean('is_delisted')
                ->default(false)
                ->after('is_sellable');

            // When the betslip stopped being pending/underway. Needed for
            // the rolling window query. Backfilled as NULL for existing
            // rows; they'll be treated as "outside the window" until they
            // settle. A one-shot data migration can backfill from
            // updated_at if you want strictness.
            $table->timestamp('settled_at')
                ->nullable()
                ->after('is_delisted');

            $table->index('is_sellable');
            $table->index('is_delisted');
            $table->index(['user_id', 'settled_at']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['pick_win_rate_rolling']);
            $table->dropIndex(['picks_settled_rolling']);
            $table->dropIndex(['sellable_status']);
            $table->dropColumn([
                'pick_win_rate_rolling',
                'picks_settled_rolling',
                'sellable_status',
                'sellable_status_changed_at',
                'sellable_warned_at',
            ]);
        });

        Schema::table('betslips', function (Blueprint $table) {
            $table->dropIndex(['is_sellable']);
            $table->dropIndex(['is_delisted']);
            $table->dropIndex(['user_id', 'settled_at']);
            $table->dropColumn([
                'is_sellable',
                'is_delisted',
                'settled_at',
            ]);
        });
    }
};