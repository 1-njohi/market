<template>
    <div
        class="flex min-h-screen flex-col bg-[#070b14] px-4 py-6 font-sans text-slate-200 selection:bg-sky-500/30 selection:text-white lg:px-8 lg:py-10"
    >
        <div class="mx-auto w-full max-w-7xl space-y-6">
            <!-- HEADER ARCHITECTURE: USER CONSOLE PROFILE -->
            <div
                class="flex flex-col gap-4 border-b border-gray-800/60 pb-6 md:flex-row md:items-center md:justify-between"
            >
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <img
                            :src="seller_data.user.avatar"
                            :alt="seller_data.user.name"
                            class="h-14 w-14 rounded border border-sky-500/30 bg-[#111622]"
                        />
                        <span
                            v-if="seller_data.user.is_verified"
                            class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-sky-500 text-[8px] font-black text-[#070b14]"
                            title="VERIFIED SQUAD"
                            >✓</span
                        >
                    </div>
                    <div>
                        <div
                            class="flex items-center gap-2 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                        >
                            <span>[ OPERATOR // CORE_NODE ]</span>
                            <span
                                v-if="!seller_data.user.is_verified"
                                class="py-0.2 rounded border border-amber-500/30 px-1 text-[8px] font-bold text-amber-500"
                                >[ UNVERIFIED ]</span
                            >
                        </div>
                        <h1
                            class="mt-0.5 font-mono text-xl font-black tracking-tight text-white uppercase"
                        >
                            {{ seller_data.user.name }}
                        </h1>
                        <p class="text-[10px] font-medium text-slate-400">
                            SYS_ID: {{ seller_data.user.email }} • Joined
                            {{ seller_data.user.member_since }}
                        </p>
                    </div>
                </div>

                <!-- NOTIFICATION TICKER HUB -->
                <div class="flex items-center gap-3 self-start md:self-center">
                    <div
                        class="group relative cursor-pointer rounded border border-[#232d42] bg-[#111622] p-2 transition-colors hover:border-sky-500/50"
                    >
                        <span
                            class="flex items-center gap-1.5 text-[10px] font-black tracking-wider text-slate-400 uppercase"
                        >
                            Notifications
                            <span
                                class="py-0.2 animate-pulse rounded-full bg-rose-500 px-1.5 font-mono text-[9px] font-black text-white"
                            >
                                {{ seller_data.notifications.unread_count }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- INSIGHTS BANNER TELEMETRY -->
            <!-- <div
                v-for="(insight, index) in seller_data.insights"
                :key="index"
                class="flex items-center gap-2.5 rounded border border-emerald-500/20 bg-emerald-500/5 px-4 py-3 text-[11px] font-bold tracking-wide text-emerald-400 uppercase"
            >
                <span>SYSTEM_INSIGHT: {{ insight }}</span>
            </div> -->

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
                        <!-- <button
                            type="button"
                            @click="triggerDeposit"
                            :disabled="isProcessing"
                            class="flex flex-1 cursor-pointer items-center justify-center rounded border border-sky-500/40 bg-transparent py-2.5 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-all duration-200 hover:border-sky-500 hover:bg-sky-500/10 disabled:pointer-events-none disabled:opacity-40"
                        >
                            <span>DEPOSIT CAPITAL (+)</span>
                        </button> -->

                        <DepositPopover />
                        <button
                            type="button"
                            @click="triggerWithdrawal"
                            :disabled="isProcessing"
                            class="flex flex-1 items-center justify-center rounded border border-emerald-500 py-2.5 text-[10px] font-black tracking-widest uppercase"
                            :class="
                                Number(seller_data.wallet.balance) > 0
                                    ? 'cursor-pointer bg-emerald-500 text-[#070b14] shadow-[0_0_15px_rgba(16,185,129,0.1)] transition-all duration-200 hover:bg-transparent hover:text-emerald-400 hover:shadow-[0_0_25px_rgba(16,185,129,0.2)] disabled:pointer-events-none disabled:opacity-40'
                                    : 'text-gray-500'
                            "
                        >
                            <span v-if="isProcessing">// PROCESSING...</span>
                            <span v-else>WITHDRAW FUNDS (→)</span>
                        </button>
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
                                {{ seller_data.wallet.currency }}
                                {{
                                    Number(
                                        seller_data.wallet.pending_balance,
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
                                {{ seller_data.wallet.currency }}
                                {{
                                    Number(
                                        seller_data.wallet.total_deposited,
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

                <!-- ROI METRIC -->
                <div
                    class="flex flex-col justify-between rounded border border-[#232d42] bg-[#111622] p-4 shadow-[0_0_15px_rgba(16,185,129,0.02)]"
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
                            <!-- <span
                                class="rounded bg-slate-800 px-1.5 py-0.5 text-[9px] font-black tracking-wide text-slate-400 uppercase"
                                >STABLE</span
                            > -->

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

                <!-- ESCROW ESCAPEMENT REVENUE -->
                <div
                    class="flex flex-col justify-between rounded border border-[#232d42] bg-[#111622] p-4 shadow-[0_0_15px_rgba(245,158,11,0.02)]"
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
                                    seller_data.financial.total_revenue.toLocaleString()
                                }}</span
                            >
                            <span
                                class="rounded bg-emerald-500/10 px-1.5 py-0.5 text-[9px] font-black tracking-wide text-emerald-400 uppercase"
                            >
                                +{{
                                    seller_data.performance.total_revenue_change
                                }}
                            </span>
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

                <!-- FOLLOWER VOLUME STREAM -->
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

            <!-- GRID ROW 2: MANAGEMENT SPLITS AND STREAM DATA -->
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                <!-- LEFT TRACK: LIVE ACTIVE BETSLIPS & ACTIVITY -->
                <div class="space-y-6 lg:col-span-8">
                    <!-- ACTIVE CONTRACT BETSLIPS -->
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
                                >[ UNRESOLVED BETSLIPS ]</span
                            >
                        </div>

                        <div class="divide-y divide-gray-800/40">
                            <div
                                v-if="
                                    seller_data.betslips.active?.items
                                        ?.length === 0
                                "
                                class="p-6 text-center font-mono text-xs text-slate-500 uppercase"
                            >
                                No active betslips currently streaming.
                            </div>
                            <BetslipsTable
                                :betslips="seller_data.betslips.active"
                            />
                        </div>
                    </div>

                    <!-- RECENT TRANSACTION &  STATUS ACTIVITY AUDIT LOG -->
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
                                class="flex items-center justify-between gap-4 rounded border border-[#232d42]/70 bg-[#111622] p-3 transition-colors hover:border-slate-700"
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
            <!-- <div class="mt-6"> -->
                <TransactionsTable :transactions="seller_data.wallet.recent_transactions" />
            <!-- </div> -->
                    <!-- </div> -->

                    <FollowersTable
                        :followers="seller_data.follower_stats.recent"
                        :title="`recent followers`"
                    />
                </div>

                <!-- RIGHT TRACK: STATISTICAL DISTRIBUTION AND PROFILE BLOCKS -->
                <div class="space-y-6 lg:col-span-4">
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
                                <!-- <button
                                    type="button"
                                    @click="triggerDeposit"
                                    :disabled="isProcessing"
                                    class="flex flex-1 cursor-pointer items-center justify-center rounded border border-sky-500/40 bg-transparent py-2.5 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-all duration-200 hover:border-sky-500 hover:bg-sky-500/10 disabled:pointer-events-none disabled:opacity-40"
                                >
                                    <span>DEPOSIT CAPITAL (+)</span>
                                </button> -->
                                <DepositPopover />

                                <button
                                    type="button"
                                    @click="triggerWithdrawal"
                                    :disabled="isProcessing"
                                    class="flex flex-1 items-center justify-center rounded border border-emerald-500 py-2.5 text-[10px] font-black tracking-widest uppercase"
                                    :class="
                                        Number(seller_data.wallet.balance) > 0
                                            ? 'cursor-pointer bg-emerald-500 text-[#070b14] shadow-[0_0_15px_rgba(16,185,129,0.1)] transition-all duration-200 hover:bg-transparent hover:text-emerald-400 hover:shadow-[0_0_25px_rgba(16,185,129,0.2)] disabled:pointer-events-none disabled:opacity-40'
                                            : 'text-gray-500'
                                    "
                                >
                                    <span v-if="isProcessing"
                                        >// PROCESSING...</span
                                    >
                                    <span v-else>WITHDRAW</span>
                                </button>
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

                    <!-- RECENT FORM RADAR STRIP -->
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
                                        'flex h-7 w-7 cursor-help flex-col items-center justify-center rounded-sm font-mono text-xs font-black transition-transform select-none hover:scale-105',
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

                    <!-- EPOCH WIN RATE BREAKDOWN COMPASS -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                                >// TIME INTERVAL DECAY WIN RATES</span
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

                    <!-- MARKET TARGETING DISTRIBUTION RATIOS -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                                >// MARKET CLASSIFICATION FRACTIONS</span
                            >
                        </div>
                        <div class="space-y-3 p-4">
                            <div
                                v-for="market in seller_data.charts
                                    .market_distribution"
                                :key="market.market"
                                class="space-y-1"
                            >
                                <div
                                    class="flex items-center justify-between text-[10px] font-bold text-slate-400 uppercase"
                                >
                                    <span>{{ market.market }}</span>
                                    <span
                                        class="font-mono font-bold text-amber-400"
                                        >{{ market.percentage }}%</span
                                    >
                                </div>
                                <div
                                    class="h-1 w-full overflow-hidden rounded bg-[#111622]"
                                >
                                    <div
                                        class="h-full bg-amber-500"
                                        :style="{
                                            width: `${market.percentage}%`,
                                        }"
                                    ></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LEAGUE PERFORMANCE STANDINGS -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-purple-400 uppercase"
                                >// COMPETITION DOMAIN STRENGTH</span
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
import { usePage } from '@inertiajs/vue3';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import DepositPopover from '@/components/DepositPopover.vue';
import TransactionsTable from '@/components/TransactionsTable.vue';

// Load payload array from inertia wrapper props verbatim
const page = usePage();
const seller_data = page.props.seller_data;
async function triggerDeposit() {
    // prompt("Enter amount you want to deposit");
    alert("ddsds")
    try {
        const response = await axios.post(
            '/deposit/initiate',
            { amount: 1000 },
            { headers: { Accept: 'application/json' } },
        );

        const data = response.data;

        if (data.success) {
            // ✅ Native browser redirect – NO CORS issues
            window.location.href = data.authorization_url;
        } else {
            alert(data.message || 'Failed to initiate deposit');
        }
    } catch (error) {
        console.error('Deposit error:', error);
        alert('An unexpected error occurred. Please try again.');
    }
}
</script>

<style scoped>
/* Standard webkit visual scroll track suppression */
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>
