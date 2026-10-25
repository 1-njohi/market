<template>
    <div class="flex min-h-screen flex-col bg-[#070b14] px-4 pt-6 pb-28 font-sans text-slate-200 selection:bg-emerald-500/30 selection:text-white lg:px-8 lg:py-10">
        <div class="mx-auto w-full max-w-7xl space-y-6">
            <!-- ═══════════════ HEADER ═══════════════ -->
            <header class="flex w-full items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3.5">
                    <div class="relative shrink-0">
                        <img :src="seller_data.user.avatar" :alt="seller_data.user.name" class="h-12 w-12 rounded-full bg-[#111622] object-cover ring-2 ring-white/10 md:h-14 md:w-14" />
                        <span
                            v-if="seller_data.user.is_verified"
                            class="absolute -right-0.5 -bottom-0.5 flex h-5 w-5 items-center justify-center rounded-full border-2 border-[#070b14] bg-emerald-400 text-[10px] font-black text-[#070b14]"
                            title="Verified"
                        >✓</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-emerald-400">Tipster</p>
                        <h1 class="truncate text-xl font-black tracking-tight text-white md:text-2xl">{{ seller_data.user.name }}</h1>
                        <p class="truncate text-xs text-slate-500">Code {{ seller_data.user.code }} · Member since {{ seller_data.user.member_since }}</p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <AsOf :at="generatedAt" class="hidden lg:inline" />
                    <DashboardSwitcher active="seller" />
                    <!-- <NotificationBell :initial-unread-count="seller_data.notifications.unread_count" /> -->
                </div>
            </header>

            <!-- ═══════════════ FEE TIER ═══════════════ -->
            <section class="relative overflow-hidden rounded-3xl border border-emerald-400/20 bg-gradient-to-r from-emerald-500/10 via-[#0b1220] to-[#0b1220] p-5">
                <div class="relative flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div :class="['flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl text-xl font-black', tierBadgeClass]">{{ tierRoman }}</div>
                        <div>
                            <p class="text-sm text-slate-400">Fee tier {{ seller_data.fee_tier.current_tier }} of 4</p>
                            <p class="text-2xl font-black tracking-tight text-white tabular-nums">
                                {{ feePercent }}%
                                <span class="text-sm font-medium text-slate-500">platform fee</span>
                            </p>
                            <p class="text-sm text-slate-500">
                                Based on <span class="font-bold text-white tabular-nums">{{ seller_data.fee_tier.total_sales }}</span> lifetime sales
                            </p>
                        </div>
                    </div>

                    <div v-if="seller_data.fee_tier.sales_until_next_tier" class="sm:w-72">
                        <div class="flex items-baseline justify-between gap-3 text-sm">
                            <span class="text-slate-400">
                                <span class="font-black text-white tabular-nums">{{ seller_data.fee_tier.sales_until_next_tier }}</span> more sales to unlock
                            </span>
                            <span class="font-bold text-emerald-300 tabular-nums">{{ (seller_data.fee_tier.next_tier_percentage * 100).toFixed(0) }}% fee</span>
                        </div>
                        <div class="mt-2 h-2.5 w-full overflow-hidden rounded-full bg-white/[0.07]" role="progressbar" :aria-valuenow="Math.round(tierProgressPct)" aria-valuemin="0" aria-valuemax="100" aria-label="Progress to next fee tier">
                            <div class="h-full rounded-full bg-gradient-to-r from-sky-400 to-emerald-300 transition-all duration-500" :style="{ width: tierProgressPct + '%' }"></div>
                        </div>
                    </div>

                    <div v-else class="inline-flex items-center gap-2 self-start rounded-full bg-emerald-400/10 px-4 py-2 sm:self-auto">
                        <span class="text-lg" aria-hidden="true">🏆</span>
                        <span class="text-sm font-bold text-emerald-300">Highest tier</span>
                    </div>
                </div>
            </section>

            <!-- ═══════════════ TAB STRIP + PERIOD (desktop) ═══════════════ -->
            <div class="hidden items-center justify-between gap-3 lg:flex">
                <BuyerTabs v-model="activeTab" :tabs="tabs" />
                <div v-if="showPeriodPicker" class="flex items-center gap-2">
                    <span class="text-xs text-slate-500">Period</span>
                    <ComparisonPicker v-model="period" :periods="periods" />
                </div>
            </div>

            <div v-if="showPeriodPicker" class="flex items-center justify-between gap-3 rounded-2xl border border-white/[0.06] bg-white/[0.03] px-4 py-2.5 lg:hidden">
                <span class="text-sm text-slate-400">Period</span>
                <ComparisonPicker v-model="period" :periods="periods" />
            </div>

            <OnboardingBanner
                v-if="shouldShowBanner"
                headline="Welcome to your seller console"
                body="Here's where you build betslips, grow followers, and get paid. Four steps to your first sale — most sellers finish in a day."
                :steps="sellerOnboardingSteps"
                @dismiss="dismissOnboarding"
            />

            <Transition appear enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0">
                <div :key="activeTab" class="space-y-6">
                    <!-- ═══════════ OVERVIEW ═══════════ -->
                    <template v-if="activeTab === 'overview'">
                        <!-- Earnings first: balance + the two things a seller does -->
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <section class="relative overflow-hidden rounded-3xl border border-emerald-400/20 bg-gradient-to-br from-emerald-500/15 via-[#0b1220] to-[#0b1220] p-5 sm:p-6 lg:col-span-2">
                                <div class="pointer-events-none absolute -top-20 -right-16 h-52 w-52 rounded-full bg-emerald-400/15 blur-3xl" aria-hidden="true"></div>

                                <div class="relative flex items-center justify-between">
                                    <p class="text-sm text-slate-400">Available balance</p>
                                    <span class="text-xs font-semibold text-slate-500">{{ seller_data.wallet.currency }}</span>
                                </div>
                                <div class="relative mt-1 text-4xl font-black tracking-tight text-white tabular-nums sm:text-5xl">
                                    <LiveValue :initial="Number(seller_data.wallet.balance)" :currency="seller_data.wallet.currency" />
                                </div>

                                <div class="relative mt-5 flex flex-col gap-2 sm:flex-row">
                                    <DepositPopover :currency="'KES'" :initial_amount="100" :initial_phone="seller_data?.user?.phone ?? ''" />
                                    <WithdrawalPopover
                                        :currency="seller_data.wallet.currency"
                                        :available-balance="Number(seller_data.wallet.balance)"
                                        :disabled="Number(seller_data.wallet.balance) <= 0"
                                        class="flex-1"
                                    />
                                </div>

                                <dl class="relative mt-5 grid grid-cols-3 gap-2">
                                    <div class="rounded-2xl bg-black/25 p-3">
                                        <dt class="text-xs text-slate-500">At stake</dt>
                                        <dd class="mt-1 text-sm font-bold text-amber-300 tabular-nums"><Money :value="seller_data.wallet.gross_at_stake" :currency="seller_data.wallet.currency" /></dd>
                                        <p class="text-xs text-slate-600">if all win</p>
                                    </div>
                                    <div class="rounded-2xl bg-black/25 p-3">
                                        <dt class="text-xs text-slate-500">Deposited</dt>
                                        <dd class="mt-1 text-sm font-bold text-sky-300 tabular-nums"><Money :value="seller_data.wallet.total_deposited" :currency="seller_data.wallet.currency" /></dd>
                                    </div>
                                    <div class="rounded-2xl bg-black/25 p-3">
                                        <dt class="text-xs text-slate-500">Withdrawn</dt>
                                        <dd class="mt-1 text-sm font-bold text-rose-300 tabular-nums"><Money :value="seller_data.wallet.total_withdrawn" :currency="seller_data.wallet.currency" /></dd>
                                    </div>
                                </dl>
                            </section>

                            <div class="flex flex-col gap-4">
                                <Link
                                    href="/fixtures"
                                    class="group relative flex flex-1 items-center justify-between gap-4 overflow-hidden rounded-3xl bg-sky-400 p-5 text-black transition-all duration-200 hover:-translate-y-0.5 hover:bg-sky-300 focus-visible:ring-2 focus-visible:ring-sky-200 focus-visible:outline-none active:scale-[0.98]"
                                >
                                    <div class="min-w-0">
                                        <p class="text-lg font-black tracking-tight">Create a betslip</p>
                                        <p class="mt-0.5 text-sm text-black/70">Browse fixtures and build your next tip</p>
                                    </div>
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-black/85 text-sky-300 transition-transform duration-200 group-hover:translate-x-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                                        </svg>
                                    </span>
                                </Link>

                                <Link
                                    href="/contests/create"
                                    class="group flex items-center justify-between gap-4 rounded-3xl border border-purple-400/30 bg-purple-400/10 px-5 py-4 transition-all duration-200 hover:border-purple-300/60 hover:bg-purple-400/15 focus-visible:ring-2 focus-visible:ring-purple-300 focus-visible:outline-none active:scale-[0.98]"
                                >
                                    <div class="min-w-0">
                                        <p class="text-base font-bold text-purple-200">Host a contest</p>
                                        <p class="text-sm text-slate-400">Compete with your followers</p>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-5 w-5 shrink-0 text-purple-300 transition-transform duration-200 group-hover:translate-x-1" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <SampleMetricCard v-if="isFresh" label="Win Rate" sample="68%" sublabel="Betslips / Sold" subsample="12 / 47" hint="Unlocks after your first settled betslip" />
                            <section v-else class="overflow-hidden rounded-3xl border border-white/[0.07] bg-[#0b1220]">
                                <div class="flex items-center justify-between px-5 pt-5">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-base font-bold text-white">Performance</h2>
                                        <span class="rounded-full bg-sky-400/10 px-2 py-0.5 text-xs font-semibold text-sky-300">{{ shortPeriodLabel }}</span>
                                    </div>
                                    <Sparkline :values="winRateSeries" :width="64" :height="18" accent="sky" />
                                </div>
                                <div class="grid grid-cols-2 gap-4 px-5 py-4">
                                    <div>
                                        <p class="text-sm text-slate-500">Win rate</p>
                                        <div class="mt-1 flex items-baseline gap-2">
                                            <span class="text-4xl font-black tracking-tight text-white tabular-nums">{{ periodMetrics.win_rate }}%</span>
                                            <DeltaChip :value="periodDeltas.win_rate_delta" suffix="%" />
                                        </div>
                                    </div>
                                    <div>
                                        <p class="text-sm text-slate-500">ROI</p>
                                        <div class="mt-1 flex items-baseline gap-2">
                                            <span class="text-4xl font-black tracking-tight text-white tabular-nums">{{ periodMetrics.roi }}%</span>
                                            <DeltaChip :value="periodDeltas.roi_delta" suffix="%" />
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 divide-x divide-white/[0.06] border-t border-white/[0.06] bg-black/20">
                                    <div class="p-3.5 text-center">
                                        <p class="text-xs text-slate-500">Betslips</p>
                                        <p class="mt-1 text-base font-bold text-white tabular-nums">{{ periodMetrics.total_betslips }}</p>
                                    </div>
                                    <div class="p-3.5 text-center">
                                        <p class="text-xs text-slate-500">Sold</p>
                                        <p class="mt-1 text-base font-bold text-emerald-300 tabular-nums">{{ periodMetrics.total_sold }}</p>
                                        <p class="text-xs font-semibold" :class="deltaTextClass(periodDeltas.total_sold_delta)">{{ signedNumber(periodDeltas.total_sold_delta) }}</p>
                                    </div>
                                    <div class="p-3.5 text-center">
                                        <p class="text-xs text-slate-500">Avg legs</p>
                                        <p class="mt-1 text-base font-bold text-white tabular-nums">{{ Number(seller_data.performance.avg_legs).toFixed(1) }}</p>
                                        <p class="text-xs text-slate-600">lifetime</p>
                                    </div>
                                </div>
                            </section>

                            <SampleMetricCard v-if="isFresh" label="Total Revenue" sample="KES 42,800" sublabel="Net Earnings" subsample="KES 39,400" hint="Unlocks after your first sale" />
                            <section v-else class="overflow-hidden rounded-3xl border border-white/[0.07] bg-[#0b1220]">
                                <div class="flex items-center justify-between px-5 pt-5">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-base font-bold text-white">Earnings</h2>
                                        <span class="rounded-full bg-amber-400/10 px-2 py-0.5 text-xs font-semibold text-amber-300">{{ shortPeriodLabel }}</span>
                                    </div>
                                    <Sparkline :values="revenueSeries" :width="64" :height="18" accent="amber" />
                                </div>
                                <div class="grid grid-cols-2 gap-4 px-5 py-4">
                                    <div>
                                        <p class="text-sm text-slate-500">Total revenue</p>
                                        <p class="mt-1 text-3xl font-black tracking-tight text-white tabular-nums"><Money :value="periodMetrics.total_revenue" :currency="'KES'" /></p>
                                        <p class="mt-0.5 text-xs font-semibold" :class="deltaTextClass(periodDeltas.revenue_delta)">{{ signedNumber(periodDeltas.revenue_delta) }} vs prior</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-slate-500">Net earnings</p>
                                        <p class="mt-1 text-3xl font-black tracking-tight text-amber-300 tabular-nums"><Money :value="periodMetrics.net_earnings" :currency="'KES'" /></p>
                                        <p class="mt-0.5 text-xs font-semibold" :class="deltaTextClass(periodDeltas.net_earnings_delta)">{{ signedNumber(periodDeltas.net_earnings_delta) }} vs prior</p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 divide-x divide-white/[0.06] border-t border-white/[0.06] bg-black/20">
                                    <div class="p-3.5 text-center">
                                        <p class="text-xs text-slate-500">Platform fees</p>
                                        <p class="mt-1 text-base font-bold text-rose-300 tabular-nums"><Money :value="seller_data.financial.platform_fees" :currency="'KES'" /></p>
                                        <p class="text-xs text-slate-600">lifetime</p>
                                    </div>
                                    <div class="p-3.5 text-center">
                                        <p class="text-xs text-slate-500">Net at stake</p>
                                        <p class="mt-1 text-base font-bold text-sky-300 tabular-nums"><Money :value="seller_data.financial.net_at_stake" :currency="'KES'" /></p>
                                        <p class="text-xs text-slate-600">live</p>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                            <div class="lg:col-span-2">
                                <Panel :title="`Active Betslips [${seller_data.betslips.total_active}]`" accent="sky">
                                    <template #actions>
                                        <LiveBadge />
                                        <ViewAllLink href="/seller/betslips?status=active" :count="seller_data.betslips.total_active" :cap="10" />
                                        <span v-if="seller_data.betslips.total_watchers > 0" class="inline-flex items-center gap-1.5 rounded-full bg-amber-400/10 px-2.5 py-1 text-xs font-bold text-amber-300">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                            </svg>
                                            {{ seller_data.betslips.total_watchers }} watching
                                        </span>
                                    </template>
                                    <div class="divide-y divide-gray-800/40">
                                        <BetslipsTable v-if="seller_data.betslips.active.length > 0" :betslips="seller_data.betslips.active" />
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
                                <div class="grid grid-cols-2 divide-x divide-white/[0.06]">
                                    <div class="p-4 text-center">
                                        <p class="text-xs text-slate-500">Total</p>
                                        <p class="mt-1 text-3xl font-black text-white tabular-nums">{{ seller_data.follower_stats.total }}</p>
                                    </div>
                                    <div class="p-4 text-center">
                                        <p class="text-xs text-slate-500">This week</p>
                                        <p class="mt-1 text-3xl font-black text-purple-300 tabular-nums">+{{ seller_data.follower_stats.new_this_week }}</p>
                                    </div>
                                </div>
                                <div class="border-t border-white/[0.06] bg-black/20 p-4 text-center">
                                    <p class="text-sm text-slate-400">
                                        Buyer conversion
                                        <span class="ml-1 text-lg font-black text-white tabular-nums">{{ seller_data.follower_stats.buyer_conversion_rate }}%</span>
                                    </p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ seller_data.follower_stats.active_buyers }} of {{ seller_data.follower_stats.total }} bought in last 30 days</p>
                                </div>
                            </Panel>
                        </div>

                        <ReferralCard v-if="seller_data.referral_card" :card="seller_data.referral_card" />
                    </template>

                    <!-- ═══════════ BETSLIPS ═══════════ -->
                    <template v-else-if="activeTab === 'betslips'">
                        <Panel :title="`Active Betslips [${seller_data.betslips.total_active}]`" accent="sky">
                            <template #actions>
                                <LiveBadge />
                                <ViewAllLink href="/seller/betslips?status=active" :count="seller_data.betslips.total_active" :cap="10" />
                                <Link
                                    href="/fixtures"
                                    class="inline-flex min-h-9 items-center rounded-lg bg-sky-400 px-3 text-sm font-bold text-[#070b14] transition-colors hover:bg-sky-300 focus-visible:ring-2 focus-visible:ring-sky-200 focus-visible:outline-none"
                                >
                                    + Create new
                                </Link>
                            </template>
                            <div class="divide-y divide-gray-800/40">
                                <BetslipsTable v-if="seller_data.betslips.active.length > 0" :betslips="seller_data.betslips.active" />
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

                        <Panel :title="`Settlements [${filteredSettlements.length}]`" accent="emerald">
                            <template #actions>
                                <ComparisonPicker v-model="period" :periods="periods" />
                                <ViewAllLink href="/seller/settlements" :count="seller_data.settlements.length" :cap="20" />
                            </template>

                            <div class="divide-y divide-gray-800/40">
                                <SettlementRow v-for="s in filteredSettlements" :key="s.id" :settlement="s" />

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
                        <Panel v-if="seller_data.contests && seller_data.contests.length > 0" :title="`Hosting [${seller_data.contests.length}]`" accent="purple">
                            <template #actions>
                                <ViewAllLink href="/contests/mine" />
                            </template>
                            <div class="divide-y divide-gray-800/40">
                                <Link v-for="c in seller_data.contests" :key="c.id" :href="contestHref(c)" class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03]">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex h-7 shrink-0 items-center justify-center rounded-lg bg-purple-500 px-2 text-xs font-black text-white">Host</span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-100">{{ c.name }}</p>
                                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                                {{ c.legs_count }} legs · {{ c.accepted_entries }} accepted
                                                <template v-if="c.pending_requests > 0">
                                                    ·
                                                    <span class="font-bold text-amber-300">{{ c.pending_requests }} pending</span>
                                                </template>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="shrink-0 text-sm font-semibold whitespace-nowrap" :class="deadlineClass(c.entry_deadline_at)">{{ formatDeadline(c.entry_deadline_at) }}</span>
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

                        <section class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.03] p-4">
                                <p class="text-sm font-black text-sky-300">Create</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-400">Set the legs, the entry deadline, and the prize structure. Your contest goes live immediately.</p>
                            </div>
                            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.03] p-4">
                                <p class="text-sm font-black text-amber-300">Review</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-400">Accept or reject join requests from followers. Pending requests are counted on each row above.</p>
                            </div>
                            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.03] p-4">
                                <p class="text-sm font-black text-emerald-300">Settle</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-400">When the fixture outcomes are known, top-ranked entries share the pool. Payouts release to wallets automatically.</p>
                            </div>
                        </section>
                    </template>

                    <!-- ═══════════ FOLLOWERS ═══════════ -->
                    <template v-else-if="activeTab === 'followers'">
                        <section class="overflow-hidden rounded-3xl border border-purple-400/20 bg-gradient-to-br from-purple-500/10 via-[#0b1220] to-[#0b1220]">
                            <div class="flex items-center justify-between px-5 pt-4">
                                <h2 class="text-base font-bold text-purple-200">Follower overview</h2>
                                <ViewAllLink href="/seller/followers" />
                            </div>
                            <div class="grid grid-cols-3 divide-x divide-white/[0.06] pt-2">
                                <div class="px-3 py-5 text-center">
                                    <p class="text-xs text-slate-500">Total</p>
                                    <p class="mt-1 text-3xl font-black text-white tabular-nums sm:text-4xl">{{ seller_data.follower_stats.total }}</p>
                                </div>
                                <div class="px-3 py-5 text-center">
                                    <p class="text-xs text-slate-500">New this week</p>
                                    <p class="mt-1 text-3xl font-black text-purple-300 tabular-nums sm:text-4xl">+{{ seller_data.follower_stats.new_this_week }}</p>
                                </div>
                                <div class="px-3 py-5 text-center">
                                    <p class="text-xs text-slate-500">Buyer conversion</p>
                                    <p class="mt-1 text-3xl font-black text-white tabular-nums sm:text-4xl">{{ seller_data.follower_stats.buyer_conversion_rate }}%</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ seller_data.follower_stats.active_buyers }} active buyers</p>
                                </div>
                            </div>
                        </section>

                        <FollowersTable :followers="seller_data.follower_stats.recent" :title="`Recent Followers`" />
                    </template>

                    <!-- ═══════════ WALLET ═══════════ -->
                    <template v-else-if="activeTab === 'wallet'">
                        <section class="relative overflow-hidden rounded-3xl border border-amber-400/20 bg-gradient-to-br from-amber-500/10 via-[#0b1220] to-[#0b1220] p-5 sm:p-6">
                            <div class="pointer-events-none absolute -top-20 -right-16 h-52 w-52 rounded-full bg-amber-400/10 blur-3xl" aria-hidden="true"></div>

                            <div class="relative flex items-center justify-between">
                                <p class="text-sm text-slate-400">Available balance</p>
                                <div class="flex items-center gap-2">
                                    <AsOf :at="generatedAt" />
                                    <span class="text-xs font-semibold text-slate-500">{{ seller_data.wallet.currency }}</span>
                                </div>
                            </div>
                            <div class="relative mt-1 text-4xl font-black tracking-tight text-white tabular-nums sm:text-5xl">
                                <LiveValue :initial="Number(seller_data.wallet.balance)" :currency="seller_data.wallet.currency" />
                            </div>

                            <div class="relative mt-5 flex flex-col gap-2 sm:flex-row">
                                <DepositPopover :currency="'KES'" :initial_amount="100" :initial_phone="seller_data?.user?.phone ?? ''" />
                                <WithdrawalPopover
                                    :currency="seller_data.wallet.currency"
                                    :available-balance="Number(seller_data.wallet.balance)"
                                    :disabled="Number(seller_data.wallet.balance) <= 0"
                                    class="flex-1"
                                />
                            </div>

                            <dl class="relative mt-5 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                <div class="flex items-center justify-between rounded-2xl bg-black/25 p-3.5 sm:block">
                                    <dt class="text-sm text-slate-500">Held funds</dt>
                                    <dd class="text-base font-bold text-amber-300 tabular-nums sm:mt-1"><Money :value="seller_data.wallet.gross_at_stake" :currency="seller_data.wallet.currency" /></dd>
                                </div>
                                <div class="flex items-center justify-between rounded-2xl bg-black/25 p-3.5 sm:block">
                                    <dt class="text-sm text-slate-500">Total deposited</dt>
                                    <dd class="text-base font-bold text-sky-300 tabular-nums sm:mt-1"><Money :value="seller_data.wallet.total_deposited" :currency="seller_data.wallet.currency" /></dd>
                                </div>
                                <div class="flex items-center justify-between rounded-2xl bg-black/25 p-3.5 sm:block">
                                    <dt class="text-sm text-slate-500">Total withdrawn</dt>
                                    <dd class="text-base font-bold text-rose-300 tabular-nums sm:mt-1"><Money :value="seller_data.wallet.total_withdrawn" :currency="seller_data.wallet.currency" /></dd>
                                </div>
                            </dl>
                        </section>

                        <Panel title="TRANSACTIONS" accent="slate">
                            <template #actions>
                                <ComparisonPicker v-model="period" :periods="periods" />
                                <ViewAllLink href="/wallet/transactions" :count="seller_data.wallet.recent_transactions.length" :cap="10" />
                            </template>
                            <TransactionsTable :transactions="filteredTransactions" />
                        </Panel>
                    </template>

                    <!-- ═══════════ ANALYTICS ═══════════ -->
                    <template v-else-if="activeTab === 'analytics'">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <Panel title="WIN RATE OVER TIME" accent="sky">
                                <div class="space-y-4 p-4">
                                    <div v-for="(rate, label) in seller_data.performance.win_rate_breakdown" :key="label" class="space-y-1.5">
                                        <div class="flex items-center justify-between text-sm text-slate-400">
                                            <span class="capitalize">{{ label.replace(/_/g, ' ') }}</span>
                                            <span class="font-bold text-white tabular-nums">{{ rate }}%</span>
                                        </div>
                                        <div class="h-2 w-full overflow-hidden rounded-full bg-white/[0.06]">
                                            <div class="h-full rounded-full bg-gradient-to-r from-sky-400 to-emerald-300 transition-all duration-500" :style="{ width: `${rate}%` }"></div>
                                        </div>
                                    </div>
                                </div>
                            </Panel>

                            <Panel v-if="seller_data.charts.league_performance.length > 0" title="LEAGUE PERFORMANCE" accent="purple">
                                <div class="space-y-3 p-4">
                                    <div v-for="league in seller_data.charts.league_performance" :key="league.league" class="flex items-center justify-between border-b border-white/[0.05] pb-3 last:border-0 last:pb-0">
                                        <span class="text-sm font-bold text-slate-200">{{ league.league }}</span>
                                        <span class="rounded-lg bg-purple-400/10 px-2 py-1 text-xs font-black text-purple-300 tabular-nums">{{ league.win_rate }}% WR</span>
                                    </div>
                                </div>
                            </Panel>

                            <Panel title="RECENT FORM" accent="slate">
                                <div class="p-4">
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            v-for="(form, index) in seller_data.performance.recent_form"
                                            :key="index"
                                            :class="[
                                                'flex h-9 w-9 items-center justify-center rounded-xl text-sm font-black',
                                                form.status === 'W'
                                                    ? 'bg-emerald-400/15 text-emerald-300 ring-1 ring-emerald-400/30'
                                                    : 'bg-rose-400/15 text-rose-300 ring-1 ring-rose-400/30',
                                            ]"
                                            :title="`Settled: ${form.date}`"
                                        >
                                            {{ form.status }}
                                        </div>
                                    </div>
                                    <p v-if="seller_data.performance.recent_form.length === 0" class="py-2 text-center text-sm text-slate-500">No form yet</p>
                                </div>
                            </Panel>

                            <Panel title="RECENT ACTIVITY" accent="slate">
                                <template #actions>
                                    <ComparisonPicker v-model="period" :periods="periods" />
                                </template>
                                <div class="no-scrollbar max-h-[420px] space-y-2.5 overflow-y-auto p-4">
                                    <div v-for="(act, index) in filteredActivity" :key="index" class="flex items-center justify-between gap-4 rounded-2xl border border-white/[0.06] bg-white/[0.03] p-3.5">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span :class="['flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-xs font-black select-none', toneBadgeClass(act.tone)]">{{ act.badge }}</span>
                                            <span class="text-sm font-medium text-slate-300">{{ act.message }}</span>
                                        </div>
                                        <span class="shrink-0 text-xs whitespace-nowrap text-slate-500">{{ act.time_ago }}</span>
                                    </div>
                                    <p v-if="filteredActivity.length === 0" class="p-4 text-center text-sm text-slate-500">No recent activity in this period.</p>
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

// shortLabel is what the phone bottom bar shows; six tabs need the shorter words.
const tabs = computed(() => [
    { key: 'overview', label: 'Overview', shortLabel: 'Home', accent: 'emerald' },
    { key: 'betslips', label: 'Betslips', shortLabel: 'Slips', accent: 'sky', badge: seller_data.betslips.total_active || null },
    { key: 'contests', label: 'Contests', shortLabel: 'Contest', accent: 'purple', badge: seller_data.contests?.length || null },
    { key: 'followers', label: 'Followers', shortLabel: 'Follows', accent: 'purple', badge: seller_data.follower_stats.total || null },
    { key: 'wallet', label: 'Wallet', shortLabel: 'Wallet', accent: 'amber' },
    { key: 'analytics', label: 'Analytics', shortLabel: 'Stats', accent: 'slate' },
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
            return 'bg-amber-500/20 text-amber-300 ring-1 ring-amber-400/40';
        case 2:
            return 'bg-slate-400/20 text-slate-200 ring-1 ring-slate-300/40';
        case 3:
            return 'bg-yellow-500/20 text-yellow-300 ring-1 ring-yellow-400/40';
        default:
            return 'bg-emerald-500/20 text-emerald-300 ring-1 ring-emerald-400/40';
    }
});

// How far lifetime sales are toward the next tier's threshold.
const tierProgressPct = computed(() => {
    const done = Number(seller_data.fee_tier.total_sales ?? 0);
    const left = Number(seller_data.fee_tier.sales_until_next_tier ?? 0);
    if (done + left <= 0) return 0;
    return Math.min(100, (done / (done + left)) * 100);
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