<template>
    <div
        class="flex min-h-screen flex-col bg-[#070b14] px-2 py-6 font-sans text-slate-200 selection:bg-emerald-500/30 selection:text-white lg:px-8 lg:py-10"
    >
        <div class="mx-auto w-full max-w-7xl space-y-6">
            <!-- ═══════════════ HEADER ═══════════════ -->
            <div
                class="flex w-full items-start justify-between gap-3 border-b border-gray-800/60 pb-6"
            >
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
                            <span>Tipster</span>
                        </div>
                        <h1
                            class="mt-0.5 truncate font-mono text-lg font-black tracking-tight text-white uppercase md:text-xl"
                        >
                            {{ seller_data.user.name }}
                        </h1>
                        <p
                            class="truncate text-[10px] font-medium text-slate-400"
                        >
                            Code: {{ seller_data.user.code }} • Member since
                            {{ seller_data.user.member_since }}
                        </p>
                    </div>
                </div>

                <div class="flex flex-shrink-0 items-center gap-2">
                    <DashboardSwitcher active="seller" />
                    <NotificationBell
                        :initial-unread-count="
                            seller_data.notifications.unread_count
                        "
                    />
                </div>
            </div>

            <!-- ═══════════════ PRIMARY CTAs ═══════════════ -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <!-- Create Betslip (spans 2 cols on desktop) -->
                <Link
                    href="/fixtures"
                    class="group relative flex items-center justify-between gap-4 overflow-hidden rounded border border-sky-500/40 bg-gradient-to-r from-sky-500/10 via-sky-500/5 to-transparent p-4 transition-all hover:border-sky-400 hover:from-sky-500/20 hover:via-sky-500/10 lg:col-span-2"
                >
                    <div
                        class="absolute inset-y-0 left-0 w-1 bg-sky-500 shadow-[0_0_15px_rgba(14,165,233,0.5)]"
                    ></div>
                    <div class="flex items-center gap-4 pl-2">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded border border-sky-500/40 bg-sky-500/10 text-sky-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M12 4.5v15m7.5-7.5h-15"
                                />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-xs font-black tracking-widest text-sky-400 uppercase"
                            >
                                Create a Betslip
                            </p>
                            <p class="mt-0.5 text-[11px] text-slate-400">
                                Browse fixtures and build your next tip
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex flex-shrink-0 items-center gap-2 pr-2 font-mono text-[10px] font-black tracking-widest text-sky-400 uppercase transition-transform group-hover:translate-x-0.5"
                    >
                        <span class="hidden sm:inline">Start</span>
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="3"
                            stroke="currentColor"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"
                            />
                        </svg>
                    </div>
                </Link>

                <!-- Create Contest (1 col) -->
                <Link
                    href="/contests/create"
                    class="group relative flex items-center justify-between gap-4 overflow-hidden rounded border border-purple-500/40 bg-gradient-to-r from-purple-500/10 via-purple-500/5 to-transparent p-4 transition-all hover:border-purple-400 hover:from-purple-500/20 hover:via-purple-500/10"
                >
                    <div
                        class="absolute inset-y-0 left-0 w-1 bg-purple-500 shadow-[0_0_15px_rgba(168,85,247,0.5)]"
                    ></div>
                    <div class="flex items-center gap-4 pl-2">
                        <div
                            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded border border-purple-500/40 bg-purple-500/10 text-purple-400"
                        >
                            <svg
                                xmlns="http://www.w3.org/2000/svg"
                                fill="none"
                                viewBox="0 0 24 24"
                                stroke-width="2.5"
                                stroke="currentColor"
                                class="h-5 w-5"
                            >
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0"
                                />
                            </svg>
                        </div>
                        <div class="min-w-0">
                            <p
                                class="text-xs font-black tracking-widest text-purple-400 uppercase"
                            >
                                Host Contest
                            </p>
                            <p class="mt-0.5 text-[11px] text-slate-400">
                                Compete with your followers and friends
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex flex-shrink-0 items-center gap-2 pr-2 font-mono text-[10px] font-black tracking-widest text-purple-400 uppercase transition-transform group-hover:translate-x-0.5"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="3"
                            stroke="currentColor"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3"
                            />
                        </svg>
                    </div>
                </Link>
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
                                <span
                                    >FEE TIER
                                    {{ seller_data.fee_tier.current_tier }} /
                                    4</span
                                >
                            </div>
                            <div
                                class="mt-0.5 font-mono font-black tracking-tight text-white"
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
                            <span class="font-mono font-black text-white">{{
                                seller_data.fee_tier.sales_until_next_tier
                            }}</span>
                            <span class="text-[10px] text-slate-400 uppercase"
                                >more sales →</span
                            >
                            <span
                                class="font-mono text-sm font-bold text-emerald-400"
                                >{{
                                    (
                                        seller_data.fee_tier
                                            .next_tier_percentage * 100
                                    ).toFixed(0)
                                }}%</span
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

            <!-- ═══════════════ TAB STRIP ═══════════════ -->
            <BuyerTabs v-model="activeTab" :tabs="tabs" />

            <!-- ═══════════════ OVERVIEW TAB ═══════════════ -->
            <template v-if="activeTab === 'overview'">
                <!-- Wallet (single responsive block) -->
                <Panel title="WALLET" accent="emerald">
                    <template #actions>
                        <span
                            class="font-mono text-[9px] font-bold text-slate-500 uppercase"
                        >
                            [{{ seller_data.wallet.currency }}]
                        </span>
                    </template>

                    <div class="space-y-4 p-4">
                        <div
                            class="relative overflow-hidden rounded border border-[#232d42] bg-[#0a101f] p-4 text-center"
                        >
                            <div
                                class="absolute inset-x-0 bottom-0 h-[2px] bg-emerald-500/30"
                            ></div>
                            <span
                                class="block text-[9px] font-black tracking-wider text-slate-500 uppercase"
                            >
                                Available Balance
                            </span>
                            <div
                                class="my-1 font-mono text-3xl font-black tracking-tight text-white"
                            >
                                <Money
                                    :value="seller_data.wallet.balance"
                                    :currency="seller_data.wallet.currency"
                                />
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <DepositPopover
                                :currency="'KES'"
                                :initial_amount="100"
                                :initial_phone="
                                    seller_data?.user?.phone ?? ''
                                "
                            />
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
                            class="grid grid-cols-1 gap-2.5 sm:grid-cols-3"
                        >
                            <div
                                class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5"
                            >
                                <div class="space-y-0.5">
                                    <span
                                        class="block text-[9px] font-bold text-slate-500 uppercase"
                                        >At Stake</span
                                    >
                                    <span
                                        class="font-mono text-[10px] font-bold text-slate-400"
                                        >If all win</span
                                    >
                                </div>
                                <span
                                    class="font-mono text-xs font-black text-amber-400"
                                >
                                    <Money
                                        :value="
                                            seller_data.wallet.gross_at_stake
                                        "
                                        :currency="
                                            seller_data.wallet.currency
                                        "
                                    />
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
                                        >Deposits</span
                                    >
                                </div>
                                <span
                                    class="font-mono text-xs font-bold text-sky-400"
                                >
                                    <Money
                                        :value="
                                            seller_data.wallet.total_deposited
                                        "
                                        :currency="
                                            seller_data.wallet.currency
                                        "
                                    />
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
                                        >Withdrawals</span
                                    >
                                </div>
                                <span
                                    class="font-mono text-xs font-bold text-rose-400"
                                >
                                    <Money
                                        :value="
                                            seller_data.wallet.total_withdrawn
                                        "
                                        :currency="
                                            seller_data.wallet.currency
                                        "
                                    />
                                </span>
                            </div>
                        </div>
                    </div>
                </Panel>

                <!-- Two metric cards: Performance + Earnings -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Panel title="PERFORMANCE" accent="sky">
                        <div class="grid grid-cols-2 gap-4 p-4">
                            <div>
                                <span
                                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                                    >Win Rate</span
                                >
                                <div
                                    class="mt-1 flex items-baseline gap-2"
                                >
                                    <span
                                        class="font-mono text-3xl font-black tracking-tight text-white"
                                        >{{
                                            seller_data.performance.win_rate
                                        }}%</span
                                    >
                                    <span
                                        :class="[
                                            'rounded px-1.5 py-0.5 text-[9px] font-black tracking-wide uppercase',
                                            seller_data.performance
                                                .win_rate_change >= 0
                                                ? 'bg-emerald-500/10 text-emerald-400'
                                                : 'bg-rose-500/10 text-rose-400',
                                        ]"
                                    >
                                        {{
                                            seller_data.performance
                                                .win_rate_change >= 0
                                                ? '+'
                                                : ''
                                        }}{{
                                            seller_data.performance
                                                .win_rate_change
                                        }}%
                                    </span>
                                </div>
                            </div>
                            <div>
                                <span
                                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                                    >ROI</span
                                >
                                <div
                                    class="mt-1 flex items-baseline gap-2"
                                >
                                    <span
                                        class="font-mono text-3xl font-black tracking-tight text-white"
                                        >{{ seller_data.performance.roi }}%</span
                                    >
                                    <span
                                        :class="[
                                            'rounded px-1.5 py-0.5 text-[9px] font-black tracking-wide uppercase',
                                            seller_data.performance
                                                .roi_change >= 0
                                                ? 'bg-emerald-500/10 text-emerald-400'
                                                : 'bg-rose-500/10 text-rose-400',
                                        ]"
                                    >
                                        {{
                                            seller_data.performance.roi_change >=
                                            0
                                                ? '+'
                                                : ''
                                        }}{{
                                            seller_data.performance.roi_change
                                        }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div
                            class="grid grid-cols-3 divide-x divide-[#232d42]/60 border-t border-[#232d42]/60"
                        >
                            <div class="p-3 text-center">
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                    >Betslips</span
                                >
                                <span
                                    class="mt-1 block font-mono text-sm font-black text-white"
                                    >{{
                                        seller_data.performance.total_betslips
                                    }}</span
                                >
                            </div>
                            <div class="p-3 text-center">
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                    >Sold</span
                                >
                                <span
                                    class="mt-1 block font-mono text-sm font-black text-emerald-400"
                                    >{{
                                        seller_data.performance.total_sold
                                    }}</span
                                >
                            </div>
                            <div class="p-3 text-center">
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                    >Avg Legs</span
                                >
                                <span
                                    class="mt-1 block font-mono text-sm font-black text-white"
                                    >{{
                                        Number(
                                            seller_data.performance.avg_legs,
                                        ).toFixed(1)
                                    }}</span
                                >
                            </div>
                        </div>
                    </Panel>

                    <Panel title="EARNINGS" accent="amber">
                        <div class="grid grid-cols-2 gap-4 p-4">
                            <div>
                                <span
                                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                                    >Total Revenue</span
                                >
                                <div
                                    class="mt-1 font-mono text-3xl font-black tracking-tight text-white"
                                >
                                    <Money
                                        :value="
                                            seller_data.financial
                                                .total_revenue
                                        "
                                        :currency="'KES'"
                                    />
                                </div>
                            </div>
                            <div>
                                <span
                                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                                    >Net Earnings</span
                                >
                                <div
                                    class="mt-1 font-mono text-3xl font-black tracking-tight text-amber-400"
                                >
                                    <Money
                                        :value="
                                            seller_data.financial
                                                .net_earnings
                                        "
                                        :currency="'KES'"
                                    />
                                </div>
                            </div>
                        </div>
                        <div
                            class="grid grid-cols-2 divide-x divide-[#232d42]/60 border-t border-[#232d42]/60"
                        >
                            <div class="p-3 text-center">
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                    >Platform Fees</span
                                >
                                <span
                                    class="mt-1 block font-mono text-sm font-black text-rose-400"
                                >
                                    <Money
                                        :value="
                                            seller_data.financial
                                                .platform_fees
                                        "
                                        :currency="'KES'"
                                    />
                                </span>
                            </div>
                            <div class="p-3 text-center">
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                    >Net at Stake</span
                                >
                                <span
                                    class="mt-1 block font-mono text-sm font-black text-sky-400"
                                >
                                    <Money
                                        :value="
                                            seller_data.financial.net_at_stake
                                        "
                                        :currency="'KES'"
                                    />
                                </span>
                            </div>
                        </div>
                    </Panel>
                </div>

                <!-- Split: Active betslips (left) + Followers summary (right) -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <Panel
                            :title="`Active Betslips [${seller_data.betslips.total_active}]`"
                            accent="sky"
                        >
                            <template #actions>
                                <span
                                    v-if="
                                        seller_data.betslips.total_watchers > 0
                                    "
                                    class="flex items-center gap-1.5 font-mono text-[9px] font-bold tracking-widest text-amber-400 uppercase"
                                >
                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="2.5"
                                        stroke="currentColor"
                                        class="h-3 w-3"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"
                                        />
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"
                                        />
                                    </svg>
                                    {{ seller_data.betslips.total_watchers }}
                                    watching
                                </span>
                                <span
                                    v-else
                                    class="font-mono text-[9px] text-slate-500 uppercase"
                                    >Active</span
                                >
                            </template>
                            <div class="divide-y divide-gray-800/40">
                                <BetslipsTable
                                    v-if="
                                        seller_data.betslips.active.length > 0
                                    "
                                    :betslips="seller_data.betslips.active"
                                />
                                <div
                                    v-else
                                    class="p-6 text-center font-mono text-xs text-slate-500"
                                >
                                    No active betslips.
                                    <Link
                                        href="/fixtures"
                                        class="ml-1 font-bold text-sky-400 hover:text-sky-300"
                                    >
                                        Create one →
                                    </Link>
                                </div>
                            </div>
                        </Panel>
                    </div>

                    <Panel title="FOLLOWERS" accent="purple">
                        <div class="grid grid-cols-2 divide-x divide-[#232d42]/60">
                            <div class="p-3 text-center">
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                    >Total</span
                                >
                                <span
                                    class="mt-1 block font-mono text-xl font-black text-white"
                                    >{{
                                        seller_data.follower_stats.total
                                    }}</span
                                >
                            </div>
                            <div class="p-3 text-center">
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                    >This Week</span
                                >
                                <span
                                    class="mt-1 block font-mono text-xl font-black text-purple-400"
                                    >+{{
                                        seller_data.follower_stats
                                            .new_this_week
                                    }}</span
                                >
                            </div>
                        </div>
                        <div
                            class="border-t border-[#232d42]/60 p-3 text-center"
                        >
                            <span
                                class="text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                >Engagement Rate</span
                            >
                            <span
                                class="ml-2 font-mono text-sm font-black text-white"
                                >{{
                                    seller_data.follower_stats.engagement_rate
                                }}%</span
                            >
                        </div>
                    </Panel>
                </div>

                <!-- Referral -->
                <ReferralCard
                    v-if="seller_data.referral_card"
                    :card="seller_data.referral_card"
                />
            </template>

            <!-- ═══════════════ BETSLIPS TAB ═══════════════ -->
            <template v-else-if="activeTab === 'betslips'">
                <Panel
                    :title="`Active Betslips [${seller_data.betslips.total_active}]`"
                    accent="sky"
                >
                    <template #actions>
                        <Link
                            href="/fixtures"
                            class="font-mono text-[9px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300"
                        >
                            + Create new
                        </Link>
                    </template>
                    <div class="divide-y divide-gray-800/40">
                        <BetslipsTable
                            v-if="seller_data.betslips.active.length > 0"
                            :betslips="seller_data.betslips.active"
                        />
                        <div
                            v-else
                            class="p-8 text-center font-mono text-xs text-slate-500"
                        >
                            No active betslips.
                            <Link
                                href="/fixtures"
                                class="ml-1 font-bold text-sky-400 hover:text-sky-300"
                            >
                                Create one →
                            </Link>
                        </div>
                    </div>
                </Panel>

                <Panel
                    :title="`Settlements [${seller_data.settlements.length}]`"
                    accent="emerald"
                >
                    <template #actions>
                        <span
                            class="font-mono text-[9px] text-slate-500 uppercase"
                            >Payout history</span
                        >
                    </template>

                    <div class="divide-y divide-gray-800/40">
                        <div
                            v-for="s in seller_data.settlements"
                            :key="s.id"
                            class="p-3 transition-colors hover:bg-[#111a30]/40"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div
                                    class="flex min-w-0 items-center gap-3"
                                >
                                    <span
                                        :class="[
                                            'flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-sm font-mono text-[9px] font-black select-none',
                                            s.outcome === 'won'
                                                ? 'bg-emerald-500 text-[#070b14]'
                                                : s.outcome === 'voided'
                                                  ? 'bg-slate-500 text-[#070b14]'
                                                  : 'bg-rose-500 text-white',
                                        ]"
                                    >
                                        {{
                                            s.outcome === 'won'
                                                ? 'W'
                                                : s.outcome === 'voided'
                                                  ? 'V'
                                                  : 'L'
                                        }}
                                    </span>
                                    <div class="min-w-0">
                                        <div
                                            class="flex items-center gap-2"
                                        >
                                            <span
                                                class="truncate font-mono text-[11px] font-bold text-sky-400"
                                            >
                                                {{ s.betslip_code }}
                                            </span>
                                        </div>
                                        <p
                                            class="mt-0.5 truncate text-[10px] text-slate-500"
                                        >
                                            from {{ s.buyer_name }} ·
                                            {{ s.settled_ago }}
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <p
                                        class="font-mono text-xs font-black"
                                        :class="
                                            s.outcome === 'won'
                                                ? 'text-emerald-400'
                                                : 'text-slate-500'
                                        "
                                    >
                                        <Money
                                            :value="s.net"
                                            :currency="'KES'"
                                            :signed="s.outcome === 'won'"
                                        />
                                    </p>
                                    <p
                                        class="text-[9px] text-slate-500 uppercase"
                                    >
                                        {{
                                            s.outcome === 'won'
                                                ? 'Net earned'
                                                : s.outcome === 'voided'
                                                  ? 'Voided'
                                                  : 'Refunded'
                                        }}
                                    </p>
                                </div>
                            </div>

                            <div
                                v-if="s.outcome === 'won'"
                                class="mt-2 flex flex-wrap items-center gap-3 border-t border-gray-800/40 pt-2 font-mono text-[9px] tracking-wide text-slate-500 uppercase"
                            >
                                <span>
                                    Gross
                                    <span class="text-slate-300">
                                        <Money
                                            :value="s.gross"
                                            :currency="'KES'"
                                        />
                                    </span>
                                </span>
                                <span class="text-slate-700">·</span>
                                <span>
                                    Fee
                                    <span class="text-rose-400">
                                        <Money
                                            :value="s.fee"
                                            :currency="'KES'"
                                        />
                                    </span>
                                </span>
                                <span class="text-slate-700">·</span>
                                <span>
                                    Net
                                    <span class="font-black text-emerald-400">
                                        <Money
                                            :value="s.net"
                                            :currency="'KES'"
                                        />
                                    </span>
                                </span>
                            </div>
                        </div>

                        <div
                            v-if="seller_data.settlements.length === 0"
                            class="p-6 text-center font-mono text-xs text-slate-500"
                        >
                            No settlements yet.
                        </div>
                    </div>
                </Panel>

                <ContestsCard :contests="seller_data.contests ?? []" />
            </template>

            <!-- ═══════════════ FOLLOWERS TAB ═══════════════ -->
            <template v-else-if="activeTab === 'followers'">
                <Panel title="FOLLOWER OVERVIEW" accent="purple">
                    <div class="grid grid-cols-3 divide-x divide-[#232d42]/60">
                        <div class="p-4 text-center">
                            <span
                                class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                >Total</span
                            >
                            <span
                                class="mt-1 block font-mono text-2xl font-black text-white"
                                >{{ seller_data.follower_stats.total }}</span
                            >
                        </div>
                        <div class="p-4 text-center">
                            <span
                                class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                >New This Week</span
                            >
                            <span
                                class="mt-1 block font-mono text-2xl font-black text-purple-400"
                                >+{{
                                    seller_data.follower_stats.new_this_week
                                }}</span
                            >
                        </div>
                        <div class="p-4 text-center">
                            <span
                                class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                >Engagement</span
                            >
                            <span
                                class="mt-1 block font-mono text-2xl font-black text-white"
                                >{{
                                    seller_data.follower_stats.engagement_rate
                                }}%</span
                            >
                        </div>
                    </div>
                </Panel>

                <FollowersTable
                    :followers="seller_data.follower_stats.recent"
                    :title="`Recent Followers`"
                />
            </template>

            <!-- ═══════════════ WALLET TAB ═══════════════ -->
            <template v-else-if="activeTab === 'wallet'">
                <Panel title="ACCOUNT: WALLET DETAILS" accent="amber">
                    <template #actions>
                        <span
                            class="font-mono text-[9px] font-bold text-slate-500 uppercase"
                        >
                            [{{ seller_data.wallet.currency }}]
                        </span>
                    </template>

                    <div class="space-y-4 p-4">
                        <div
                            class="relative overflow-hidden rounded border border-[#232d42] bg-[#0a101f] p-4 text-center"
                        >
                            <div
                                class="absolute inset-x-0 bottom-0 h-[2px] bg-emerald-500/30"
                            ></div>
                            <span
                                class="block text-[9px] font-black tracking-wider text-slate-500 uppercase"
                            >
                                Available Balance
                            </span>
                            <div
                                class="my-1 font-mono text-3xl font-black tracking-tight text-white"
                            >
                                <Money
                                    :value="seller_data.wallet.balance"
                                    :currency="seller_data.wallet.currency"
                                />
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <DepositPopover
                                :currency="'KES'"
                                :initial_amount="100"
                                :initial_phone="
                                    seller_data?.user?.phone ?? ''
                                "
                            />
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
                            class="grid grid-cols-1 gap-2.5 sm:grid-cols-3"
                        >
                            <div
                                class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5"
                            >
                                <div class="space-y-0.5">
                                    <span
                                        class="block text-[9px] font-bold text-slate-500 uppercase"
                                        >Held Funds</span
                                    >
                                    <span
                                        class="font-mono text-[10px] font-bold text-slate-400"
                                        >Pending</span
                                    >
                                </div>
                                <span
                                    class="font-mono text-xs font-black text-amber-400"
                                >
                                    <Money
                                        :value="
                                            seller_data.wallet.gross_at_stake
                                        "
                                        :currency="
                                            seller_data.wallet.currency
                                        "
                                    />
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
                                        >Deposits</span
                                    >
                                </div>
                                <span
                                    class="font-mono text-xs font-bold text-sky-400"
                                >
                                    <Money
                                        :value="
                                            seller_data.wallet.total_deposited
                                        "
                                        :currency="
                                            seller_data.wallet.currency
                                        "
                                    />
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
                                        >Withdrawals</span
                                    >
                                </div>
                                <span
                                    class="font-mono text-xs font-bold text-rose-400"
                                >
                                    <Money
                                        :value="
                                            seller_data.wallet.total_withdrawn
                                        "
                                        :currency="
                                            seller_data.wallet.currency
                                        "
                                    />
                                </span>
                            </div>
                        </div>
                    </div>
                </Panel>

                <TransactionsTable
                    :transactions="seller_data.wallet.recent_transactions"
                />
            </template>

            <!-- ═══════════════ ANALYTICS TAB ═══════════════ -->
            <template v-else-if="activeTab === 'analytics'">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Panel title="WIN RATE OVER TIME" accent="sky">
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
                    </Panel>

                    <Panel title="LEAGUE PERFORMANCE" accent="purple">
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
                            <div
                                v-if="
                                    seller_data.charts.league_performance
                                        .length === 0
                                "
                                class="py-2 text-center text-[10px] text-slate-500 uppercase"
                            >
                                No league data
                            </div>
                        </div>
                    </Panel>

                    <Panel title="RECENT FORM" accent="slate">
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
                                    :title="`Settled: ${form.date}`"
                                >
                                    {{ form.status }}
                                </div>
                            </div>
                            <p
                                v-if="
                                    seller_data.performance.recent_form
                                        .length === 0
                                "
                                class="py-2 text-center font-mono text-[10px] text-slate-500 uppercase"
                            >
                                No form yet
                            </p>
                        </div>
                    </Panel>

                    <Panel title="RECENT ACTIVITY" accent="slate">
                        <div
                            class="no-scrollbar max-h-[420px] space-y-2.5 overflow-y-auto p-4"
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
                                v-if="seller_data.activity.length === 0"
                                class="p-4 text-center font-mono text-xs text-slate-500"
                            >
                                No recent activity.
                            </div>
                        </div>
                    </Panel>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup>
import { computed, ref, watch, onMounted, onUnmounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

import Panel from '@/components/Panel.vue';
import Money from '@/components/Money.vue';
import BuyerTabs from '@/components/BuyerTabs.vue';
import BetslipsTable from '@/components/BetslipsTable.vue';
import FollowersTable from '@/components/FollowersTable.vue';
import TransactionsTable from '@/components/TransactionsTable.vue';
import DepositPopover from '@/components/DepositPopover.vue';
import WithdrawalPopover from '@/components/WithdrawalPopover.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import ReferralCard from '@/components/ReferralCard.vue';
import DashboardSwitcher from '@/components/DashboardSwitcher.vue';
import ContestsCard from '@/components/ContestsCard.vue';

const page = usePage();
const seller_data = page.props.seller_data;

// ─── Tabs ──────────────────────────────────────────────────────────
const TAB_KEYS = ['overview', 'betslips', 'followers', 'wallet', 'analytics'];

const tabs = computed(() => [
    { key: 'overview', label: 'Overview', accent: 'emerald' },
    {
        key: 'betslips',
        label: 'Betslips',
        accent: 'sky',
        badge: seller_data.betslips.total_active || null,
    },
    {
        key: 'followers',
        label: 'Followers',
        accent: 'purple',
        badge: seller_data.follower_stats.total || null,
    },
    { key: 'wallet', label: 'Wallet', accent: 'amber' },
    { key: 'analytics', label: 'Analytics', accent: 'slate' },
]);

const readHash = () => {
    const hash = (window.location.hash || '').replace('#', '');
    return TAB_KEYS.includes(hash) ? hash : 'overview';
};

const activeTab = ref(readHash());

const onHashChange = () => {
    activeTab.value = readHash();
};

onMounted(() => {
    window.addEventListener('hashchange', onHashChange);
});

onUnmounted(() => {
    window.removeEventListener('hashchange', onHashChange);
});

watch(activeTab, (val) => {
    if (window.location.hash !== `#${val}`) {
        history.replaceState(null, '', `#${val}`);
    }
});

// ─── Fee tier helpers ──────────────────────────────────────────────
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

// ─── Tone badge helper ─────────────────────────────────────────────
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