<template>
    <div
        class="flex min-h-screen flex-col bg-[#070b14] px-4 py-6 font-sans text-slate-200 selection:bg-sky-500/30 selection:text-white lg:px-8 lg:py-10"
    >
        <div class="mx-auto w-full max-w-7xl space-y-6">
            <!-- HEADER ARCHITECTURE: USER CONSOLE PROFILE -->

            <div
                class="flex w-full items-start justify-between gap-3 border-b border-gray-800/60 pb-6"
            >
                <!-- LEFT: avatar + profile info (min-w-0 allows truncation) -->
                <div class="flex min-w-0 items-center gap-3 md:gap-4">
                    <div class="relative flex-shrink-0">
                        <img
                            :src="buyer_data.user.avatar"
                            :alt="buyer_data.user.name"
                            class="h-12 w-12 rounded border border-sky-500/30 bg-[#111622] md:h-14 md:w-14"
                        />
                        <span
                            v-if="buyer_data.user.is_verified"
                            class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-sky-500 text-[8px] font-black text-[#070b14]"
                            title="VERIFIED SQUAD"
                            >✓</span
                        >
                    </div>

                    <div class="min-w-0">
                        <div
                            class="flex items-center gap-2 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                        >
                            <span>BUYER</span>
                            <!-- <span
                                v-if="!buyer_data.user.is_verified"
                                class="rounded border border-amber-500/30 px-1 text-[8px] font-bold text-amber-500"
                                >[ UNVERIFIED ]</span
                            > -->
                        </div>

                        <h1
                            class="mt-0.5 truncate font-mono text-lg font-black tracking-tight text-white uppercase md:text-xl"
                        >
                            {{ buyer_data.user.name }}
                        </h1>

                        <p
                            class="truncate text-[10px] font-medium text-slate-400"
                        >
                            Code: {{ buyer_data.user.code }} • Joined
                            {{ buyer_data.user.member_since }}
                        </p>
                    </div>
                </div>
                <!-- RIGHT: switcher + bell -->
                <div class="flex flex-shrink-0 items-center gap-2">
                    <DashboardSwitcher active="buyer" />
                    <NotificationBell
                        :initial-unread-count="
                            buyer_data.notifications.unread_count
                        "
                    />
                </div>
            </div>

            <!-- Compact Wallet (Mobile Only) -->
            <div
                class="block overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40 shadow-[0_0_15px_rgba(16,185,129,0.02)] md:hidden"
            >
                <div
                    class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
                >
                    <span
                        class="text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                        >WALLET</span
                    >
                    <span
                        class="font-mono text-[9px] font-bold text-slate-500 uppercase"
                        >[{{ buyer_data.wallet.currency }}]</span
                    >
                </div>
                <div class="space-y-4 p-4">
                    <div
                        class="group relative overflow-hidden rounded border border-[#232d42] bg-[#0a101f] p-3 text-center"
                    >
                        <div
                            class="absolute inset-x-0 bottom-0 h-[2px] bg-emerald-500/30"
                        ></div>
                        <span
                            class="block text-[9px] font-black tracking-wider text-slate-500 uppercase"
                            >AVAILABLE BALANCE</span
                        >
                        <div
                            class="my-1 font-mono text-2xl font-black tracking-tight text-white"
                        >
                            {{ buyer_data.wallet.currency }}
                            {{
                                Number(
                                    buyer_data.wallet.balance,
                                ).toLocaleString(undefined, {
                                    minimumFractionDigits: 2,
                                })
                            }}
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <DepositPopover />

                        <WithdrawalPopover
                            :currency="buyer_data.wallet.currency"
                            :available-balance="
                                Number(buyer_data.wallet.balance)
                            "
                            :disabled="Number(buyer_data.wallet.balance) <= 0"
                            class="flex-1"
                        />
                    </div>

                    <div
                        class="grid grid-cols-1 gap-2.5 sm:grid-cols-3 lg:grid-cols-1"
                    >
                        <div
                            class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5 transition-colors hover:border-amber-500/20"
                        >
                            <div class="space-y-0.5">
                                <span
                                    class="block text-[9px] font-bold text-slate-500 uppercase"
                                    >In escrow</span
                                >
                                <span
                                    class="font-mono text-[10px] font-bold text-slate-400"
                                    >Held</span
                                >
                            </div>
                            <span
                                class="font-mono text-xs font-black text-amber-400"
                            >
                                {{ buyer_data.wallet.currency }}
                                {{
                                    Number(
                                        buyer_data.wallet.escrow_balance,
                                    ).toFixed(2)
                                }}
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5 transition-colors hover:border-sky-500/20"
                        >
                            <div class="space-y-0.5">
                                <span
                                    class="block text-[9px] font-bold text-slate-500 uppercase"
                                    >Total Deposited</span
                                >
                                <span
                                    class="font-mono text-[10px] font-bold text-slate-400"
                                    >Deposits
                                </span>
                            </div>
                            <span
                                class="font-mono text-xs font-bold text-sky-400"
                            >
                                {{ buyer_data.wallet.currency }}
                                {{
                                    Number(
                                        buyer_data.wallet.total_deposited,
                                    ).toFixed(2)
                                }}
                            </span>
                        </div>

                        <div
                            class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5 transition-colors hover:border-rose-500/20"
                        >
                            <div class="space-y-0.5">
                                <span
                                    class="block text-[9px] font-bold text-slate-500 uppercase"
                                    >Total Withdrawn</span
                                >
                                <span
                                    class="font-mono text-[10px] font-bold text-slate-400"
                                    >Withdrawals</span
                                >
                            </div>
                            <span
                                class="font-mono text-xs font-bold text-rose-400"
                            >
                                {{ buyer_data.wallet.currency }}
                                {{
                                    Number(
                                        buyer_data.wallet.total_withdrawn,
                                    ).toFixed(2)
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- GRID ROW 1: CORE TELEMETRY METRIC CARDS -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- WIN RATE METRIC -->
                <div
                    class="flex flex-col justify-between rounded border border-[#232d42] bg-[#111622] p-4 shadow-[0_0_15px_rgba(14,165,233,0.02)]"
                >
                    <div>
                        <span
                            class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                            >WIN RATE</span
                        >
                        <div class="mt-2 flex items-baseline gap-2">
                            <span
                                class="font-mono text-3xl font-black tracking-tight text-white"
                                >{{ buyer_data.performance.win_rate }}%</span
                            >
                            <!-- No trend for now, optional -->
                        </div>
                    </div>
                    <div
                        class="mt-4 flex justify-between border-t border-gray-800/40 pt-2 font-mono text-[10px] tracking-tight text-slate-400 uppercase"
                    >
                        <span>Total Purchases:</span>
                        <span class="font-bold text-white">{{
                            buyer_data.performance.total_purchases
                        }}</span>
                    </div>
                </div>

                <!-- TOTAL PURCHASES METRIC -->
                <div
                    class="flex flex-col justify-between rounded border border-[#232d42] bg-[#111622] p-4 shadow-[0_0_15px_rgba(16,185,129,0.02)]"
                >
                    <div>
                        <span
                            class="text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                            >TOTAL PURCHASES</span
                        >
                        <div class="mt-2 flex items-baseline gap-2">
                            <span
                                class="font-mono text-3xl font-black tracking-tight text-white"
                                >{{
                                    buyer_data.performance.total_purchases
                                }}</span
                            >
                            <span
                                class="rounded bg-amber-500/10 px-1.5 py-0.5 text-[9px] font-black tracking-wide text-amber-400 uppercase"
                            >
                                {{ buyer_data.purchases.pending }} PENDING
                            </span>
                        </div>
                    </div>
                    <div
                        class="mt-4 flex justify-between border-t border-gray-800/40 pt-2 font-mono text-[10px] tracking-tight text-slate-400 uppercase"
                    >
                        <span>Avg Price:</span>
                        <span class="font-bold text-white"
                            >KES
                            {{
                                Number(
                                    buyer_data.performance.avg_price,
                                ).toFixed(2)
                            }}</span
                        >
                    </div>
                </div>

                <!-- TOTAL SPENT METRIC -->
                <div
                    class="flex flex-col justify-between rounded border border-[#232d42] bg-[#111622] p-4 shadow-[0_0_15px_rgba(245,158,11,0.02)]"
                >
                    <div>
                        <span
                            class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                            >TOTAL SPENT</span
                        >
                        <div class="mt-2 flex items-baseline gap-2">
                            <span
                                class="font-mono text-3xl font-black tracking-tight text-white"
                                >KES
                                {{
                                    buyer_data.financial.total_spent.toLocaleString()
                                }}</span
                            >
                        </div>
                    </div>
                    <div
                        class="mt-4 flex justify-between border-t border-gray-800/40 pt-2 font-mono text-[10px] tracking-tight text-slate-400 uppercase"
                    >
                        <span>Net Spent:</span>
                        <span class="font-bold text-amber-400"
                            >KES
                            {{
                                Number(buyer_data.financial.net_spent).toFixed(
                                    2,
                                )
                            }}</span
                        >
                    </div>
                </div>

                <!-- REFUND RATE METRIC -->
                <div
                    class="flex flex-col justify-between rounded border border-[#232d42] bg-[#111622] p-4"
                >
                    <div>
                        <span
                            class="text-[10px] font-black tracking-widest text-purple-400 uppercase"
                            >REFUND RATE</span
                        >
                        <div class="mt-2 flex items-baseline gap-2">
                            <span
                                class="font-mono text-3xl font-black tracking-tight text-white"
                            >
                                {{ buyer_data.performance.refund_rate }}%
                            </span>
                            <span
                                class="rounded bg-purple-500/10 px-1.5 py-0.5 text-[9px] font-black tracking-wide text-purple-400 uppercase"
                            >
                                REFUNDED:
                                {{
                                    Number(
                                        buyer_data.performance.total_refunded,
                                    ).toFixed(2)
                                }}
                            </span>
                        </div>
                    </div>
                    <div
                        class="mt-4 flex justify-between border-t border-gray-800/40 pt-2 font-mono text-[10px] tracking-tight text-slate-400 uppercase"
                    >
                        <span>Balance:</span>
                        <span class="font-bold text-white"
                            >KES
                            {{
                                Number(buyer_data.quick_stats.balance).toFixed(
                                    2,
                                )
                            }}</span
                        >
                    </div>
                </div>
            </div>

            <!-- GRID ROW 2: MANAGEMENT SPLITS AND STREAM DATA -->
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                <!-- LEFT TRACK: LIVE ACTIVE PURCHASES & ACTIVITY -->
                <div class="space-y-6 lg:col-span-8">
                    <!-- ACTIVE PURCHASES -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                            >
                                ACTIVE PURCHASES [{{
                                    buyer_data.purchases.active
                                }}]</span
                            >
                            <span
                                class="font-mono text-[9px] text-slate-500 uppercase"
                                >Your purchases</span
                            >
                        </div>

                        <div class="divide-y divide-gray-800/40">
                            <div
                                v-if="buyer_data.purchases.recent?.length === 0"
                                class="p-6 text-center font-mono text-xs text-slate-500 uppercase"
                            >
                                No active purchases currently.
                            </div>
                            <BetslipsTable :betslips="purchasesForTable" />
                        </div>
                    </div>
                    <!-- SETTLED BETSLIPS -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                            >
                                Settled Betslips [{{
                                    buyer_data.settled_outcomes.length
                                }}]
                            </span>
                            <span
                                class="font-mono text-[9px] text-slate-500 uppercase"
                            >
                                Outcomes
                            </span>
                        </div>

                        <div class="divide-y divide-gray-800/40">
                            <div
                                v-for="outcome in buyer_data.settled_outcomes"
                                :key="outcome.id"
                                class="flex items-center justify-between gap-3 p-3 transition-colors hover:bg-[#111a30]/40"
                            >
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        :class="[
                                            'flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-sm font-mono text-[9px] font-black select-none',
                                            outcome.outcome === 'won'
                                                ? 'bg-emerald-500 text-[#070b14]'
                                                : outcome.outcome === 'voided'
                                                  ? 'bg-slate-500 text-[#070b14]'
                                                  : 'bg-rose-500 text-white',
                                        ]"
                                    >
                                        {{
                                            outcome.outcome === 'won'
                                                ? 'W'
                                                : outcome.outcome === 'voided'
                                                  ? 'V'
                                                  : 'L'
                                        }}
                                    </span>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="truncate font-mono text-[11px] font-bold text-sky-400"
                                            >
                                                {{ outcome.betslip_code }}
                                            </span>
                                        </div>
                                        <p
                                            class="mt-0.5 truncate text-[10px] text-slate-500"
                                        >
                                            from {{ outcome.seller_name }} ·
                                            {{ outcome.settled_ago }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p
                                        class="font-mono text-xs font-black"
                                        :class="
                                            outcome.outcome === 'won'
                                                ? 'text-rose-400'
                                                : 'text-emerald-400'
                                        "
                                    >
                                        {{
                                            outcome.outcome === 'won'
                                                ? '-'
                                                : '+'
                                        }}KES
                                        {{ Number(outcome.price).toFixed(2) }}
                                    </p>
                                    <p
                                        class="text-[9px] text-slate-500 uppercase"
                                    >
                                        {{
                                            outcome.outcome === 'won'
                                                ? 'Paid out'
                                                : outcome.outcome === 'voided'
                                                  ? 'Voided'
                                                  : 'Refunded'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="buyer_data.settled_outcomes.length === 0"
                                class="p-6 text-center font-mono text-xs text-slate-500 uppercase"
                            >
                                No settled betslips yet.
                            </div>
                        </div>
                    </div>
                    <!-- WATCH RECORD -->
                    <div
                        v-if="buyer_data.watch_record.settled_count > 0"
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                                >
                                    Watch Record
                                </span>
                                <InfoPopover title="How the record works">
                                    <p>
                                        Every slip you watch is logged here once
                                        it settles. Each one counts as a flat 1
                                        unit stake at the slip's listed total
                                        odds.
                                    </p>
                                    <div
                                        class="rounded border border-[#232d42] bg-[#070b14] px-3 py-2 font-mono text-[11px]"
                                    >
                                        <p class="text-emerald-400">
                                            Win: + (odds − 1) units
                                        </p>
                                        <p class="text-rose-400">
                                            Loss: − 1 unit
                                        </p>
                                        <p class="text-slate-400">
                                            Void: 0 units
                                        </p>
                                    </div>
                                    <p class="text-slate-400">
                                        This is a hypothetical P/L — you never
                                        staked real money on watched slips.
                                    </p>
                                </InfoPopover>
                            </div>
                            <Link
                                href="/watchlist/record"
                                class="font-mono text-[9px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300"
                            >
                                Full record →
                            </Link>
                        </div>

                        <!-- Aggregate row -->
                        <div
                            class="grid grid-cols-3 divide-x divide-[#232d42]/60"
                        >
                            <div class="p-3">
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                >
                                    Paper P/L
                                </span>
                                <p
                                    class="mt-1 font-mono text-sm font-black"
                                    :class="
                                        buyer_data.watch_record.units > 0
                                            ? 'text-emerald-400'
                                            : buyer_data.watch_record.units < 0
                                              ? 'text-rose-400'
                                              : 'text-slate-400'
                                    "
                                >
                                    {{
                                        buyer_data.watch_record.units > 0
                                            ? '+'
                                            : ''
                                    }}{{
                                        buyer_data.watch_record.units.toFixed(
                                            2,
                                        )
                                    }}u
                                </p>
                            </div>
                            <div class="p-3">
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                >
                                    Watched
                                </span>
                                <p
                                    class="mt-1 font-mono text-sm font-black text-white"
                                >
                                    {{ buyer_data.watch_record.settled_count }}
                                </p>
                            </div>
                            <div class="p-3">
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                >
                                    Breakdown
                                </span>
                                <p class="mt-1 font-mono text-sm font-black">
                                    <span class="text-emerald-400"
                                        >{{
                                            buyer_data.watch_record.won_count
                                        }}W</span
                                    >
                                    <span class="mx-1 text-slate-600">·</span>
                                    <span class="text-rose-400"
                                        >{{
                                            buyer_data.watch_record.lost_count
                                        }}L</span
                                    >
                                    <span class="mx-1 text-slate-600">·</span>
                                    <span class="text-slate-400"
                                        >{{
                                            buyer_data.watch_record
                                                .voided_count
                                        }}V</span
                                    >
                                </p>
                            </div>
                        </div>

                        <!-- Top sellers -->
                        <div
                            v-if="buyer_data.watch_record.sellers.length > 0"
                            class="border-t border-[#232d42]/60"
                        >
                            <div class="divide-y divide-[#232d42]/40">
                                <div
                                    v-for="s in buyer_data.watch_record.sellers.slice(
                                        0,
                                        3,
                                    )"
                                    :key="s.seller_id"
                                    class="flex items-center justify-between gap-3 p-3 transition-colors hover:bg-[#111a30]/40"
                                >
                                    <div class="min-w-0">
                                        <p
                                            class="truncate font-mono text-[11px] font-bold text-slate-300"
                                        >
                                            {{ s.seller_name }}
                                        </p>
                                        <p
                                            class="mt-0.5 text-[10px] text-slate-500"
                                        >
                                            {{ s.settled_count }} settled ·
                                            {{ s.won_count }}W ·
                                            {{ s.lost_count }}L
                                        </p>
                                    </div>
                                    <span
                                        class="font-mono text-xs font-black"
                                        :class="
                                            s.units > 0
                                                ? 'text-emerald-400'
                                                : s.units < 0
                                                  ? 'text-rose-400'
                                                  : 'text-slate-400'
                                        "
                                    >
                                        {{ s.units > 0 ? '+' : ''
                                        }}{{ s.units.toFixed(2) }}u
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- RECENT ACTIVITY AUDIT LOG -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-slate-300 uppercase"
                                >Recent activity</span
                            >
                        </div>
                        <div
                            class="no-scrollbar max-h-[340px] space-y-2.5 overflow-y-auto p-4"
                        >
                            <div
                                v-for="(act, index) in buyer_data.activity"
                                :key="index"
                                class="flex items-center justify-between gap-4 rounded border border-[#232d42]/70 bg-[#111622] p-3 transition-colors hover:border-slate-700"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        :class="[
                                            'flex h-5 w-5 items-center justify-center rounded-sm font-mono text-[9px] font-black select-none',
                                            toneBadgeClass(act.tone),
                                        ]"
                                    >
                                        {{ act.badge }}
                                    </span>
                                    <span
                                        class="text-[11px] font-bold tracking-wide text-slate-300 uppercase"
                                        >{{ act.message }}</span
                                    >
                                </div>
                                <span
                                    class="font-mono text-[9px] font-bold whitespace-nowrap text-slate-500"
                                    >{{ act.time_ago }}</span
                                >
                            </div>
                            <div
                                v-if="buyer_data.activity.length === 0"
                                class="p-4 text-center font-mono text-xs text-slate-500"
                            >
                                No recent activity.
                            </div>
                        </div>
                    </div>

                    <!-- TRANSACTIONS TABLE -->
                    <TransactionsTable
                        :transactions="buyer_data.wallet.recent_transactions"
                    />

                    <!-- FOLLOWING TABLE (Sellers the buyer follows) -->
                    <FollowingTable
                        v-if="buyer_data.following_stats"
                        :following="buyer_data.following_stats.recent"
                        title="Sellers You Follow"
                    />
                </div>

                <!-- RIGHT TRACK: STATISTICAL DISTRIBUTION AND PROFILE BLOCKS -->
                <div class="space-y-6 lg:col-span-4">
                    <!-- WALLET DETAILS (Desktop only) -->
                    <div
                        class="hidden overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40 shadow-[0_0_15px_rgba(16,185,129,0.02)] md:block"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                                >ACCOUNT: WALLET DETAILS</span
                            >
                            <span
                                class="font-mono text-[9px] font-bold text-slate-500 uppercase"
                                >[{{ buyer_data.wallet.currency }}]</span
                            >
                        </div>

                        <div class="space-y-4 p-4">
                            <div
                                class="group relative overflow-hidden rounded border border-[#232d42] bg-[#0a101f] p-3 text-center"
                            >
                                <div
                                    class="absolute inset-x-0 bottom-0 h-[2px] bg-emerald-500/30"
                                ></div>
                                <span
                                    class="block text-[9px] font-black tracking-wider text-slate-500 uppercase"
                                    >LIQUID AVAILABLE BALANCE</span
                                >
                                <div
                                    class="my-1 font-mono text-2xl font-black tracking-tight text-white"
                                >
                                    {{ buyer_data.wallet.currency }}
                                    {{
                                        Number(
                                            buyer_data.wallet.balance,
                                        ).toLocaleString(undefined, {
                                            minimumFractionDigits: 2,
                                        })
                                    }}
                                </div>
                            </div>
                            <div class="flex flex-col gap-2 sm:flex-row">
                                <DepositPopover />

                                <WithdrawalPopover
                                    :currency="buyer_data.wallet.currency"
                                    :available-balance="
                                        Number(buyer_data.wallet.balance)
                                    "
                                    :disabled="
                                        Number(buyer_data.wallet.balance) <= 0
                                    "
                                    class="flex-1"
                                />
                            </div>

                            <div
                                class="grid grid-cols-1 gap-2.5 sm:grid-cols-3 lg:grid-cols-1"
                            >
                                <div
                                    class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5 transition-colors hover:border-amber-500/20"
                                >
                                    <div class="space-y-0.5">
                                        <span
                                            class="block text-[9px] font-bold text-slate-500 uppercase"
                                            >Pending Escrow</span
                                        >
                                        <span
                                            class="font-mono text-[10px] font-bold text-slate-400"
                                            >Locked contracts</span
                                        >
                                    </div>
                                    <span
                                        class="font-mono text-xs font-black text-amber-400"
                                    >
                                        {{ buyer_data.wallet.currency }}
                                        {{
                                            Number(
                                                buyer_data.wallet
                                                    .escrow_balance,
                                            ).toFixed(2)
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5 transition-colors hover:border-sky-500/20"
                                >
                                    <div class="space-y-0.5">
                                        <span
                                            class="block text-[9px] font-bold text-slate-500 uppercase"
                                            >Total Deposited</span
                                        >
                                        <span
                                            class="font-mono text-[10px] font-bold text-slate-400"
                                            >Node injections</span
                                        >
                                    </div>
                                    <span
                                        class="font-mono text-xs font-bold text-sky-400"
                                    >
                                        {{ buyer_data.wallet.currency }}
                                        {{
                                            Number(
                                                buyer_data.wallet
                                                    .total_deposited,
                                            ).toFixed(2)
                                        }}
                                    </span>
                                </div>

                                <div
                                    class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5 transition-colors hover:border-rose-500/20"
                                >
                                    <div class="space-y-0.5">
                                        <span
                                            class="block text-[9px] font-bold text-slate-500 uppercase"
                                            >Total Withdrawn</span
                                        >
                                        <span
                                            class="font-mono text-[10px] font-bold text-slate-400"
                                            >Cleared revenue</span
                                        >
                                    </div>
                                    <span
                                        class="font-mono text-xs font-bold text-rose-400"
                                    >
                                        {{ buyer_data.wallet.currency }}
                                        {{
                                            Number(
                                                buyer_data.wallet
                                                    .total_withdrawn,
                                            ).toFixed(2)
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RECENT FORM RADAR STRIP -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-slate-200 uppercase"
                                >Recent form</span
                            >
                        </div>
                        <div class="p-4">
                            <div class="flex flex-wrap gap-1.5">
                                <div
                                    v-for="(form, index) in buyer_data
                                        .performance.recent_form"
                                    :key="index"
                                    :class="[
                                        'flex h-7 w-7 cursor-help flex-col items-center justify-center rounded-sm font-mono text-xs font-black transition-transform select-none hover:scale-105',
                                        form.status === 'W'
                                            ? 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                            : form.status === 'L'
                                              ? 'border border-rose-500/30 bg-rose-500/10 text-rose-400'
                                              : 'border border-amber-500/30 bg-amber-500/10 text-amber-400',
                                    ]"
                                    :title="`Date: ${form.date}`"
                                >
                                    {{ form.status }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- EPOCH WIN RATE BREAKDOWN COMPASS -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                                >Win rate over time</span
                            >
                        </div>
                        <div class="space-y-3.5 p-4">
                            <div
                                v-for="(rate, label) in buyer_data.performance
                                    .win_rate_breakdown"
                                :key="label"
                                class="space-y-1"
                            >
                                <div
                                    class="flex items-center justify-between font-mono text-[10px] font-black tracking-wider text-slate-400 uppercase"
                                >
                                    <span>{{ label.replace(/_/g, ' ') }}</span>
                                    <span class="font-mono text-white"
                                        >{{ rate }}%</span
                                    >
                                </div>
                                <div
                                    class="h-1.5 w-full overflow-hidden rounded border border-[#232d42] bg-[#111622]"
                                >
                                    <div
                                        class="h-full bg-gradient-to-r from-sky-500 to-emerald-400 transition-all duration-500"
                                        :style="{ width: `${rate}%` }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- PURCHASE STATUS DISTRIBUTION -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                                >Purchase status</span
                            >
                        </div>
                        <div class="space-y-3 p-4">
                            <div
                                v-for="status in buyer_data.charts
                                    .status_distribution"
                                :key="status.status"
                                class="space-y-1"
                            >
                                <div
                                    class="flex items-center justify-between text-[10px] font-bold text-slate-400 uppercase"
                                >
                                    <span>{{ status.status }}</span>
                                    <span
                                        class="font-mono font-bold text-amber-400"
                                        >{{ status.count }}</span
                                    >
                                </div>
                                <div
                                    class="h-1 w-full overflow-hidden rounded bg-[#111622]"
                                >
                                    <div
                                        class="h-full bg-amber-500"
                                        :style="{
                                            width: `${
                                                buyer_data.purchases.total > 0
                                                    ? (status.count /
                                                          buyer_data.purchases
                                                              .total) *
                                                      100
                                                    : 0
                                            }%`,
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TOP SELLERS PERFORMANCE -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-purple-400 uppercase"
                                >TOP SELLERS</span
                            >
                        </div>
                        <div class="space-y-3 p-4">
                            <div
                                v-for="seller in buyer_data.charts
                                    .seller_performance"
                                :key="seller.seller_name"
                                class="flex items-center justify-between border-b border-gray-800/30 pb-2 last:border-0 last:pb-0"
                            >
                                <div>
                                    <span
                                        class="text-[11px] font-bold text-slate-400 uppercase"
                                        >{{ seller.seller_name }}</span
                                    >
                                    <span class="ml-2 text-[9px] text-slate-500"
                                        >({{ seller.total }} purchases)</span
                                    >
                                </div>
                                <span
                                    class="rounded border border-purple-500/20 bg-purple-500/10 px-1.5 py-0.5 font-mono text-xs font-black text-purple-400"
                                    >{{ seller.win_rate }}% WR</span
                                >
                            </div>
                            <div
                                v-if="
                                    buyer_data.charts.seller_performance
                                        .length === 0
                                "
                                class="py-2 text-center text-[10px] text-slate-500 uppercase"
                            >
                                No seller data available
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import BetslipsTable from '@/components/BetslipsTable.vue';
import FollowingTable from '@/components/FollowingTable.vue';
import TransactionsTable from '@/components/TransactionsTable.vue';
import InfoPopover from '@/components/InfoPopover.vue';
import DepositPopover from '@/components/DepositPopover.vue';
import WithdrawalPopover from '@/components/WithdrawalPopover.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import DashboardSwitcher from '@/components/DashboardSwitcher.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const buyer_data = page.props.buyer_data;

const purchasesForTable = computed(() => {
    return (buyer_data.purchases.recent || []).map((p) => ({
        id: p.id,
        code: p.betslip_code,
        legs: p.legs ?? 0,
        total_odds: p.total_odds,
        price: p.price,
        remaining: 1,
        status: p.status,
        is_winner: p.is_winner,
        purchases: 1,
        is_expiring_soon: false,
        seller_name: p.seller_name,
    }));
});

const toneBadgeClass = (tone) => {
    switch (tone) {
        case 'positive':
            return 'bg-emerald-500 text-[#070b14]';
        case 'negative':
            return 'bg-rose-500 text-white';
        case 'pending':
            return 'bg-amber-500 text-[#070b14]';
        case 'neutral':
        default:
            return 'bg-slate-500 text-white';
    }
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
