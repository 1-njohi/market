<template>
    <div
        class="flex min-h-screen flex-col bg-[#070b14] px-2 py-6 font-sans text-slate-200 selection:bg-sky-500/30 selection:text-white lg:px-8 lg:py-10"
    >
        <div class="mx-auto w-full max-w-7xl space-y-6">
            <!-- ═══════════════ HEADER ═══════════════ -->
            <div
                class="flex w-full items-start justify-between gap-3 border-b border-gray-800/60 pb-6"
            >
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

                <div class="flex flex-shrink-0 items-center gap-2">
                    <DashboardSwitcher active="buyer" />
                    <NotificationBell
                        :initial-unread-count="
                            buyer_data.notifications.unread_count
                        "
                    />
                </div>
            </div>

        <!-- ═══════════════ PRIMARY CTAs ═══════════════ -->
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <!-- Marketplace CTA (spans 2 cols) -->
            <Link
                href="/marketplace"
                class="group relative flex items-center justify-between gap-4 overflow-hidden rounded border border-emerald-500/40 bg-gradient-to-r from-emerald-500/10 via-emerald-500/5 to-transparent p-4 transition-all hover:border-emerald-400 hover:from-emerald-500/20 hover:via-emerald-500/10 lg:col-span-2"
            >
                <div
                    class="absolute inset-y-0 left-0 w-1 bg-emerald-500 shadow-[0_0_15px_rgba(16,185,129,0.5)]"
                ></div>
                <div class="flex items-center gap-4 pl-2">
                    <div
                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded border border-emerald-500/40 bg-emerald-500/10 text-emerald-400"
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
                                d="M13.5 21v-7.5a.75.75 0 0 1 .75-.75h3a.75.75 0 0 1 .75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349M3.75 21V9.349m0 0a3.001 3.001 0 0 0 3.75-.615A2.993 2.993 0 0 0 9.75 9.75c.896 0 1.7-.393 2.25-1.016a2.993 2.993 0 0 0 2.25 1.016c.896 0 1.7-.393 2.25-1.015a3.001 3.001 0 0 0 3.75.614m-16.5 0a3.004 3.004 0 0 1-.621-4.72l1.189-1.19A1.5 1.5 0 0 1 5.378 3h13.243a1.5 1.5 0 0 1 1.06.44l1.19 1.189a3 3 0 0 1-.621 4.72M6.75 18h3.75a.75.75 0 0 0 .75-.75V13.5a.75.75 0 0 0-.75-.75H6.75a.75.75 0 0 0-.75.75v3.75c0 .414.336.75.75.75Z"
                            />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-xs font-black tracking-widest text-emerald-400 uppercase"
                        >
                            Browse the Marketplace
                        </p>
                        <p class="mt-0.5 text-[11px] text-slate-400">
                            Discover fresh betslips from verified sellers
                        </p>
                    </div>
                </div>
                <div
                    class="flex flex-shrink-0 items-center gap-2 pr-2 font-mono text-[10px] font-black tracking-widest text-emerald-400 uppercase transition-transform group-hover:translate-x-0.5"
                >
                    <span class="hidden sm:inline">Enter</span>
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

            <!-- Refer CTA (1 col) -->
            <Link
                href="/refer"
                class="group relative flex items-center justify-between gap-4 overflow-hidden rounded border border-amber-500/40 bg-gradient-to-r from-amber-500/10 via-amber-500/5 to-transparent p-4 transition-all hover:border-amber-400 hover:from-amber-500/20 hover:via-amber-500/10"
            >
                <div
                    class="absolute inset-y-0 left-0 w-1 bg-amber-500 shadow-[0_0_15px_rgba(245,158,11,0.5)]"
                ></div>
                <div class="flex items-center gap-4 pl-2">
                    <div
                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded border border-amber-500/40 bg-amber-500/10 text-amber-400"
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
                                d="M21 11.25v8.25a1.5 1.5 0 0 1-1.5 1.5H4.5a1.5 1.5 0 0 1-1.5-1.5v-8.25M12 4.875A2.625 2.625 0 1 0 9.375 7.5H12m0-2.625V7.5m0-2.625A2.625 2.625 0 1 1 14.625 7.5H12m0 0V21m-8.625-9.75h18c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125h-18c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z"
                            />
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <p
                            class="text-xs font-black tracking-widest text-amber-400 uppercase"
                        >
                            Refer & Earn
                        </p>
                        <p class="mt-0.5 text-[11px] text-slate-400">
                            10% of every winning purchase
                        </p>
                    </div>
                </div>
                <div
                    class="flex flex-shrink-0 items-center gap-2 pr-2 font-mono text-[10px] font-black tracking-widest text-amber-400 uppercase transition-transform group-hover:translate-x-0.5"
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

            <!-- ═══════════════ TAB STRIP ═══════════════ -->
            <BuyerTabs v-model="activeTab" :tabs="tabs" />

            <!-- ═══════════════ OVERVIEW TAB ═══════════════ -->
            <template v-if="activeTab === 'overview'">
                <!-- Wallet (single block, responsive) -->
                <Panel title="WALLET" accent="emerald">
                    <template #actions>
                        <span
                            class="font-mono text-[9px] font-bold text-slate-500 uppercase"
                        >
                            [{{ buyer_data.wallet.currency }}]
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
                                    :value="buyer_data.wallet.balance"
                                    :currency="buyer_data.wallet.currency"
                                />
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <DepositPopover
                                :currency="'KES'"
                                :initial_amount="100"
                                :initial_phone="
                                    buyer_data?.user?.phone ?? ''
                                "
                            />
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
                            class="grid grid-cols-1 gap-2.5 sm:grid-cols-3"
                        >
                            <div
                                class="flex items-center justify-between rounded border border-gray-800/40 bg-[#111622] p-2.5"
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
                                    <Money
                                        :value="
                                            buyer_data.wallet.escrow_balance
                                        "
                                        :currency="
                                            buyer_data.wallet.currency
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
                                            buyer_data.wallet.total_deposited
                                        "
                                        :currency="
                                            buyer_data.wallet.currency
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
                                            buyer_data.wallet.total_withdrawn
                                        "
                                        :currency="
                                            buyer_data.wallet.currency
                                        "
                                    />
                                </span>
                            </div>
                        </div>
                    </div>
                </Panel>

                <!-- Two metric cards: Performance + Money -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <Panel title="PERFORMANCE" accent="sky">
                        <div class="grid grid-cols-2 gap-4 p-4">
                            <div>
                                <span
                                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                                    >Win Rate</span
                                >
                                <div
                                    class="mt-1 font-mono text-3xl font-black tracking-tight text-white"
                                >
                                    {{ buyer_data.performance.win_rate }}%
                                </div>
                            </div>
                            <div>
                                <span
                                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                                    >Refund Rate</span
                                >
                                <div
                                    class="mt-1 font-mono text-3xl font-black tracking-tight text-white"
                                >
                                    {{ buyer_data.performance.refund_rate }}%
                                </div>
                            </div>
                        </div>
                        <div
                            class="grid grid-cols-3 divide-x divide-[#232d42]/60 border-t border-[#232d42]/60"
                        >
                            <div class="p-3 text-center">
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                    >Purchases</span
                                >
                                <span
                                    class="mt-1 block font-mono text-sm font-black text-white"
                                    >{{
                                        buyer_data.performance.total_purchases
                                    }}</span
                                >
                            </div>
                            <div class="p-3 text-center">
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                    >Pending</span
                                >
                                <span
                                    class="mt-1 block font-mono text-sm font-black text-amber-400"
                                    >{{ buyer_data.purchases.pending }}</span
                                >
                            </div>
                            <div class="p-3 text-center">
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                    >Avg Price</span
                                >
                                <span
                                    class="mt-1 block font-mono text-sm font-black text-white"
                                >
                                    <Money
                                        :value="
                                            buyer_data.performance.avg_price
                                        "
                                        :currency="'KES'"
                                    />
                                </span>
                            </div>
                        </div>
                    </Panel>

                    <Panel title="MONEY" accent="amber">
                        <div class="grid grid-cols-2 gap-4 p-4">
                            <div>
                                <span
                                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                                    >Total Spent</span
                                >
                                <div
                                    class="mt-1 font-mono text-3xl font-black tracking-tight text-white"
                                >
                                    <Money
                                        :value="
                                            buyer_data.financial.total_spent
                                        "
                                        :currency="'KES'"
                                    />
                                </div>
                            </div>
                            <div>
                                <span
                                    class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                                    >Net Spent</span
                                >
                                <div
                                    class="mt-1 font-mono text-3xl font-black tracking-tight text-amber-400"
                                >
                                    <Money
                                        :value="
                                            buyer_data.financial.net_spent
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
                                    >Refunded</span
                                >
                                <span
                                    class="mt-1 block font-mono text-sm font-black text-purple-400"
                                >
                                    <Money
                                        :value="
                                            buyer_data.performance
                                                .total_refunded
                                        "
                                        :currency="'KES'"
                                    />
                                </span>
                            </div>
                            <div class="p-3 text-center">
                                <span
                                    class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                    >Committed</span
                                >
                                <span
                                    class="mt-1 block font-mono text-sm font-black text-white"
                                >
                                    <Money
                                        :value="
                                            buyer_data.financial
                                                .total_committed
                                        "
                                        :currency="'KES'"
                                    />
                                </span>
                            </div>
                        </div>
                    </Panel>
                </div>

                <!-- Split: Active purchases (left) + Recent form (right) -->
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="lg:col-span-2">
                        <Panel
                            :title="`Active Purchases [${buyer_data.purchases.active}]`"
                            accent="sky"
                        >
                            <template #actions>
                                <span
                                    class="font-mono text-[9px] text-slate-500 uppercase"
                                    >Your purchases</span
                                >
                            </template>
                            <div class="divide-y divide-gray-800/40">
                                <div
                                    v-if="
                                        buyer_data.purchases.recent
                                            ?.length === 0
                                    "
                                    class="p-6 text-center font-mono text-xs text-slate-500"
                                >
                                    No active purchases currently.
                                </div>
                                <BetslipsTable
                                    v-else
                                    :betslips="purchasesForTable"
                                />
                            </div>
                        </Panel>
                    </div>

                    <Panel title="RECENT FORM" accent="slate">
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
                                              : form.status === 'V'
                                                ? 'border border-slate-500/30 bg-slate-500/10 text-slate-400'
                                                : 'border border-amber-500/30 bg-amber-500/10 text-amber-400',
                                    ]"
                                    :title="`Date: ${form.date}`"
                                >
                                    {{ form.status }}
                                </div>
                            </div>
                            <p
                                v-if="
                                    buyer_data.performance.recent_form
                                        .length === 0
                                "
                                class="py-2 text-center font-mono text-[10px] text-slate-500 uppercase"
                            >
                                No form yet
                            </p>
                        </div>
                    </Panel>
                </div>

                <!-- Referral -->
                <ReferralCard
                    v-if="buyer_data.referral_card"
                    :card="buyer_data.referral_card"
                />
            </template>

            <!-- ═══════════════ PURCHASES TAB ═══════════════ -->
            <template v-else-if="activeTab === 'purchases'">
                <Panel
                    :title="`Active Purchases [${buyer_data.purchases.active}]`"
                    accent="emerald"
                >
                    <template #actions>
                        <span
                            class="font-mono text-[9px] text-slate-500 uppercase"
                            >Your purchases</span
                        >
                    </template>
                    <div class="divide-y divide-gray-800/40">
                        <div
                            v-if="buyer_data.purchases.recent?.length === 0"
                            class="p-6 text-center font-mono text-xs text-slate-500"
                        >
                            No active purchases currently.
                        </div>
                        <BetslipsTable v-else :betslips="purchasesForTable" />
                    </div>
                </Panel>

                <Panel
                    :title="`Settled Betslips [${buyer_data.settled_outcomes.length}]`"
                    accent="emerald"
                >
                    <template #actions>
                        <span
                            class="font-mono text-[9px] text-slate-500 uppercase"
                            >Outcomes</span
                        >
                    </template>
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
                                    <Money
                                        :value="outcome.price"
                                        :currency="'KES'"
                                        :signed="true"
                                        :decimals="2"
                                    />
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
                            class="p-6 text-center font-mono text-xs text-slate-500"
                        >
                            No settled betslips yet.
                        </div>
                    </div>
                </Panel>

                <ContestsCard :contests="buyer_data.contests ?? []" />
            </template>

            <!-- ═══════════════ WATCHLIST TAB ═══════════════ -->
            <template v-else-if="activeTab === 'watchlist'">
                <Panel
                    v-if="buyer_data.watch_record.settled_count > 0"
                    title="WATCH RECORD"
                    accent="purple"
                >
                    <template #header>
                        <div class="flex items-center gap-2">
                            <span
                                class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                            >
                                Watch Record
                            </span>
                            <InfoPopover title="How the record works">
                                <p>
                                    Every slip you watch is logged here once it
                                    settles. Each one counts as a flat 1 unit
                                    stake at the slip's listed total odds.
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
                    </template>

                    <template #actions>
                        <Link
                            href="/watchlist/record"
                            class="font-mono text-[9px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300"
                        >
                            Full record →
                        </Link>
                    </template>

                    <div class="grid grid-cols-3 divide-x divide-[#232d42]/60">
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
                                    buyer_data.watch_record.units.toFixed(2)
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
                                    >{{ buyer_data.watch_record.won_count }}W</span
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
                                        buyer_data.watch_record.voided_count
                                    }}V</span
                                >
                            </p>
                        </div>
                    </div>

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
                </Panel>

                <Panel
                    v-else
                    title="WATCH RECORD"
                    accent="purple"
                >
                    <div
                        class="p-8 text-center font-mono text-xs text-slate-500"
                    >
                        No settled watches yet.
                    </div>
                </Panel>

                <FollowingTable
                    v-if="buyer_data.following_stats"
                    :following="buyer_data.following_stats.recent"
                    title="Sellers You Follow"
                />
            </template>

            <!-- ═══════════════ WALLET TAB ═══════════════ -->
            <template v-else-if="activeTab === 'wallet'">
                <Panel title="ACCOUNT: WALLET DETAILS" accent="amber">
                    <template #actions>
                        <span
                            class="font-mono text-[9px] font-bold text-slate-500 uppercase"
                        >
                            [{{ buyer_data.wallet.currency }}]
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
                                Liquid Available Balance
                            </span>
                            <div
                                class="my-1 font-mono text-3xl font-black tracking-tight text-white"
                            >
                                <Money
                                    :value="buyer_data.wallet.balance"
                                    :currency="buyer_data.wallet.currency"
                                />
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <DepositPopover
                                :currency="'KES'"
                                :initial_amount="100"
                                :initial_phone="
                                    buyer_data?.user?.phone ?? ''
                                "
                            />
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
                            class="grid grid-cols-1 gap-2.5 sm:grid-cols-3"
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
                                    <Money
                                        :value="
                                            buyer_data.wallet.escrow_balance
                                        "
                                        :currency="
                                            buyer_data.wallet.currency
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
                                        >Node injections</span
                                    >
                                </div>
                                <span
                                    class="font-mono text-xs font-bold text-sky-400"
                                >
                                    <Money
                                        :value="
                                            buyer_data.wallet.total_deposited
                                        "
                                        :currency="
                                            buyer_data.wallet.currency
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
                                        >Cleared revenue</span
                                    >
                                </div>
                                <span
                                    class="font-mono text-xs font-bold text-rose-400"
                                >
                                    <Money
                                        :value="
                                            buyer_data.wallet.total_withdrawn
                                        "
                                        :currency="
                                            buyer_data.wallet.currency
                                        "
                                    />
                                </span>
                            </div>
                        </div>
                    </div>
                </Panel>

                <TransactionsTable
                    :transactions="buyer_data.wallet.recent_transactions"
                />
            </template>

            <!-- ═══════════════ ANALYTICS TAB ═══════════════ -->
            <template v-else-if="activeTab === 'analytics'">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Panel title="WIN RATE OVER TIME" accent="sky">
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
                    </Panel>

                    <Panel title="PURCHASE STATUS" accent="amber">
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
                    </Panel>

                    <Panel title="TOP SELLERS" accent="purple">
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
                                    <span
                                        class="ml-2 text-[9px] text-slate-500"
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
                    </Panel>

                    <Panel title="RECENT ACTIVITY" accent="slate">
                        <div
                            class="no-scrollbar max-h-[420px] space-y-2.5 overflow-y-auto p-4"
                        >
                            <div
                                v-for="(act, index) in buyer_data.activity"
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
                                v-if="buyer_data.activity.length === 0"
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
import { computed, ref, watch, onMounted } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

import Panel from '@/components/Panel.vue';
import Money from '@/components/Money.vue';
import BuyerTabs from '@/components/BuyerTabs.vue';
import BetslipsTable from '@/components/BetslipsTable.vue';
import FollowingTable from '@/components/FollowingTable.vue';
import ContestsCard from '@/components/ContestsCard.vue';
import TransactionsTable from '@/components/TransactionsTable.vue';
import InfoPopover from '@/components/InfoPopover.vue';
import DepositPopover from '@/components/DepositPopover.vue';
import WithdrawalPopover from '@/components/WithdrawalPopover.vue';
import ReferralCard from '@/components/ReferralCard.vue';
import NotificationBell from '@/components/NotificationBell.vue';
import DashboardSwitcher from '@/components/DashboardSwitcher.vue';

const page = usePage();
const buyer_data = page.props.buyer_data;

// ─── Tabs ──────────────────────────────────────────────────────────
const TAB_KEYS = ['overview', 'purchases', 'watchlist', 'wallet', 'analytics'];

const tabs = computed(() => [
    {
        key: 'overview',
        label: 'Overview',
        accent: 'sky',
    },
    {
        key: 'purchases',
        label: 'Purchases',
        accent: 'emerald',
        badge: buyer_data.purchases.active || null,
    },
    {
        key: 'watchlist',
        label: 'Watchlist',
        accent: 'purple',
        badge: buyer_data.watch_record?.settled_count || null,
    },
    {
        key: 'wallet',
        label: 'Wallet',
        accent: 'amber',
    },
    {
        key: 'analytics',
        label: 'Analytics',
        accent: 'slate',
    },
]);

// Read initial tab from hash (e.g. #wallet)
const readHash = () => {
    const hash = (window.location.hash || '').replace('#', '');
    return TAB_KEYS.includes(hash) ? hash : 'overview';
};

const activeTab = ref(readHash());

// Keep ref in sync if user navigates with back/forward
const onHashChange = () => {
    activeTab.value = readHash();
};

onMounted(() => {
    window.addEventListener('hashchange', onHashChange);
});

watch(activeTab, (val) => {
    // Avoid pushing duplicate history entries
    if (window.location.hash !== `#${val}`) {
        // replaceState keeps history clean when clicking between tabs
        history.replaceState(null, '', `#${val}`);
    }
});

// ─── Purchases table shape ─────────────────────────────────────────
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