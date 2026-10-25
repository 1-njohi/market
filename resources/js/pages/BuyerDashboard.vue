<template>
    <div class="flex min-h-screen flex-col bg-[#070b14] px-4 pt-6 pb-28 font-sans text-slate-200 selection:bg-sky-500/30 selection:text-white lg:px-8 lg:py-10">
        <div class="mx-auto w-full max-w-7xl space-y-6">
            <!-- ═══════════════ HEADER ═══════════════ -->
            <header class="flex w-full items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3.5">
                    <div class="relative shrink-0">
                        <img :src="buyer_data.user.avatar" :alt="buyer_data.user.name" class="h-12 w-12 rounded-full bg-[#111622] object-cover ring-2 ring-white/10 md:h-14 md:w-14" />
                        <span
                            v-if="buyer_data.user.is_verified"
                            class="absolute -right-0.5 -bottom-0.5 flex h-5 w-5 items-center justify-center rounded-full border-2 border-[#070b14] bg-sky-400 text-[10px] font-black text-[#070b14]"
                            title="Verified squad"
                        >✓</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-sky-400">Buyer</p>
                        <h1 class="truncate text-xl font-black tracking-tight text-white md:text-2xl">{{ buyer_data.user.name }}</h1>
                        <p class="truncate text-xs text-slate-500">Code {{ buyer_data.user.code }} · Joined {{ buyer_data.user.member_since }}</p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <AsOf :at="generatedAt" class="hidden lg:inline" />
                    <DashboardSwitcher active="buyer" />
                    <!-- <NotificationBell :initial-unread-count="buyer_data.notifications.unread_count" /> -->
                </div>
            </header>

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
                headline="Welcome to your buyer console"
                body="Everything you need to research, buy, and track betslips lives here. Start with the four steps below — they take less than a minute."
                :steps="buyerOnboardingSteps"
                @dismiss="dismissOnboarding"
            />

            <Transition appear enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0">
                <div :key="activeTab" class="space-y-6">
                    <!-- ═══════════ OVERVIEW ═══════════ -->
                    <template v-if="activeTab === 'overview'">
                        <!-- Money first: balance + the two things you do with it -->
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <section class="relative overflow-hidden rounded-3xl border border-emerald-400/20 bg-gradient-to-br from-emerald-500/15 via-[#0b1220] to-[#0b1220] p-5 sm:p-6 lg:col-span-2">
                                <div class="pointer-events-none absolute -top-20 -right-16 h-52 w-52 rounded-full bg-emerald-400/15 blur-3xl" aria-hidden="true"></div>

                                <div class="relative flex items-center justify-between">
                                    <p class="text-sm text-slate-400">Available balance</p>
                                    <span class="text-xs font-semibold text-slate-500">{{ buyer_data.wallet.currency }}</span>
                                </div>
                                <div class="relative mt-1 text-4xl font-black tracking-tight text-white tabular-nums sm:text-5xl">
                                    <LiveValue :initial="Number(buyer_data.wallet.balance)" :currency="buyer_data.wallet.currency" />
                                </div>

                                <div class="relative mt-5 flex flex-col gap-2 sm:flex-row">
                                    <DepositPopover :currency="'KES'" :initial_amount="100" :initial_phone="buyer_data?.user?.phone ?? ''" />
                                    <WithdrawalPopover
                                        :currency="buyer_data.wallet.currency"
                                        :available-balance="Number(buyer_data.wallet.balance)"
                                        :disabled="Number(buyer_data.wallet.balance) <= 0"
                                        class="flex-1"
                                    />
                                </div>

                                <dl class="relative mt-5 grid grid-cols-3 gap-2">
                                    <div class="rounded-2xl bg-black/25 p-3">
                                        <dt class="text-xs text-slate-500">In escrow</dt>
                                        <dd class="mt-1 text-sm font-bold text-amber-300 tabular-nums"><Money :value="buyer_data.wallet.escrow_balance" :currency="buyer_data.wallet.currency" /></dd>
                                    </div>
                                    <div class="rounded-2xl bg-black/25 p-3">
                                        <dt class="text-xs text-slate-500">Deposited</dt>
                                        <dd class="mt-1 text-sm font-bold text-sky-300 tabular-nums"><Money :value="buyer_data.wallet.total_deposited" :currency="buyer_data.wallet.currency" /></dd>
                                    </div>
                                    <div class="rounded-2xl bg-black/25 p-3">
                                        <dt class="text-xs text-slate-500">Withdrawn</dt>
                                        <dd class="mt-1 text-sm font-bold text-rose-300 tabular-nums"><Money :value="buyer_data.wallet.total_withdrawn" :currency="buyer_data.wallet.currency" /></dd>
                                    </div>
                                </dl>
                            </section>

                            <div class="flex flex-col gap-4">
                                <Link
                                    href="/marketplace"
                                    class="group relative flex flex-1 items-center justify-between gap-4 overflow-hidden rounded-3xl bg-emerald-400 p-5 text-black transition-all duration-200 hover:-translate-y-0.5 hover:bg-emerald-300 focus-visible:ring-2 focus-visible:ring-emerald-200 focus-visible:outline-none active:scale-[0.98]"
                                >
                                    <div class="min-w-0">
                                        <p class="text-lg font-black tracking-tight">Browse the marketplace</p>
                                        <p class="mt-0.5 text-sm text-black/70">Fresh betslips from verified sellers</p>
                                    </div>
                                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-black/85 text-emerald-300 transition-transform duration-200 group-hover:translate-x-1">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-5 w-5" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                        </svg>
                                    </span>
                                </Link>

                                <Link
                                    href="/refer"
                                    class="group flex items-center justify-between gap-4 rounded-3xl border border-amber-400/30 bg-amber-400/10 px-5 py-4 transition-all duration-200 hover:border-amber-300/60 hover:bg-amber-400/15 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-[0.98]"
                                >
                                    <div class="min-w-0">
                                        <p class="text-base font-bold text-amber-200">Refer and earn</p>
                                        <p class="text-sm text-slate-400">10% of every winning purchase</p>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-5 w-5 shrink-0 text-amber-300 transition-transform duration-200 group-hover:translate-x-1" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                            </div>
                        </div>

                        <!-- Seller eligibility: shown to buyers who haven't qualified yet -->
                        <SellerProgressCard v-if="buyer_data.seller_status === 'ineligible'" :progress="buyer_data.seller_progress" :status="buyer_data.seller_status" />

                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <SampleMetricCard v-if="isFresh" label="Win Rate" sample="62%" sublabel="Purchases" subsample="48" hint="Unlocks after your first purchase" />
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
                                        <p class="text-sm text-slate-500">Refund rate</p>
                                        <div class="mt-1 flex items-baseline gap-2">
                                            <span class="text-4xl font-black tracking-tight text-white tabular-nums">{{ periodMetrics.refund_rate }}%</span>
                                            <DeltaChip :value="periodDeltas.refund_rate_delta" suffix="%" invert />
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-3 divide-x divide-white/[0.06] border-t border-white/[0.06] bg-black/20">
                                    <div class="p-3.5 text-center">
                                        <p class="text-xs text-slate-500">Purchases</p>
                                        <p class="mt-1 text-base font-bold text-white tabular-nums">{{ periodMetrics.total_purchases }}</p>
                                        <p class="text-xs font-semibold" :class="deltaTextClass(periodDeltas.total_purchases_delta)">{{ signedNumber(periodDeltas.total_purchases_delta) }}</p>
                                    </div>
                                    <div class="p-3.5 text-center">
                                        <p class="text-xs text-slate-500">Pending</p>
                                        <p class="mt-1 text-base font-bold text-amber-300 tabular-nums">{{ buyer_data.purchases.pending }}</p>
                                        <p class="text-xs text-slate-600">live</p>
                                    </div>
                                    <div class="p-3.5 text-center">
                                        <p class="text-xs text-slate-500">Avg price</p>
                                        <p class="mt-1 text-base font-bold text-white tabular-nums"><Money :value="periodMetrics.avg_price" :currency="'KES'" /></p>
                                        <p class="text-xs font-semibold" :class="deltaTextClass(periodDeltas.avg_price_delta)">{{ signedNumber(periodDeltas.avg_price_delta) }}</p>
                                    </div>
                                </div>
                            </section>

                            <SampleMetricCard v-if="isFresh" label="Total Spent" sample="KES 12,400" sublabel="Net Spent" subsample="KES 12,400" hint="Unlocks after your first purchase" />
                            <section v-else class="overflow-hidden rounded-3xl border border-white/[0.07] bg-[#0b1220]">
                                <div class="flex items-center justify-between px-5 pt-5">
                                    <div class="flex items-center gap-2">
                                        <h2 class="text-base font-bold text-white">Money</h2>
                                        <span class="rounded-full bg-amber-400/10 px-2 py-0.5 text-xs font-semibold text-amber-300">{{ shortPeriodLabel }}</span>
                                    </div>
                                    <Sparkline :values="spentSeries" :width="64" :height="18" accent="amber" />
                                </div>
                                <div class="grid grid-cols-2 gap-4 px-5 py-4">
                                    <div>
                                        <p class="text-sm text-slate-500">Total spent</p>
                                        <p class="mt-1 text-3xl font-black tracking-tight text-white tabular-nums"><Money :value="periodMetrics.total_spent" :currency="'KES'" /></p>
                                        <p class="mt-0.5 text-xs font-semibold" :class="deltaTextClass(periodDeltas.total_spent_delta)">{{ signedNumber(periodDeltas.total_spent_delta) }} vs prior</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-slate-500">Net spent</p>
                                        <p class="mt-1 text-3xl font-black tracking-tight text-amber-300 tabular-nums"><Money :value="periodMetrics.net_spent" :currency="'KES'" /></p>
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 divide-x divide-white/[0.06] border-t border-white/[0.06] bg-black/20">
                                    <div class="p-3.5 text-center">
                                        <p class="text-xs text-slate-500">Refunded</p>
                                        <p class="mt-1 text-base font-bold text-purple-300 tabular-nums"><Money :value="buyer_data.performance.total_refunded" :currency="'KES'" /></p>
                                        <p class="text-xs text-slate-600">lifetime</p>
                                    </div>
                                    <div class="p-3.5 text-center">
                                        <p class="text-xs text-slate-500">Committed</p>
                                        <p class="mt-1 text-base font-bold text-white tabular-nums"><Money :value="buyer_data.financial.total_committed" :currency="'KES'" /></p>
                                        <p class="text-xs text-slate-600">lifetime</p>
                                    </div>
                                </div>
                            </section>
                        </div>

                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                            <div class="lg:col-span-2">
                                <Panel :title="`Active Purchases [${buyer_data.purchases.active}]`" accent="sky">
                                    <template #actions>
                                        <LiveBadge />
                                        <ViewAllLink href="/buyer/purchases" :count="buyer_data.purchases.total" :cap="10" />
                                    </template>
                                    <div class="divide-y divide-gray-800/40">
                                        <EmptyState
                                            v-if="buyer_data.purchases.recent?.length === 0"
                                            title="No active purchases"
                                            body="When you buy a betslip, it shows here so you can track it live through to settlement."
                                            cta-label="Browse marketplace"
                                            cta-href="/marketplace"
                                            accent="sky"
                                        />
                                        <BetslipsTable v-else :betslips="purchasesForTable" />
                                    </div>
                                </Panel>
                            </div>

                            <Panel title="RECENT FORM" accent="slate">
                                <div class="p-4">
                                    <div class="flex flex-wrap gap-2">
                                        <div
                                            v-for="(form, index) in buyer_data.performance.recent_form"
                                            :key="index"
                                            :class="[
                                                'flex h-9 w-9 cursor-help items-center justify-center rounded-xl text-sm font-black transition-transform select-none hover:scale-105',
                                                form.status === 'W'
                                                    ? 'bg-emerald-400/15 text-emerald-300 ring-1 ring-emerald-400/30'
                                                    : form.status === 'L'
                                                      ? 'bg-rose-400/15 text-rose-300 ring-1 ring-rose-400/30'
                                                      : form.status === 'V'
                                                        ? 'bg-slate-400/15 text-slate-300 ring-1 ring-slate-400/30'
                                                        : 'bg-amber-400/15 text-amber-300 ring-1 ring-amber-400/30',
                                            ]"
                                            :title="`Date: ${form.date}`"
                                        >
                                            {{ form.status }}
                                        </div>
                                    </div>
                                    <p v-if="buyer_data.performance.recent_form.length === 0" class="py-2 text-center text-sm text-slate-500">No form yet</p>
                                </div>
                            </Panel>
                        </div>

                        <ReferralCard v-if="buyer_data.referral_card" :card="buyer_data.referral_card" />
                    </template>

                    <!-- ═══════════ PURCHASES ═══════════ -->
                    <template v-else-if="activeTab === 'purchases'">
                        <Panel :title="`Active Purchases [${buyer_data.purchases.active}]`" accent="emerald">
                            <template #actions>
                                <LiveBadge />
                                <ViewAllLink href="/buyer/purchases?status=active" :count="buyer_data.purchases.total" :cap="10" />
                            </template>
                            <div class="divide-y divide-gray-800/40">
                                <EmptyState
                                    v-if="buyer_data.purchases.recent?.length === 0"
                                    title="No active purchases"
                                    body="When you buy a betslip, it shows here so you can track it live through to settlement."
                                    cta-label="Browse marketplace"
                                    cta-href="/marketplace"
                                    accent="emerald"
                                />
                                <BetslipsTable v-else :betslips="purchasesForTable" />
                            </div>
                        </Panel>

                        <Panel :title="`Settled Betslips [${filteredOutcomes.length}]`" accent="emerald">
                            <template #actions>
                                <ComparisonPicker v-model="period" :periods="periods" />
                                <ViewAllLink href="/buyer/purchases?status=settled" :count="buyer_data.performance.total_purchases" :cap="20" />
                            </template>
                            <div class="divide-y divide-gray-800/40">
                                <div v-for="outcome in filteredOutcomes" :key="outcome.id" class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03]">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span
                                            :class="[
                                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-sm font-black select-none',
                                                outcome.outcome === 'won'
                                                    ? 'bg-emerald-400 text-[#070b14]'
                                                    : outcome.outcome === 'voided'
                                                      ? 'bg-slate-400 text-[#070b14]'
                                                      : 'bg-rose-400 text-white',
                                            ]"
                                        >
                                            {{ outcome.outcome === 'won' ? 'W' : outcome.outcome === 'voided' ? 'V' : 'L' }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-mono text-sm font-bold text-sky-300">{{ outcome.betslip_code }}</p>
                                            <p class="mt-0.5 truncate text-xs text-slate-500">from {{ outcome.seller_name }} · {{ outcome.settled_ago }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-bold tabular-nums" :class="outcome.outcome === 'won' ? 'text-rose-300' : 'text-emerald-300'">
                                            <Money :value="outcome.price" :currency="'KES'" :signed="true" :decimals="2" />
                                        </p>
                                        <p class="text-xs text-slate-500">{{ outcome.outcome === 'won' ? 'Paid out' : outcome.outcome === 'voided' ? 'Voided' : 'Refunded' }}</p>
                                    </div>
                                </div>

                                <EmptyState
                                    v-if="filteredOutcomes.length === 0"
                                    title="No settled betslips in this period"
                                    body="Try a wider period, or wait for your active purchases to settle."
                                    accent="emerald"
                                />
                            </div>
                        </Panel>
                    </template>

                    <!-- ═══════════ CONTESTS ═══════════ -->
                    <template v-else-if="activeTab === 'contests'">
                        <Panel v-if="buyer_data.contests && buyer_data.contests.length > 0" :title="`Active Contests [${buyer_data.contests.length}]`" accent="amber">
                            <template #actions>
                                <ViewAllLink href="/contests/mine" />
                            </template>
                            <div class="divide-y divide-gray-800/40">
                                <Link v-for="c in buyer_data.contests" :key="c.id" :href="contestHref(c)" class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03]">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex h-7 shrink-0 items-center justify-center rounded-lg bg-sky-400 px-2 text-xs font-black text-[#070b14]">Play</span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-100">{{ c.name }}</p>
                                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ c.legs_count }} legs · {{ c.picks_submitted }}/{{ c.legs_count }} picks</p>
                                        </div>
                                    </div>
                                    <span class="shrink-0 text-sm font-semibold whitespace-nowrap" :class="deadlineClass(c.entry_deadline_at)">{{ formatDeadline(c.entry_deadline_at) }}</span>
                                </Link>
                            </div>
                        </Panel>

                        <Panel v-else title="CONTESTS" accent="amber">
                            <EmptyState
                                title="No active contests"
                                body="Join a contest to compete with other buyers, or browse contests hosted by sellers."
                                cta-label="Browse contests"
                                cta-href="/contests/mine"
                                accent="amber"
                            />
                        </Panel>

                        <section class="grid gap-3 sm:grid-cols-3">
                            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.03] p-4">
                                <p class="text-sm font-black text-sky-300">Pick</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-400">Choose your selections for each leg from a host's contest. Submit before the deadline to enter.</p>
                            </div>
                            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.03] p-4">
                                <p class="text-sm font-black text-amber-300">Compete</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-400">Your picks are ranked against other players based on accuracy and odds multiplier.</p>
                            </div>
                            <div class="rounded-2xl border border-white/[0.06] bg-white/[0.03] p-4">
                                <p class="text-sm font-black text-emerald-300">Win</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-400">Top-ranked entries share the prize pool. Payouts hit your wallet automatically after settlement.</p>
                            </div>
                        </section>
                    </template>

                    <!-- ═══════════ WATCHLIST ═══════════ -->
                    <template v-else-if="activeTab === 'watchlist'">
                        <Panel v-if="buyer_data.watch_record.settled_count > 0" title="WATCH RECORD" accent="purple">
                            <template #header>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-bold text-amber-300">Watch record</span>
                                    <InfoPopover title="How the record works">
                                        <p>Every slip you watch is logged here once it settles. Each one counts as a flat 1 unit stake at the slip's listed total odds.</p>
                                        <div class="rounded border border-[#232d42] bg-[#070b14] px-3 py-2 font-mono text-[11px]">
                                            <p class="text-emerald-400">Win: + (odds − 1) units</p>
                                            <p class="text-rose-400">Loss: − 1 unit</p>
                                            <p class="text-slate-400">Void: 0 units</p>
                                        </div>
                                        <p class="text-slate-400">This is a hypothetical P/L — you never staked real money on watched slips.</p>
                                    </InfoPopover>
                                </div>
                            </template>

                            <template #actions>
                                <ViewAllLink href="/watchlist/record" />
                            </template>

                            <div class="grid grid-cols-3 divide-x divide-white/[0.06]">
                                <div class="p-4">
                                    <p class="text-xs text-slate-500">Paper P/L</p>
                                    <p
                                        class="mt-1 text-lg font-black tabular-nums"
                                        :class="buyer_data.watch_record.units > 0 ? 'text-emerald-300' : buyer_data.watch_record.units < 0 ? 'text-rose-300' : 'text-slate-400'"
                                    >
                                        {{ buyer_data.watch_record.units > 0 ? '+' : '' }}{{ buyer_data.watch_record.units.toFixed(2) }}u
                                    </p>
                                </div>
                                <div class="p-4">
                                    <p class="text-xs text-slate-500">Watched</p>
                                    <p class="mt-1 text-lg font-black text-white tabular-nums">{{ buyer_data.watch_record.settled_count }}</p>
                                </div>
                                <div class="p-4">
                                    <p class="text-xs text-slate-500">Breakdown</p>
                                    <p class="mt-1 text-lg font-black tabular-nums">
                                        <span class="text-emerald-300">{{ buyer_data.watch_record.won_count }}W</span>
                                        <span class="mx-1 text-slate-600">·</span>
                                        <span class="text-rose-300">{{ buyer_data.watch_record.lost_count }}L</span>
                                        <span class="mx-1 text-slate-600">·</span>
                                        <span class="text-slate-400">{{ buyer_data.watch_record.voided_count }}V</span>
                                    </p>
                                </div>
                            </div>

                            <div v-if="buyer_data.watch_record.sellers.length > 0" class="border-t border-white/[0.06]">
                                <div class="divide-y divide-white/[0.05]">
                                    <div v-for="s in buyer_data.watch_record.sellers.slice(0, 3)" :key="s.seller_id" class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03]">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-200">{{ s.seller_name }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">{{ s.settled_count }} settled · {{ s.won_count }}W · {{ s.lost_count }}L</p>
                                        </div>
                                        <span class="text-sm font-black tabular-nums" :class="s.units > 0 ? 'text-emerald-300' : s.units < 0 ? 'text-rose-300' : 'text-slate-400'">
                                            {{ s.units > 0 ? '+' : '' }}{{ s.units.toFixed(2) }}u
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </Panel>

                        <Panel v-else title="WATCH RECORD" accent="purple">
                            <template #actions>
                                <ViewAllLink href="/watchlist/record" />
                            </template>
                            <EmptyState
                                title="No watch history yet"
                                body="Every slip you watch is scored here as a paper P/L, so you can compare tipsters before you commit real money."
                                cta-label="Find slips to watch"
                                cta-href="/marketplace"
                                accent="purple"
                            />
                        </Panel>
                    </template>

                    <!-- ═══════════ FOLLOWING ═══════════ -->
                    <template v-else-if="activeTab === 'following'">
                        <section class="rounded-3xl border border-purple-400/20 bg-gradient-to-br from-purple-500/10 to-[#0b1220] p-6 text-center">
                            <div class="flex items-center justify-end">
                                <ViewAllLink href="/buyer/following" :count="buyer_data.following_stats?.total ?? 0" :cap="10" />
                            </div>
                            <p class="text-sm text-slate-400">Sellers you follow</p>
                            <p class="mt-1 text-5xl font-black tracking-tight text-white tabular-nums">{{ buyer_data.following_stats?.total ?? 0 }}</p>
                            <p class="mx-auto mt-3 max-w-md text-sm leading-relaxed text-slate-500">Followed sellers appear here and their new slips show first in your marketplace.</p>
                        </section>

                        <FollowingTable v-if="buyer_data.following_stats && buyer_data.following_stats.total > 0" :following="buyer_data.following_stats.recent" title="Recently Followed" />

                        <EmptyState
                            v-else
                            title="You're not following anyone yet"
                            body="Follow tipsters to see their slips first and track their performance over time."
                            cta-label="Browse marketplace"
                            cta-href="/marketplace"
                            accent="purple"
                        />
                    </template>

                    <!-- ═══════════ WALLET ═══════════ -->
                    <template v-else-if="activeTab === 'wallet'">
                        <section class="relative overflow-hidden rounded-3xl border border-amber-400/20 bg-gradient-to-br from-amber-500/10 via-[#0b1220] to-[#0b1220] p-5 sm:p-6">
                            <div class="pointer-events-none absolute -top-20 -right-16 h-52 w-52 rounded-full bg-amber-400/10 blur-3xl" aria-hidden="true"></div>

                            <div class="relative flex items-center justify-between">
                                <p class="text-sm text-slate-400">Liquid available balance</p>
                                <div class="flex items-center gap-2">
                                    <AsOf :at="generatedAt" />
                                    <span class="text-xs font-semibold text-slate-500">{{ buyer_data.wallet.currency }}</span>
                                </div>
                            </div>
                            <div class="relative mt-1 text-4xl font-black tracking-tight text-white tabular-nums sm:text-5xl">
                                <LiveValue :initial="Number(buyer_data.wallet.balance)" :currency="buyer_data.wallet.currency" />
                            </div>

                            <div class="relative mt-5 flex flex-col gap-2 sm:flex-row">
                                <DepositPopover :currency="'KES'" :initial_amount="100" :initial_phone="buyer_data?.user?.phone ?? ''" />
                                <WithdrawalPopover
                                    :currency="buyer_data.wallet.currency"
                                    :available-balance="Number(buyer_data.wallet.balance)"
                                    :disabled="Number(buyer_data.wallet.balance) <= 0"
                                    class="flex-1"
                                />
                            </div>

                            <dl class="relative mt-5 grid grid-cols-1 gap-2 sm:grid-cols-3">
                                <div class="flex items-center justify-between rounded-2xl bg-black/25 p-3.5 sm:block">
                                    <dt class="text-sm text-slate-500">Pending escrow</dt>
                                    <dd class="text-base font-bold text-amber-300 tabular-nums sm:mt-1"><Money :value="buyer_data.wallet.escrow_balance" :currency="buyer_data.wallet.currency" /></dd>
                                </div>
                                <div class="flex items-center justify-between rounded-2xl bg-black/25 p-3.5 sm:block">
                                    <dt class="text-sm text-slate-500">Total deposited</dt>
                                    <dd class="text-base font-bold text-sky-300 tabular-nums sm:mt-1"><Money :value="buyer_data.wallet.total_deposited" :currency="buyer_data.wallet.currency" /></dd>
                                </div>
                                <div class="flex items-center justify-between rounded-2xl bg-black/25 p-3.5 sm:block">
                                    <dt class="text-sm text-slate-500">Total withdrawn</dt>
                                    <dd class="text-base font-bold text-rose-300 tabular-nums sm:mt-1"><Money :value="buyer_data.wallet.total_withdrawn" :currency="buyer_data.wallet.currency" /></dd>
                                </div>
                            </dl>
                        </section>

                        <Panel title="TRANSACTIONS" accent="slate">
                            <template #actions>
                                <ComparisonPicker v-model="period" :periods="periods" />
                                <ViewAllLink href="/wallet/transactions" :count="buyer_data.wallet.recent_transactions.length" :cap="10" />
                            </template>
                            <TransactionsTable :transactions="filteredTransactions" />
                        </Panel>
                    </template>

                    <!-- ═══════════ ANALYTICS ═══════════ -->
                    <template v-else-if="activeTab === 'analytics'">
                        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                            <Panel title="WIN RATE OVER TIME" accent="sky">
                                <div class="space-y-4 p-4">
                                    <div v-for="(rate, label) in buyer_data.performance.win_rate_breakdown" :key="label" class="space-y-1.5">
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

                            <Panel title="PURCHASE STATUS" accent="amber">
                                <div class="space-y-4 p-4">
                                    <div v-for="status in buyer_data.charts.status_distribution" :key="status.status" class="space-y-1.5">
                                        <div class="flex items-center justify-between text-sm text-slate-400">
                                            <span class="capitalize">{{ status.status }}</span>
                                            <span class="font-bold text-amber-300 tabular-nums">{{ status.count }}</span>
                                        </div>
                                        <div class="h-2 w-full overflow-hidden rounded-full bg-white/[0.06]">
                                            <div
                                                class="h-full rounded-full bg-amber-400"
                                                :style="{ width: `${buyer_data.purchases.total > 0 ? (status.count / buyer_data.purchases.total) * 100 : 0}%` }"
                                            ></div>
                                        </div>
                                    </div>
                                </div>
                            </Panel>

                            <Panel title="TOP SELLERS" accent="purple">
                                <div class="space-y-3 p-4">
                                    <div v-for="seller in buyer_data.charts.seller_performance" :key="seller.seller_name" class="flex items-center justify-between border-b border-white/[0.05] pb-3 last:border-0 last:pb-0">
                                        <div class="min-w-0">
                                            <span class="text-sm font-bold text-slate-200">{{ seller.seller_name }}</span>
                                            <span class="ml-2 text-xs text-slate-500">({{ seller.total }} purchases)</span>
                                        </div>
                                        <span class="rounded-lg bg-purple-400/10 px-2 py-1 text-xs font-black text-purple-300 tabular-nums">{{ seller.win_rate }}% WR</span>
                                    </div>
                                    <p v-if="buyer_data.charts.seller_performance.length === 0" class="py-2 text-center text-sm text-slate-500">No seller data available</p>
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
import SellerProgressCard from '@/components/SellerProgressCard.vue';
import BuyerTabs from '@/components/BuyerTabs.vue';
import BetslipsTable from '@/components/BetslipsTable.vue';
import FollowingTable from '@/components/FollowingTable.vue';
import InfoPopover from '@/components/InfoPopover.vue';
import TransactionsTable from '@/components/TransactionsTable.vue';
import DepositPopover from '@/components/DepositPopover.vue';
import WithdrawalPopover from '@/components/WithdrawalPopover.vue';
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
import { useOnboarding } from '@/composables/useOnboarding';
import { usePeriod } from '@/composables/usePeriod';

const page = usePage();
const buyer_data = page.props.buyer_data;

const {
    isFresh,
    shouldShowBanner,
    dismiss: dismissOnboarding,
} = useOnboarding(buyer_data, { role: 'buyer' });

const buyerOnboardingSteps = [
    { title: 'Browse marketplace', body: 'Find a betslip that catches your eye', href: '/marketplace' },
    { title: 'Buy your first slip', body: 'Escrow protects your money until settlement', href: '/marketplace' },
    { title: 'Watch a seller', body: 'Follow tipsters to see their slips first', href: '/marketplace' },
    { title: 'Fund your wallet', body: 'Deposit via M-Pesa in seconds', href: '#wallet' },
];

const {
    period,
    periods,
    shortLabel: shortPeriodLabel,
    filterByDate,
} = usePeriod('buyer', '30d');

const periodMetrics = computed(() => {
    const fromBackend = buyer_data.periods?.[period.value];
    if (fromBackend) {
        return {
            win_rate: fromBackend.win_rate ?? 0,
            refund_rate: fromBackend.refund_rate ?? 0,
            total_spent: fromBackend.total_spent ?? 0,
            net_spent: fromBackend.net_spent ?? 0,
            total_purchases: fromBackend.total_purchases ?? 0,
            avg_price: fromBackend.avg_price ?? 0,
        };
    }
    return {
        win_rate: buyer_data.performance.win_rate,
        refund_rate: buyer_data.performance.refund_rate,
        total_spent: buyer_data.financial.total_spent,
        net_spent: buyer_data.financial.net_spent,
        total_purchases: buyer_data.performance.total_purchases,
        avg_price: buyer_data.performance.avg_price,
    };
});

const periodDeltas = computed(() => {
    const c = buyer_data.periods?.[period.value]?.comparison;
    return {
        win_rate_delta: c?.win_rate_delta ?? 0,
        refund_rate_delta: c?.refund_rate_delta ?? 0,
        total_spent_delta: c?.total_spent_delta ?? 0,
        total_purchases_delta: c?.total_purchases_delta ?? 0,
        avg_price_delta: c?.avg_price_delta ?? 0,
    };
});

const filteredTransactions = computed(() =>
    filterByDate(buyer_data.wallet.recent_transactions, 'created_at'),
);

const filteredOutcomes = computed(() =>
    filterByDate(buyer_data.settled_outcomes, 'settled_at'),
);

const filteredActivity = computed(() =>
    filterByDate(buyer_data.activity, 'created_at'),
);

const winRateSeries = computed(() => {
    if (buyer_data.performance?.series?.win_rate_7d) {
        return buyer_data.performance.series.win_rate_7d;
    }
    const b = buyer_data.performance.win_rate_breakdown ?? {};
    return [b.all_time, b.last_90_days, b.last_30_days, b.last_7_days].filter(
        (n) => typeof n === 'number' && !Number.isNaN(n),
    );
});

const spentSeries = computed(() => {
    if (buyer_data.performance?.series?.spent_7d) {
        return buyer_data.performance.series.spent_7d;
    }
    const txs = buyer_data.wallet.recent_transactions ?? [];
    const days = 7;
    const buckets = new Array(days).fill(0);
    const now = Date.now();
    const dayMs = 24 * 60 * 60 * 1000;

    txs.forEach((tx) => {
        if (tx.type !== 'purchase') return;
        const t = new Date(tx.created_at).getTime();
        if (Number.isNaN(t)) return;
        const diff = now - t;
        const idx = days - 1 - Math.floor(diff / dayMs);
        if (idx >= 0 && idx < days) buckets[idx] += Math.abs(tx.amount);
    });

    return buckets.some((v) => v > 0) ? buckets : [];
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
    () => buyer_data.generated_at ?? new Date().toISOString(),
);

const TAB_KEYS = [
    'overview',
    'purchases',
    'contests',
    'watchlist',
    'following',
    'wallet',
    'analytics',
];

const tabs = computed(() => [
    { key: 'overview', label: 'Overview', shortLabel: 'Home', accent: 'sky' },
    {
        key: 'purchases',
        label: 'Purchases',
        shortLabel: 'Buys',
        accent: 'emerald',
        badge: buyer_data.purchases.active || null,
    },
    {
        key: 'contests',
        label: 'Contests',
        shortLabel: 'Contest',
        accent: 'amber',
        badge: buyer_data.contests?.length || null,
    },
    {
        key: 'watchlist',
        label: 'Watchlist',
        shortLabel: 'Watch',
        accent: 'purple',
        badge: buyer_data.watch_record?.settled_count || null,
    },
    {
        key: 'following',
        label: 'Following',
        shortLabel: 'Follows',
        accent: 'purple',
        badge: buyer_data.following_stats?.total || null,
    },
    { key: 'wallet', label: 'Wallet', shortLabel: 'Wallet', accent: 'amber' },
    {
        key: 'analytics',
        label: 'Analytics',
        shortLabel: 'Stats',
        accent: 'slate',
    },
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

const contestHref = (contest) => `/contests/${contest.uuid}/picks`;

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
        case 'pending': return 'bg-amber-500 text-[#070b14]';
        case 'neutral':
        default: return 'bg-slate-500 text-white';
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