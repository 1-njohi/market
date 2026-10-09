<template>
    <div
        class="flex min-h-screen flex-col bg-[#070b14] px-2 pt-6 pb-24 font-sans text-slate-200 selection:bg-emerald-500/30 selection:text-white lg:px-8 lg:py-10"
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
                    <AsOf :at="generatedAt" class="hidden lg:inline" />
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
                                Compete with your followers
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

            <!-- ═══════════════ TAB STRIP + PERIOD (desktop) ═══════════════ -->
            <div class="hidden items-center justify-between gap-3 lg:flex">
                <BuyerTabs v-model="activeTab" :tabs="tabs" />
                <div v-if="showPeriodPicker" class="flex items-center gap-2">
                    <span
                        class="font-mono text-[9px] font-bold tracking-widest text-slate-500 uppercase"
                    >
                        Period
                    </span>
                    <ComparisonPicker v-model="period" :periods="periods" />
                </div>
            </div>

            <div
                v-if="showPeriodPicker"
                class="flex items-center justify-between gap-3 rounded border border-[#232d42] bg-[#111622] p-2 lg:hidden"
            >
                <span
                    class="ml-1 font-mono text-[9px] font-bold tracking-widest text-slate-500 uppercase"
                >
                    Period
                </span>
                <ComparisonPicker v-model="period" :periods="periods" />
            </div>

            <OnboardingBanner
                v-if="shouldShowBanner"
                headline="Welcome to your seller console"
                body="Here's where you build betslips, grow followers, and get paid. Four steps to your first sale — most sellers finish in a day."
                :steps="sellerOnboardingSteps"
                @dismiss="dismissOnboarding"
            />

            <Transition
                appear
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div :key="activeTab" class="space-y-6">
                    <!-- ═══════════ OVERVIEW ═══════════ -->
                    <template v-if="activeTab === 'overview'">
                        <Panel title="WALLET" accent="emerald">
                            <template #actions>
                                <AsOf :at="generatedAt" />
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
                                        <LiveValue
                                            :initial="
                                                Number(
                                                    seller_data.wallet.balance,
                                                )
                                            "
                                            :currency="
                                                seller_data.wallet.currency
                                            "
                                        />
                                    </div>
                                </div>

                                <div
                                    class="flex flex-col gap-2 sm:flex-row"
                                >
                                    <DepositPopover
                                        :currency="'KES'"
                                        :initial_amount="100"
                                        :initial_phone="
                                            seller_data?.user?.phone ?? ''
                                        "
                                    />
                                    <WithdrawalPopover
                                        :currency="
                                            seller_data.wallet.currency
                                        "
                                        :available-balance="
                                            Number(seller_data.wallet.balance)
                                        "
                                        :disabled="
                                            Number(
                                                seller_data.wallet.balance,
                                            ) <= 0
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
                                                    seller_data.wallet
                                                        .gross_at_stake
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
                                                    seller_data.wallet
                                                        .total_deposited
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
                                                    seller_data.wallet
                                                        .total_withdrawn
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

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <SampleMetricCard
                                v-if="isFresh"
                                label="Win Rate"
                                sample="68%"
                                sublabel="Betslips / Sold"
                                subsample="12 / 47"
                                hint="Unlocks after your first settled betslip"
                            />
                            <Panel v-else title="PERFORMANCE" accent="sky">
                                <template #actions>
                                    <span
                                        class="rounded border border-sky-500/20 bg-sky-500/5 px-1.5 py-0.5 font-mono text-[9px] font-black tracking-widest text-sky-400 uppercase"
                                    >
                                        {{ shortPeriodLabel }}
                                    </span>
                                    <Sparkline
                                        :values="winRateSeries"
                                        :width="64"
                                        :height="18"
                                        accent="sky"
                                    />
                                </template>
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
                                                    periodMetrics.win_rate
                                                }}%</span
                                            >
                                            <DeltaChip
                                                :value="
                                                    periodDeltas.win_rate_delta
                                                "
                                                suffix="%"
                                            />
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
                                                >{{
                                                    periodMetrics.roi
                                                }}%</span
                                            >
                                            <DeltaChip
                                                :value="periodDeltas.roi_delta"
                                                suffix="%"
                                            />
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
                                                periodMetrics.total_betslips
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
                                                periodMetrics.total_sold
                                            }}</span
                                        >
                                        <span
                                            class="mt-0.5 block font-mono text-[9px] font-bold"
                                            :class="
                                                deltaTextClass(
                                                    periodDeltas.total_sold_delta,
                                                )
                                            "
                                        >
                                            {{
                                                signedNumber(
                                                    periodDeltas.total_sold_delta,
                                                )
                                            }}
                                        </span>
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
                                                    seller_data.performance
                                                        .avg_legs,
                                                ).toFixed(1)
                                            }}</span
                                        >
                                        <span
                                            class="mt-0.5 block font-mono text-[9px] text-slate-600"
                                            >lifetime</span
                                        >
                                    </div>
                                </div>
                            </Panel>

                            <SampleMetricCard
                                v-if="isFresh"
                                label="Total Revenue"
                                sample="KES 42,800"
                                sublabel="Net Earnings"
                                subsample="KES 39,400"
                                hint="Unlocks after your first sale"
                            />
                            <Panel v-else title="EARNINGS" accent="amber">
                                <template #actions>
                                    <span
                                        class="rounded border border-amber-500/20 bg-amber-500/5 px-1.5 py-0.5 font-mono text-[9px] font-black tracking-widest text-amber-400 uppercase"
                                    >
                                        {{ shortPeriodLabel }}
                                    </span>
                                    <Sparkline
                                        :values="revenueSeries"
                                        :width="64"
                                        :height="18"
                                        accent="amber"
                                    />
                                </template>
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
                                                    periodMetrics.total_revenue
                                                "
                                                :currency="'KES'"
                                            />
                                        </div>
                                        <span
                                            class="mt-0.5 block font-mono text-[9px] font-bold"
                                            :class="
                                                deltaTextClass(
                                                    periodDeltas.revenue_delta,
                                                )
                                            "
                                        >
                                            {{
                                                signedNumber(
                                                    periodDeltas.revenue_delta,
                                                )
                                            }}
                                            vs prior
                                        </span>
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
                                                    periodMetrics.net_earnings
                                                "
                                                :currency="'KES'"
                                            />
                                        </div>
                                        <span
                                            class="mt-0.5 block font-mono text-[9px] font-bold"
                                            :class="
                                                deltaTextClass(
                                                    periodDeltas.net_earnings_delta,
                                                )
                                            "
                                        >
                                            {{
                                                signedNumber(
                                                    periodDeltas.net_earnings_delta,
                                                )
                                            }}
                                            vs prior
                                        </span>
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
                                        <span
                                            class="mt-0.5 block font-mono text-[9px] text-slate-600"
                                            >lifetime</span
                                        >
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
                                                    seller_data.financial
                                                        .net_at_stake
                                                "
                                                :currency="'KES'"
                                            />
                                        </span>
                                        <span
                                            class="mt-0.5 block font-mono text-[9px] text-slate-600"
                                            >live</span
                                        >
                                    </div>
                                </div>
                            </Panel>
                        </div>

                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                            <div class="lg:col-span-2">
                                <Panel
                                    :title="`Active Betslips [${seller_data.betslips.total_active}]`"
                                    accent="sky"
                                >
                                    <template #actions>
                                        <LiveBadge />
                                        <ViewAllLink
                                            href="/seller/betslips?status=active"
                                            :count="
                                                seller_data.betslips.total_active
                                            "
                                            :cap="10"
                                        />
                                        <span
                                            v-if="
                                                seller_data.betslips
                                                    .total_watchers > 0
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
                                            {{
                                                seller_data.betslips
                                                    .total_watchers
                                            }}
                                            watching
                                        </span>
                                    </template>
                                    <div class="divide-y divide-gray-800/40">
                                        <BetslipsTable
                                            v-if="
                                                seller_data.betslips.active
                                                    .length > 0
                                            "
                                            :betslips="
                                                seller_data.betslips.active
                                            "
                                        />
                                        <EmptyState
                                            v-else
                                            title="No active betslips"
                                            body="Create your first betslip, set your price, and it'll show here with live purchase and watcher counts."
                                            cta-label="Create a betslip"
                                            cta-href="/fixtures"
                                            accent="sky"
                                        />
                                    </div>
                                </Panel>
                            </div>

                            <Panel title="FOLLOWERS" accent="purple">
                                <template #actions>
                                    <ViewAllLink href="/seller/followers" />
                                </template>
                                <div
                                    class="grid grid-cols-2 divide-x divide-[#232d42]/60"
                                >
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
                                        >Buyer Conversion</span
                                    >
                                    <span
                                        class="ml-2 font-mono text-sm font-black text-white"
                                        >{{
                                            seller_data.follower_stats
                                                .buyer_conversion_rate
                                        }}%</span
                                    >
                                    <p
                                        class="mt-0.5 text-[9px] text-slate-600"
                                    >
                                        {{
                                            seller_data.follower_stats
                                                .active_buyers
                                        }}
                                        of
                                        {{ seller_data.follower_stats.total }}
                                        bought in last 30 days
                                    </p>
                                </div>
                            </Panel>
                        </div>

                        <ReferralCard
                            v-if="seller_data.referral_card"
                            :card="seller_data.referral_card"
                        />
                    </template>

                    <!-- ═══════════ BETSLIPS ═══════════ -->
                    <template v-else-if="activeTab === 'betslips'">
                        <Panel
                            :title="`Active Betslips [${seller_data.betslips.total_active}]`"
                            accent="sky"
                        >
                            <template #actions>
                                <LiveBadge />
                                <ViewAllLink
                                    href="/seller/betslips?status=active"
                                    :count="seller_data.betslips.total_active"
                                    :cap="10"
                                />
                                <Link
                                    href="/fixtures"
                                    class="font-mono text-[9px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:text-sky-300"
                                >
                                    + Create new
                                </Link>
                            </template>
                            <div class="divide-y divide-gray-800/40">
                                <BetslipsTable
                                    v-if="
                                        seller_data.betslips.active.length > 0
                                    "
                                    :betslips="seller_data.betslips.active"
                                />
                                <EmptyState
                                    v-else
                                    title="No active betslips"
                                    body="Create your first betslip, set your price, and it'll show here with live purchase and watcher counts."
                                    cta-label="Create a betslip"
                                    cta-href="/fixtures"
                                    accent="sky"
                                />
                            </div>
                        </Panel>

                        <Panel
                            :title="`Settlements [${filteredSettlements.length}]`"
                            accent="emerald"
                        >
                            <template #actions>
                                <ComparisonPicker
                                    v-model="period"
                                    :periods="periods"
                                />
                                <ViewAllLink
                                    href="/seller/settlements"
                                    :count="seller_data.settlements.length"
                                    :cap="20"
                                />
                            </template>

                            <div class="divide-y divide-gray-800/40">
                                <SettlementRow
                                    v-for="s in filteredSettlements"
                                    :key="s.id"
                                    :settlement="s"
                                />

                                <EmptyState
                                    v-if="filteredSettlements.length === 0"
                                    title="No settlements in this period"
                                    body="Try a wider period, or wait for your active betslips to reach settlement."
                                    accent="emerald"
                                />
                            </div>
                        </Panel>
                    </template>

                    <!-- ═══════════ CONTESTS ═══════════ -->
                    <template v-else-if="activeTab === 'contests'">
                        <Panel
                            v-if="
                                seller_data.contests &&
                                seller_data.contests.length > 0
                            "
                            :title="`Hosting [${seller_data.contests.length}]`"
                            accent="purple"
                        >
                            <template #actions>
                                <ViewAllLink href="/contests/mine" />
                            </template>
                            <div class="divide-y divide-gray-800/40">
                                <Link
                                    v-for="c in seller_data.contests"
                                    :key="c.id"
                                    :href="contestHref(c)"
                                    class="flex items-center justify-between gap-3 p-3 transition-colors hover:bg-[#111a30]/40"
                                >
                                    <div
                                        class="flex min-w-0 items-center gap-3"
                                    >
                                        <span
                                            class="flex h-5 flex-shrink-0 items-center justify-center rounded-sm bg-purple-500 px-1.5 font-mono text-[8px] font-black tracking-wider text-white uppercase"
                                        >
                                            HOST
                                        </span>
                                        <div class="min-w-0">
                                            <p
                                                class="truncate font-mono text-[11px] font-bold text-slate-200"
                                            >
                                                {{ c.name }}
                                            </p>
                                            <p
                                                class="mt-0.5 truncate text-[10px] text-slate-500"
                                            >
                                                {{ c.legs_count }} legs ·
                                                {{ c.accepted_entries }}
                                                accepted
                                                <template
                                                    v-if="
                                                        c.pending_requests > 0
                                                    "
                                                >
                                                    ·
                                                    <span
                                                        class="font-bold text-amber-400"
                                                    >
                                                        {{
                                                            c.pending_requests
                                                        }}
                                                        pending
                                                    </span>
                                                </template>
                                            </p>
                                        </div>
                                    </div>
                                    <span
                                        class="flex-shrink-0 font-mono text-[10px] font-bold whitespace-nowrap"
                                        :class="
                                            deadlineClass(c.entry_deadline_at)
                                        "
                                    >
                                        {{
                                            formatDeadline(
                                                c.entry_deadline_at,
                                            )
                                        }}
                                    </span>
                                </Link>
                            </div>
                        </Panel>

                        <Panel v-else title="CONTESTS" accent="purple">
                            <template #actions>
                                <ViewAllLink href="/contests/mine" />
                            </template>
                            <EmptyState
                                title="You're not hosting any contests"
                                body="Host a contest to compete with your followers, review entries, and reward the sharpest picks."
                                cta-label="Host a contest"
                                cta-href="/contests/create"
                                accent="purple"
                            />
                        </Panel>

                        <Panel title="HOW HOSTING WORKS" accent="slate">
                            <div
                                class="space-y-3 p-4 text-[11px] leading-relaxed text-slate-400"
                            >
                                <p>
                                    <span class="font-black text-slate-200"
                                        >CREATE:</span
                                    >
                                    Set the legs, the entry deadline, and the
                                    prize structure. Your contest goes live
                                    immediately.
                                </p>
                                <p>
                                    <span class="font-black text-slate-200"
                                        >REVIEW:</span
                                    >
                                    Accept or reject join requests from
                                    followers. Pending requests are counted on
                                    each row above.
                                </p>
                                <p>
                                    <span class="font-black text-slate-200"
                                        >SETTLE:</span
                                    >
                                    When the fixture outcomes are known,
                                    top-ranked entries share the pool. Payouts
                                    release to wallets automatically.
                                </p>
                            </div>
                        </Panel>
                    </template>

                    <!-- ═══════════ FOLLOWERS ═══════════ -->
                    <template v-else-if="activeTab === 'followers'">
                        <Panel title="FOLLOWER OVERVIEW" accent="purple">
                            <template #actions>
                                <ViewAllLink href="/seller/followers" />
                            </template>
                            <div
                                class="grid grid-cols-3 divide-x divide-[#232d42]/60"
                            >
                                <div class="p-4 text-center">
                                    <span
                                        class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                        >Total</span
                                    >
                                    <span
                                        class="mt-1 block font-mono text-2xl font-black text-white"
                                        >{{
                                            seller_data.follower_stats.total
                                        }}</span
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
                                            seller_data.follower_stats
                                                .new_this_week
                                        }}</span
                                    >
                                </div>
                                <div class="p-4 text-center">
                                    <span
                                        class="block text-[9px] font-black tracking-widest text-slate-500 uppercase"
                                        >Buyer Conversion</span
                                    >
                                    <span
                                        class="mt-1 block font-mono text-2xl font-black text-white"
                                        >{{
                                            seller_data.follower_stats
                                                .buyer_conversion_rate
                                        }}%</span
                                    >
                                    <span
                                        class="mt-0.5 block text-[9px] text-slate-600"
                                    >
                                        {{
                                            seller_data.follower_stats
                                                .active_buyers
                                        }}
                                        active buyers
                                    </span>
                                </div>
                            </div>
                        </Panel>

                        <FollowersTable
                            :followers="seller_data.follower_stats.recent"
                            :title="`Recent Followers`"
                        />
                    </template>

                    <!-- ═══════════ WALLET ═══════════ -->
                    <template v-else-if="activeTab === 'wallet'">
                        <Panel title="ACCOUNT: WALLET DETAILS" accent="amber">
                            <template #actions>
                                <AsOf :at="generatedAt" />
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
                                        <LiveValue
                                            :initial="
                                                Number(
                                                    seller_data.wallet.balance,
                                                )
                                            "
                                            :currency="
                                                seller_data.wallet.currency
                                            "
                                        />
                                    </div>
                                </div>

                                <div
                                    class="flex flex-col gap-2 sm:flex-row"
                                >
                                    <DepositPopover
                                        :currency="'KES'"
                                        :initial_amount="100"
                                        :initial_phone="
                                            seller_data?.user?.phone ?? ''
                                        "
                                    />
                                    <WithdrawalPopover
                                        :currency="
                                            seller_data.wallet.currency
                                        "
                                        :available-balance="
                                            Number(seller_data.wallet.balance)
                                        "
                                        :disabled="
                                            Number(
                                                seller_data.wallet.balance,
                                            ) <= 0
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
                                                    seller_data.wallet
                                                        .gross_at_stake
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
                                                    seller_data.wallet
                                                        .total_deposited
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
                                                    seller_data.wallet
                                                        .total_withdrawn
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

                        <Panel title="TRANSACTIONS" accent="slate">
                            <template #actions>
                                <ComparisonPicker
                                    v-model="period"
                                    :periods="periods"
                                />
                                <ViewAllLink
                                    href="/wallet/transactions"
                                    :count="
                                        seller_data.wallet.recent_transactions
                                            .length
                                    "
                                    :cap="10"
                                />
                            </template>
                            <TransactionsTable
                                :transactions="filteredTransactions"
                            />
                        </Panel>
                    </template>

                    <!-- ═══════════ ANALYTICS ═══════════ -->
                    <template v-else-if="activeTab === 'analytics'">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <Panel title="WIN RATE OVER TIME" accent="sky">
                                <div class="space-y-3.5 p-4">
                                    <div
                                        v-for="(rate, label) in seller_data
                                            .performance.win_rate_breakdown"
                                        :key="label"
                                        class="space-y-1"
                                    >
                                        <div
                                            class="flex items-center justify-between font-mono text-[10px] font-black tracking-wider text-slate-400 uppercase"
                                        >
                                            <span>{{
                                                label.replace(/_/g, ' ')
                                            }}</span>
                                            <span class="font-mono text-white"
                                                >{{ rate }}%</span
                                            >
                                        </div>
                                        <div
                                            class="h-1.5 w-full overflow-hidden rounded border border-[#232d42] bg-[#111622]"
                                        >
                                            <div
                                                class="h-full bg-gradient-to-r from-sky-500 to-emerald-400 transition-all duration-500"
                                                :style="{
                                                    width: `${rate}%`,
                                                }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </Panel>

                            <Panel
                                v-if="
                                    seller_data.charts.league_performance
                                        .length > 0
                                "
                                title="LEAGUE PERFORMANCE"
                                accent="purple"
                            >
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
                                <template #actions>
                                    <ComparisonPicker
                                        v-model="period"
                                        :periods="periods"
                                    />
                                </template>
                                <div
                                    class="no-scrollbar max-h-[420px] space-y-2.5 overflow-y-auto p-4"
                                >
                                    <div
                                        v-for="(act, index) in filteredActivity"
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
                                    <p
                                        v-if="filteredActivity.length === 0"
                                        class="p-4 text-center font-mono text-xs text-slate-500"
                                    >
                                        No recent activity in this period.
                                    </p>
                                </div>
                            </Panel>
                        </div>
                    </template>
                </div>
            </Transition>
        </div>

        <BuyerTabs v-model="activeTab" :tabs="tabs" variant="bottom-bar" />
    </div>
</template>

<script setup>
import {
    computed,
    ref,
    watch,
    nextTick,
    onMounted,
    onUnmounted,
} from 'vue';
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
import EmptyState from '@/components/EmptyState.vue';
import SampleMetricCard from '@/components/SampleMetricCard.vue';
import OnboardingBanner from '@/components/OnboardingBanner.vue';
import ComparisonPicker from '@/components/ComparisonPicker.vue';
import Sparkline from '@/components/Sparkline.vue';
import AsOf from '@/components/AsOf.vue';
import LiveValue from '@/components/LiveValue.vue';
import DeltaChip from '@/components/DeltaChip.vue';
import LiveBadge from '@/components/LiveBadge.vue';
import ViewAllLink from '@/components/ViewAllLink.vue';
import SettlementRow from '@/components/SettlementRow.vue';
import { useOnboarding } from '@/composables/useOnboarding';
import { usePeriod } from '@/composables/usePeriod';

const page = usePage();
const seller_data = page.props.seller_data;

const {
    isFresh,
    shouldShowBanner,
    dismiss: dismissOnboarding,
} = useOnboarding(seller_data, { role: 'seller' });

const sellerOnboardingSteps = [
    { title: 'Create a betslip', body: 'Pick fixtures and set your price', href: '/fixtures' },
    { title: 'Share your profile', body: 'Grow followers who buy your slips', href: '/refer' },
    { title: 'Host a contest', body: 'Compete with your followers', href: '/contests/create' },
    { title: 'Get paid', body: 'Earnings settle to your wallet', href: '#wallet' },
];

const {
    period,
    periods,
    shortLabel: shortPeriodLabel,
    filterByDate,
} = usePeriod('seller', '30d');

const periodMetrics = computed(() => {
    const fromBackend = seller_data.periods?.[period.value];
    if (fromBackend) {
        return {
            win_rate: fromBackend.win_rate ?? 0,
            roi: fromBackend.roi ?? 0,
            total_betslips: fromBackend.total_betslips ?? 0,
            total_sold: fromBackend.total_sold ?? 0,
            total_revenue: fromBackend.total_revenue ?? 0,
            net_earnings: fromBackend.net_earnings ?? 0,
        };
    }
    return {
        win_rate: seller_data.performance.win_rate,
        roi: seller_data.performance.roi,
        total_betslips: seller_data.performance.total_betslips,
        total_sold: seller_data.performance.total_sold,
        total_revenue: seller_data.financial.total_revenue,
        net_earnings: seller_data.financial.net_earnings,
    };
});

const periodDeltas = computed(() => {
    const c = seller_data.periods?.[period.value]?.comparison;
    return {
        win_rate_delta: c?.win_rate_delta ?? 0,
        roi_delta: c?.roi_delta ?? 0,
        total_sold_delta: c?.total_sold_delta ?? 0,
        revenue_delta: c?.revenue_delta ?? 0,
        net_earnings_delta: c?.net_earnings_delta ?? 0,
    };
});

const filteredSettlements = computed(() =>
    filterByDate(seller_data.settlements, 'settled_at'),
);

const filteredTransactions = computed(() =>
    filterByDate(seller_data.wallet.recent_transactions, 'created_at'),
);

const filteredActivity = computed(() =>
    filterByDate(seller_data.activity, 'created_at'),
);

const winRateSeries = computed(() => {
    if (seller_data.performance?.series?.win_rate_7d) {
        return seller_data.performance.series.win_rate_7d;
    }
    const b = seller_data.performance.win_rate_breakdown ?? {};
    return [b.all_time, b.last_90_days, b.last_30_days, b.last_7_days].filter(
        (n) => typeof n === 'number' && !Number.isNaN(n),
    );
});

const revenueSeries = computed(() => {
    if (seller_data.performance?.series?.revenue_7d) {
        return seller_data.performance.series.revenue_7d;
    }
    const trend = seller_data.financial?.revenue_trend ?? [];
    const values = trend.map((d) => Number(d.revenue ?? 0));
    return values.some((v) => v > 0) ? values : [];
});

const deltaTextClass = (delta) => {
    if (!delta || delta === 0) return 'text-slate-500';
    return delta > 0 ? 'text-emerald-400' : 'text-rose-400';
};

const signedNumber = (delta) => {
    if (delta === undefined || delta === null) return '';
    const sign = delta > 0 ? '+' : '';
    return `${sign}${delta}`;
};

const generatedAt = computed(
    () => seller_data.generated_at ?? new Date().toISOString(),
);

const TAB_KEYS = [
    'overview',
    'betslips',
    'contests',
    'followers',
    'wallet',
    'analytics',
];

const tabs = computed(() => [
    { key: 'overview', label: 'Overview', accent: 'emerald' },
    { key: 'betslips', label: 'Betslips', accent: 'sky', badge: seller_data.betslips.total_active || null },
    { key: 'contests', label: 'Contests', accent: 'purple', badge: seller_data.contests?.length || null },
    { key: 'followers', label: 'Followers', accent: 'purple', badge: seller_data.follower_stats.total || null },
    { key: 'wallet', label: 'Wallet', accent: 'amber' },
    { key: 'analytics', label: 'Analytics', accent: 'slate' },
]);

const readHash = () => {
    const hash = (window.location.hash || '').replace('#', '');
    return TAB_KEYS.includes(hash) ? hash : 'overview';
};

const activeTab = ref(readHash());

const showPeriodPicker = computed(() => activeTab.value === 'overview');

const onHashChange = () => {
    activeTab.value = readHash();
};

onMounted(() => {
    window.addEventListener('hashchange', onHashChange);
});

onUnmounted(() => {
    window.removeEventListener('hashchange', onHashChange);
});

watch(activeTab, async (val) => {
    if (window.location.hash !== `#${val}`) {
        history.replaceState(null, '', `#${val}`);
    }
    await nextTick();
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

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

const contestHref = (contest) => `/contests/${contest.uuid}/manage`;

const formatDeadline = (iso) => {
    if (!iso) return '—';
    const d = new Date(iso);
    const now = new Date();
    const diffMs = d - now;

    if (diffMs < 0) return 'expired';

    const hours = Math.floor(diffMs / (1000 * 60 * 60));
    if (hours < 1) {
        const mins = Math.floor(diffMs / (1000 * 60));
        return `${mins}m left`;
    }
    if (hours < 24) return `${hours}h left`;
    const days = Math.floor(hours / 24);
    return `${days}d left`;
};

const deadlineClass = (iso) => {
    if (!iso) return 'text-slate-500';
    const d = new Date(iso);
    const hours = (d - new Date()) / (1000 * 60 * 60);
    if (hours < 0) return 'text-rose-400';
    if (hours < 12) return 'text-amber-400';
    return 'text-slate-500';
};

const toneBadgeClass = (tone) => {
    switch (tone) {
        case 'positive': return 'bg-emerald-500 text-[#070b14]';
        case 'negative': return 'bg-rose-500 text-white';
        case 'pending':  return 'bg-amber-500 text-[#070b14]';
        case 'neutral':
        default:         return 'bg-slate-500 text-white';
    }
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>