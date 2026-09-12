<template>
    <div
        class="flex min-h-screen flex-col bg-[#070b14] px-4 py-6 font-sans text-slate-200 selection:bg-sky-500/30 selection:text-white lg:px-8 lg:py-10"
    >
        <div class="mx-auto w-full max-w-7xl space-y-6">
            <!-- ═══════════════ HEADER ═══════════════ -->
            <div
                class="flex w-full items-start justify-between gap-3 border-b border-gray-800/60 pb-6"
            >
                <!-- LEFT: profile -->
                <div class="flex min-w-0 items-center gap-3 md:gap-4">
                    <div class="relative flex-shrink-0">
                        <img
                            :src="seller_data.user.avatar"
                            :alt="seller_data.user.name"
                            class="h-12 w-12 rounded border border-emerald-500/30 bg-[#111622] md:h-14 md:w-14"
                        />
                        <span
                            v-if="seller_data.user.is_verified"
                            class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500 text-[8px] font-black text-[#070b14]"
                            title="VERIFIED"
                            >✓</span
                        >
                    </div>
                    <div class="min-w-0">
                        <div
                            class="flex items-center gap-2 text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                        >
                            <span>[ SELLER PROFILE ]</span>
                            <span
                                v-if="!seller_data.user.is_verified"
                                class="hidden rounded border border-amber-500/30 px-1 text-[8px] font-bold text-amber-500 sm:inline"
                                >[ UNVERIFIED ]</span
                            >
                        </div>
                        <h1
                            class="mt-0.5 truncate font-mono text-lg font-black tracking-tight text-white uppercase md:text-xl"
                        >
                            {{ seller_data.user.name }}
                        </h1>
                        <p class="truncate text-[10px] font-medium text-slate-400">
                            SYS_ID: {{ seller_data.user.email }} • Joined
                            {{ seller_data.user.member_since }}
                        </p>
                    </div>
                </div>

                <!-- RIGHT: switcher + bell -->
                <div class="flex flex-shrink-0 items-center gap-2">
                    <DashboardSwitcher active="seller" />
                    <NotificationBell
                        :initial-unread-count="
                            seller_data.notifications.unread_count
                        "
                    />
                </div>
            </div>

            <!-- ═══════════════ FEE TIER BANNER ═══════════════ -->
            <div
                class="overflow-hidden rounded border border-emerald-500/20 bg-gradient-to-r from-emerald-500/5 via-transparent to-transparent"
            >
                <div
                    class="flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <div class="flex items-center gap-4">
                        <div
                            :class="[
                                'flex h-12 w-12 items-center justify-center rounded-full font-mono text-lg font-black',
                                tierBadgeClass,
                            ]"
                        >
                            {{ tierRoman }}
                        </div>
                        <div>
                            <div
                                class="flex items-center gap-2 text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                            >
                                <span>FEE TIER {{ seller_data.fee_tier.current_tier }} / 4</span>
                            </div>
                            <div
                                class="mt-0.5 font-mono text-xl font-black tracking-tight text-white"
                            >
                                {{ feePercent }}%
                                <span class="text-xs font-normal text-slate-500"
                                    >platform fee</span
                                >
                            </div>
                            <p class="mt-0.5 text-[10px] text-slate-400">
                                Based on
                                <span class="font-mono font-bold text-white">{{
                                    seller_data.fee_tier.total_sales
                                }}</span>
                                lifetime sales
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="seller_data.fee_tier.sales_until_next_tier"
                        class="flex flex-col items-start gap-1 sm:items-end"
                    >
                        <span
                            class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                            >Next Tier</span
                        >
                        <div class="flex items-baseline gap-1.5">
                            <span
                                class="font-mono text-2xl font-black text-white"
                                >{{ seller_data.fee_tier.sales_until_next_tier }}</span
                            >
                            <span class="text-[10px] text-slate-400 uppercase"
                                >more sales →</span
                            >
                            <span class="font-mono text-sm font-bold text-emerald-400"
                                >{{ (seller_data.fee_tier.next_tier_percentage * 100).toFixed(0) }}%</span
                            >
                        </div>
                    </div>

                    <div
                        v-else
                        class="flex items-center gap-2 rounded border border-emerald-500/30 bg-emerald-500/10 px-3 py-1.5"
                    >
                        <span class="text-lg">🏆</span>
                        <span
                            class="text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                            >Highest Tier</span
                        >
                    </div>
                </div>
            </div>

            <!-- ═══════════════ MOBILE WALLET ═══════════════ -->
            <div
                class="block overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40 shadow-[0_0_15px_rgba(16,185,129,0.02)] md:hidden"
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
                        >[{{ seller_data.wallet.currency }}]</span
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
                            {{ seller_data.wallet.currency }}
                            {{
                                Number(
                                    seller_data.wallet.balance,
                                ).toLocaleString(undefined, {
                                    minimumFractionDigits: 2,
                                })
                            }}
                        </div>
                    </div>

                    <div class="flex flex-col gap-2 sm:flex-row">
                        <DepositPopover />
                        <WithdrawalPopover
                            :currency="seller_data.wallet.currency"
                            :available-balance="
                                Number(seller_data.wallet.balance)
                            "
                            :disabled="Number(seller_data.wallet.balance) <= 0"
                            class="flex-1"
                        />
                    </div>

                    <div
                        class="grid grid-cols-1 gap-2.5 sm:grid-cols-3 lg:grid-cols-1"
                    >
                        <div
                            class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5"
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
                                {{ seller_data.wallet.currency }}
                                {{
                                    Number(
                                        seller_data.wallet.pending_balance,
                                    ).toFixed(2)
                                }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5"
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
                                {{ seller_data.wallet.currency }}
                                {{
                                    Number(
                                        seller_data.wallet.total_deposited,
                                    ).toFixed(2)
                                }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5"
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
                                {{ seller_data.wallet.currency }}
                                {{
                                    Number(
                                        seller_data.wallet.total_withdrawn,
                                    ).toFixed(2)
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ═══════════════ METRIC CARDS ═══════════════ -->
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <!-- WIN RATE -->
                <div
                    class="flex flex-col justify-between rounded border border-[#232d42] bg-[#111622] p-4"
                >
                    <div>
                        <span
                            class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                            >WIN RATE</span
                        >
                        <div class="mt-2 flex items-baseline gap-2">
                            <span
                                class="font-mono text-3xl font-black tracking-tight text-white"
                                >{{ seller_data.performance.win_rate }}%</span
                            >
                            <span
                                :class="[
                                    'rounded px-1.5 py-0.5 text-[9px] font-black tracking-wide uppercase',
                                    seller_data.performance.win_rate_change >= 0
                                        ? 'bg-emerald-500/10 text-emerald-400'
                                        : 'bg-rose-500/10 text-rose-400',
                                ]"
                            >
                                {{
                                    seller_data.performance.win_rate_change >= 0
                                        ? '+'
                                        : ''
                                }}{{ seller_data.performance.win_rate_change }}%
                            </span>
                        </div>
                    </div>
                    <div
                        class="mt-4 flex justify-between border-t border-gray-800/40 pt-2 font-mono text-[10px] tracking-tight text-slate-400 uppercase"
                    >
                        <span>Total Betslips/Sold:</span>
                        <span class="font-bold text-white">{{
                            `${seller_data.performance.total_betslips}/${seller_data.performance.total_sold}`
                        }}</span>
                    </div>
                </div>

                <!-- ROI -->
                <div
                    class="flex flex-col justify-between rounded border border-[#232d42] bg-[#111622] p-4"
                >
                    <div>
                        <span
                            class="text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                            >TOTAL ROI</span
                        >
                        <div class="mt-2 flex items-baseline gap-2">
                            <span
                                class="font-mono text-3xl font-black tracking-tight text-white"
                                >{{ seller_data.performance.roi }}%</span
                            >
                            <span
                                :class="[
                                    'rounded px-1.5 py-0.5 text-[9px] font-black tracking-wide uppercase',
                                    seller_data.performance.roi_change >= 0
                                        ? 'bg-emerald-500/10 text-emerald-400'
                                        : 'bg-rose-500/10 text-rose-400',
                                ]"
                            >
                                {{
                                    seller_data.performance.roi_change >= 0
                                        ? '+'
                                        : ''
                                }}{{ seller_data.performance.roi_change }}%
                            </span>
                        </div>
                    </div>
                    <div
                        class="mt-4 flex justify-between border-t border-gray-800/40 pt-2 font-mono text-[10px] tracking-tight text-slate-400 uppercase"
                    >
                        <span>Avg Legs Per Slip:</span>
                        <span class="font-bold text-white">{{
                            Number(seller_data.performance.avg_legs).toFixed(2)
                        }}</span>
                    </div>
                </div>

                <!-- REVENUE -->
                <div
                    class="flex flex-col justify-between rounded border border-[#232d42] bg-[#111622] p-4"
                >
                    <div>
                        <span
                            class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                            >TOTAL REVENUE</span
                        >
                        <div class="mt-2 flex items-baseline gap-2">
                            <span
                                class="font-mono text-3xl font-black tracking-tight text-white"
                                >KES
                                {{
                                    Number(
                                        seller_data.financial.total_revenue,
                                    ).toLocaleString()
                                }}</span
                            >
                        </div>
                    </div>
                    <div
                        class="mt-4 flex justify-between border-t border-gray-800/40 pt-2 font-mono text-[10px] tracking-tight text-slate-400 uppercase"
                    >
                        <span>Net Earnings:</span>
                        <span class="font-bold text-amber-400"
                            >KES {{ seller_data.financial.net_earnings }}</span
                        >
                    </div>
                </div>

                <!-- FOLLOWERS -->
                <div
                    class="flex flex-col justify-between rounded border border-[#232d42] bg-[#111622] p-4"
                >
                    <div>
                        <span
                            class="text-[10px] font-black tracking-widest text-purple-400 uppercase"
                            >NETWORK FOLLOWER</span
                        >
                        <div class="mt-2 flex items-baseline gap-2">
                            <span
                                class="font-mono text-3xl font-black tracking-tight text-white"
                                >{{ seller_data.follower_stats.total }}</span
                            >
                            <span
                                class="rounded bg-purple-500/10 px-1.5 py-0.5 text-[9px] font-black tracking-wide text-purple-400 uppercase"
                            >
                                +{{ seller_data.follower_stats.new_this_week }}
                                THIS WEEK
                            </span>
                        </div>
                    </div>
                    <div
                        class="mt-4 flex justify-between border-t border-gray-800/40 pt-2 font-mono text-[10px] tracking-tight text-slate-400 uppercase"
                    >
                        <span>Engagement Rate:</span>
                        <span class="font-bold text-white"
                            >{{
                                seller_data.follower_stats.engagement_rate
                            }}%</span
                        >
                    </div>
                </div>
            </div>

            <!-- ═══════════════ MAIN GRID ═══════════════ -->
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                <!-- LEFT TRACK -->
                <div class="space-y-6 lg:col-span-8">
                    <!-- ACTIVE BETSLIPS -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                            >
                                ACTIVE BETSLIPS [{{
                                    seller_data.betslips.total_active
                                }}]</span
                            >
                            <span
                                class="font-mono text-[9px] text-slate-500 uppercase"
                                >[ UNRESOLVED ]</span
                            >
                        </div>
                        <div class="divide-y divide-gray-800/40">
                            <BetslipsTable
                                :betslips="seller_data.betslips.active"
                            />
                        </div>
                    </div>

                    <!-- ACTIVITY -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-slate-300 uppercase"
                                >HISTORICAL_LEDGER: RECENT SETTLEMENTS</span
                            >
                        </div>
                        <div
                            class="no-scrollbar max-h-[340px] space-y-2.5 overflow-y-auto p-4"
                        >
                            <div
                                v-for="(act, index) in seller_data.activity"
                                :key="index"
                                class="flex items-center justify-between gap-4 rounded border border-[#232d42]/70 bg-[#111622] p-3"
                            >
                                <div class="flex items-center gap-3">
                                    <span
                                        :class="[
                                            'flex h-5 w-5 items-center justify-center rounded-sm font-mono text-[9px] font-black select-none',
                                            act.result === 'won'
                                                ? 'bg-emerald-500 text-[#070b14]'
                                                : 'bg-rose-500 text-white',
                                        ]"
                                    >
                                        {{ act.result === 'won' ? 'W' : 'L' }}
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
                        </div>
                    </div>

                    <TransactionsTable
                        :transactions="seller_data.wallet.recent_transactions"
                    />

                    <FollowersTable
                        :followers="seller_data.follower_stats.recent"
                        :title="`recent followers`"
                    />
                </div>

                <!-- RIGHT TRACK -->
                <div class="space-y-6 lg:col-span-4">
                    <!-- WALLET (desktop) -->
                    <div
                        class="hidden overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40 md:block"
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
                                >[{{ seller_data.wallet.currency }}]</span
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
                                    {{ seller_data.wallet.currency }}
                                    {{
                                        Number(
                                            seller_data.wallet.balance,
                                        ).toLocaleString(undefined, {
                                            minimumFractionDigits: 2,
                                        })
                                    }}
                                </div>
                            </div>

                            <div class="flex flex-col gap-2 sm:flex-row">
                                <DepositPopover />
                                <WithdrawalPopover
                                    :currency="seller_data.wallet.currency"
                                    :available-balance="
                                        Number(seller_data.wallet.balance)
                                    "
                                    :disabled="
                                        Number(seller_data.wallet.balance) <= 0
                                    "
                                    class="flex-1"
                                />
                            </div>

                            <div
                                class="grid grid-cols-1 gap-2.5 sm:grid-cols-3 lg:grid-cols-1"
                            >
                                <div
                                    class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5"
                                >
                                    <div class="space-y-0.5">
                                        <span
                                            class="block text-[9px] font-bold text-slate-500 uppercase"
                                            >Pending Escrow</span
                                        >
                                        <span
                                            class="font-mono text-[10px] font-bold text-slate-400"
                                            >Locked</span
                                        >
                                    </div>
                                    <span
                                        class="font-mono text-xs font-black text-amber-400"
                                    >
                                        {{ seller_data.wallet.currency }}
                                        {{
                                            Number(
                                                seller_data.wallet
                                                    .pending_balance,
                                            ).toFixed(2)
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5"
                                >
                                    <div class="space-y-0.5">
                                        <span
                                            class="block text-[9px] font-bold text-slate-500 uppercase"
                                            >Total Deposited</span
                                        >
                                        <span
                                            class="font-mono text-[10px] font-bold text-slate-400"
                                            >Injections</span
                                        >
                                    </div>
                                    <span
                                        class="font-mono text-xs font-bold text-sky-400"
                                    >
                                        {{ seller_data.wallet.currency }}
                                        {{
                                            Number(
                                                seller_data.wallet
                                                    .total_deposited,
                                            ).toFixed(2)
                                        }}
                                    </span>
                                </div>
                                <div
                                    class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5"
                                >
                                    <div class="space-y-0.5">
                                        <span
                                            class="block text-[9px] font-bold text-slate-500 uppercase"
                                            >Total Withdrawn</span
                                        >
                                        <span
                                            class="font-mono text-[10px] font-bold text-slate-400"
                                            >Cleared</span
                                        >
                                    </div>
                                    <span
                                        class="font-mono text-xs font-bold text-rose-400"
                                    >
                                        {{ seller_data.wallet.currency }}
                                        {{
                                            Number(
                                                seller_data.wallet
                                                    .total_withdrawn,
                                            ).toFixed(2)
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- FEE TIER CARD -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                                >// FEE TIER PROGRESS</span
                            >
                        </div>
                        <div class="space-y-3 p-4">
                            <div
                                class="flex items-center justify-between font-mono text-[10px] font-black tracking-wider text-slate-400 uppercase"
                            >
                                <span>Current Tier</span>
                                <span class="text-white"
                                    >{{ seller_data.fee_tier.current_tier }} / 4
                                    ({{ feePercent }}%)</span
                                >
                            </div>
                            <div class="flex gap-1">
                                <div
                                    v-for="i in 4"
                                    :key="i"
                                    :class="[
                                        'h-1.5 flex-1 rounded-sm transition-all',
                                        i <= seller_data.fee_tier.current_tier
                                            ? 'bg-emerald-500'
                                            : 'bg-[#232d42]',
                                    ]"
                                ></div>
                            </div>
                            <div class="pt-2">
                                <div
                                    class="flex items-center justify-between font-mono text-[10px] text-slate-400"
                                >
                                    <span>Lifetime Sales</span>
                                    <span class="text-white"
                                        >{{ seller_data.fee_tier.total_sales }}</span
                                    >
                                </div>
                                <div
                                    v-if="
                                        seller_data.fee_tier.sales_until_next_tier
                                    "
                                    class="mt-2 rounded border border-sky-500/20 bg-sky-500/5 p-2 text-[10px] text-sky-400"
                                >
                                    <span class="font-black">{{
                                        seller_data.fee_tier.sales_until_next_tier
                                    }}</span>
                                    more sales to unlock
                                    <span class="font-black">{{
                                        (
                                            seller_data.fee_tier
                                                .next_tier_percentage * 100
                                        ).toFixed(0)
                                    }}%</span
                                    >
                                    fee rate
                                </div>
                                <div
                                    v-else
                                    class="mt-2 rounded border border-emerald-500/20 bg-emerald-500/5 p-2 text-center text-[10px] font-black text-emerald-400 uppercase"
                                >
                                    🏆 Highest tier unlocked
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- RECENT FORM -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-slate-200 uppercase"
                                >RECENT FORM STREAM</span
                            >
                        </div>
                        <div class="p-4">
                            <div class="flex flex-wrap gap-1.5">
                                <div
                                    v-for="(form, index) in seller_data
                                        .performance.recent_form"
                                    :key="index"
                                    :class="[
                                        'flex h-7 w-7 items-center justify-center rounded-sm font-mono text-xs font-black',
                                        form.status === 'W'
                                            ? 'border border-emerald-500/30 bg-emerald-500/10 text-emerald-400'
                                            : 'border border-rose-500/30 bg-rose-500/10 text-rose-400',
                                    ]"
                                    :title="`Settled Date: ${form.date}`"
                                >
                                    {{ form.status }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- WIN RATE BREAKDOWN -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                                >// TIME INTERVAL DECAY</span
                            >
                        </div>
                        <div class="space-y-3.5 p-4">
                            <div
                                v-for="(rate, label) in seller_data.performance
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

                    <!-- LEAGUE PERFORMANCE -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-purple-400 uppercase"
                                >// COMPETITION STRENGTH</span
                            >
                        </div>
                        <div class="space-y-3 p-4">
                            <div
                                v-for="league in seller_data.charts
                                    .league_performance"
                                :key="league.league"
                                class="flex items-center justify-between border-b border-gray-800/30 pb-2 last:border-0 last:pb-0"
                            >
                                <span
                                    class="text-[11px] font-bold text-slate-400 uppercase"
                                    >{{ league.league }}</span
                                >
                                <span
                                    class="rounded border border-purple-500/20 bg-purple-500/10 px-1.5 py-0.5 font-mono text-xs font-black text-purple-400"
                                    >{{ league.win_rate }}% WR</span
                                >
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
import FollowersTable from '@/components/FollowersTable.vue';
import TransactionsTable from '@/components/TransactionsTable.vue';
import DepositPopover from '@/components/DepositPopover.vue';
import WithdrawalPopover from '@/components/WithdrawalPopover.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import DashboardSwitcher from '@/components/DashboardSwitcher.vue';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const seller_data = page.props.seller_data;

const feePercent = computed(() =>
    (seller_data.fee_tier.current_percentage * 100).toFixed(0),
);

const tierRoman = computed(() => {
    const map = ['I', 'II', 'III', 'IV'];
    return map[seller_data.fee_tier.current_tier - 1] ?? 'I';
});

const tierBadgeClass = computed(() => {
    switch (seller_data.fee_tier.current_tier) {
        case 1:
            return 'bg-amber-500/20 text-amber-400 border border-amber-500/30';
        case 2:
            return 'bg-slate-400/20 text-slate-300 border border-slate-400/30';
        case 3:
            return 'bg-yellow-500/20 text-yellow-400 border border-yellow-500/30';
        default:
            return 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30';
    }
});
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