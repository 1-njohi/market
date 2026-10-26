<template>
    <Head title="Play" />

    <div class="flex min-h-screen flex-col bg-[#070b14] px-4 pt-6 pb-28 font-sans text-slate-200 selection:bg-amber-500/30 selection:text-white lg:px-8 lg:py-10">
        <div class="mx-auto w-full max-w-7xl space-y-6">
            <!-- ═══════════════ HEADER ═══════════════ -->
            <header class="flex w-full items-center justify-between gap-3">
                <div class="flex min-w-0 items-center gap-3.5">
                    <div class="relative shrink-0">
                        <img
                            :src="play_data.user.avatar"
                            :alt="play_data.user.name"
                            class="h-12 w-12 rounded-full bg-[#111622] object-cover ring-2 ring-white/10 md:h-14 md:w-14"
                        />
                        <span
                            v-if="play_data.user.is_verified"
                            class="absolute -right-0.5 -bottom-0.5 flex h-5 w-5 items-center justify-center rounded-full border-2 border-[#070b14] bg-amber-400 text-[10px] font-black text-[#070b14]"
                            title="Verified"
                        >✓</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold text-amber-400">Player</p>
                        <h1 class="truncate text-xl font-black tracking-tight text-white md:text-2xl">{{ play_data.user.name }}</h1>
                        <p class="truncate text-xs text-slate-500">Code {{ play_data.user.code }} · Joined {{ play_data.user.joined_ago }}</p>
                    </div>
                </div>

                <div class="flex shrink-0 items-center gap-2">
                    <AsOf :at="play_data.generated_at" class="hidden lg:inline" />
                    <DashboardSwitcher active="play" />
                </div>
            </header>

            <!-- ═══════════════ TAB STRIP (desktop) ═══════════════ -->
            <div class="hidden lg:flex">
                <PlayTabs v-model="activeTab" :tabs="tabs" />
            </div>

            <Transition appear enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 translate-y-1" enter-to-class="opacity-100 translate-y-0">
                <div :key="activeTab" class="space-y-6">
                    <!-- ═══════════ OVERVIEW ═══════════ -->
                    <template v-if="activeTab === 'overview'">
                        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                            <!-- Your standing: the number you're playing for -->
                            <section class="relative overflow-hidden rounded-3xl border border-amber-400/20 bg-gradient-to-br from-amber-500/15 via-[#0b1220] to-[#0b1220] p-5 sm:p-6">
                                <div class="pointer-events-none absolute -top-20 -right-16 h-52 w-52 rounded-full bg-amber-400/15 blur-3xl" aria-hidden="true"></div>

                                <p class="relative text-sm text-slate-400">Reputation</p>
                                <p class="relative mt-1 flex items-baseline gap-2">
                                    <span
                                        class="text-5xl font-black tracking-tight tabular-nums"
                                        :class="play_data.overview.stats.reputation_points > 0 ? 'text-emerald-300' : play_data.overview.stats.reputation_points < 0 ? 'text-rose-300' : 'text-white'"
                                    >{{ signed(play_data.overview.stats.reputation_points) }}</span>
                                    <span class="text-base font-bold text-slate-500">pts</span>
                                </p>

                                <div class="relative mt-5 rounded-2xl bg-black/25 p-4">
                                    <p class="text-xs text-slate-500">Accuracy</p>
                                    <p class="mt-0.5 text-2xl font-black text-sky-300 tabular-nums">
                                        {{ play_data.overview.stats.accuracy !== null ? play_data.overview.stats.accuracy + '%' : '—' }}
                                    </p>
                                </div>
                            </section>

                            <!-- Where you're playing: tap a tile to jump to that tab -->
                            <section class="grid grid-cols-2 gap-3 lg:col-span-2">
                                <button
                                    type="button"
                                    @click="activeTab = 'betslips'"
                                    class="group cursor-pointer rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-emerald-400/40 focus-visible:ring-2 focus-visible:ring-emerald-300 focus-visible:outline-none active:scale-[0.98]"
                                >
                                    <p class="text-sm text-slate-400">Active slips</p>
                                    <p class="mt-1 text-4xl font-black text-emerald-300 tabular-nums">{{ play_data.overview.stats.active_betslips }}</p>
                                    <p class="mt-2 text-xs font-semibold text-slate-500 transition-colors group-hover:text-emerald-300">View slips →</p>
                                </button>

                                <button
                                    type="button"
                                    @click="activeTab = 'matchdays'"
                                    class="group cursor-pointer rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-rose-400/40 focus-visible:ring-2 focus-visible:ring-rose-300 focus-visible:outline-none active:scale-[0.98]"
                                >
                                    <p class="text-sm text-slate-400">Open matchdays</p>
                                    <p class="mt-1 text-4xl font-black text-rose-300 tabular-nums">{{ play_data.overview.stats.open_matchdays }}</p>
                                    <p class="mt-2 text-xs font-semibold text-slate-500 transition-colors group-hover:text-rose-300">Make your picks →</p>
                                </button>

                                <button
                                    type="button"
                                    @click="activeTab = 'contests'"
                                    class="group cursor-pointer rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-amber-400/40 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-[0.98]"
                                >
                                    <p class="text-sm text-slate-400">Contests</p>
                                    <p class="mt-1 text-4xl font-black text-amber-300 tabular-nums">{{ play_data.overview.stats.created_contests + play_data.overview.stats.entered_contests }}</p>
                                    <p class="mt-2 text-xs font-semibold text-slate-500 transition-colors group-hover:text-amber-300">
                                        {{ play_data.overview.stats.created_contests }} hosted · {{ play_data.overview.stats.entered_contests }} entered
                                    </p>
                                </button>

                                <button
                                    type="button"
                                    @click="activeTab = 'jackpots'"
                                    class="group cursor-pointer rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-purple-400/40 focus-visible:ring-2 focus-visible:ring-purple-300 focus-visible:outline-none active:scale-[0.98]"
                                >
                                    <p class="text-sm text-slate-400">Jackpots</p>
                                    <p class="mt-1 text-4xl font-black text-purple-300 tabular-nums">{{ play_data.overview.stats.open_jackpots }}</p>
                                    <p class="mt-2 text-xs font-semibold text-slate-500 transition-colors group-hover:text-purple-300">See open jackpots →</p>
                                </button>
                            </section>
                        </div>

                        <Panel title="NEEDS ATTENTION" accent="amber">
                            <EmptyState
                                v-if="play_data.overview.needs_attention.length === 0"
                                title="You're all caught up"
                                body="No pending picks, entries, or settlement notifications right now."
                                accent="emerald"
                            />
                            <div v-else class="divide-y divide-white/[0.06]">
                                <Link
                                    v-for="item in play_data.overview.needs_attention"
                                    :key="item.key"
                                    :href="item.href"
                                    class="group flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03] focus-visible:bg-white/[0.04] focus-visible:outline-none"
                                >
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="h-2 w-2 shrink-0 rounded-full bg-amber-400 shadow-[0_0_10px_rgba(251,191,36,0.7)]" aria-hidden="true"></span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-100">{{ item.title }}</p>
                                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ item.body }}</p>
                                        </div>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-4 w-4 shrink-0 text-slate-500 transition-transform duration-200 group-hover:translate-x-1 group-hover:text-amber-300" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                            </div>
                        </Panel>

                        <!-- Seller progress: only when ineligible -->
                        <SellerProgressCard v-if="play_data.seller_status === 'ineligible'" :progress="play_data.seller_progress" :status="play_data.seller_status" />

                        <!-- Buyer dashboard teaser: only before first purchase -->
                        <section v-if="!play_data.user.has_purchased" class="relative overflow-hidden rounded-3xl border border-sky-400/25 bg-gradient-to-br from-sky-500/15 via-[#0b1220] to-[#0b1220] p-5 sm:p-6">
                            <div class="pointer-events-none absolute -top-16 -left-12 h-44 w-44 rounded-full bg-sky-400/15 blur-3xl" aria-hidden="true"></div>
                            <div class="relative flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold text-sky-300">Buyer dashboard</p>
                                    <p class="mt-1 text-lg font-bold text-white">It unlocks after your first purchase</p>
                                    <p class="mt-1 max-w-xl text-sm leading-relaxed text-slate-400">Once you buy a betslip, your buyer view will appear in the switcher — live tracking, settlement results, and buyer-side analytics.</p>
                                </div>
                                <Link
                                    href="/marketplace"
                                    class="inline-flex min-h-12 shrink-0 items-center gap-2 rounded-xl bg-sky-400 px-6 text-sm font-black text-[#070b14] transition-all duration-200 hover:bg-sky-300 focus-visible:ring-2 focus-visible:ring-sky-200 focus-visible:outline-none active:scale-[0.98]"
                                >
                                    Browse marketplace
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                            </div>
                        </section>
                    </template>

                    <!-- ═══════════ BETSLIPS ═══════════ -->
                    <template v-else-if="activeTab === 'betslips'">
                        <Panel :title="`Active Slips [${play_data.betslips.counts.active}]`" accent="emerald">
                            <EmptyState
                                v-if="play_data.betslips.active.length === 0"
                                title="No active slips"
                                body="Betslips you create for yourself live here so you can track them live through to settlement."
                                cta-label="Build a betslip"
                                cta-href="/betslip/confirm"
                                accent="emerald"
                            />
                            <div v-else class="divide-y divide-white/[0.06]">
                                <div v-for="b in play_data.betslips.active" :key="b.id" class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03]">
                                    <div class="min-w-0">
                                        <p class="truncate font-mono text-sm font-bold text-emerald-300">{{ b.code }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ b.legs_count }} legs</p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-3">
                                        <p class="text-base font-black text-amber-300 tabular-nums">{{ b.total_odds.toFixed(2) }}</p>
                                        <span class="rounded-full bg-amber-400/10 px-2.5 py-1 text-xs font-bold text-amber-300 capitalize">{{ b.status }}</span>
                                    </div>
                                </div>
                            </div>
                        </Panel>

                        <Panel :title="`Settled Slips [${play_data.betslips.counts.settled}]`" accent="slate">
                            <EmptyState
                                v-if="play_data.betslips.recent_settled.length === 0"
                                title="No settled slips yet"
                                body="Once your slips settle, your wins and losses show up here."
                                accent="slate"
                            />
                            <div v-else class="divide-y divide-white/[0.06]">
                                <div v-for="b in play_data.betslips.recent_settled" :key="b.id" class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03]">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span
                                            :class="[
                                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-sm font-black select-none',
                                                b.is_winner ? 'bg-emerald-400 text-[#070b14]' : 'bg-rose-400 text-white',
                                            ]"
                                        >
                                            {{ b.is_winner ? 'W' : 'L' }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-mono text-sm font-bold text-slate-200">{{ b.code }}</p>
                                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ b.legs_count }} legs</p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-3">
                                        <p class="text-base font-black tabular-nums" :class="b.is_winner ? 'text-emerald-300' : 'text-slate-500'">{{ b.total_odds.toFixed(2) }}</p>
                                        <span :class="['rounded-full px-2.5 py-1 text-xs font-bold', b.is_winner ? 'bg-emerald-400/10 text-emerald-300' : 'bg-rose-400/10 text-rose-300']">{{ b.is_winner ? 'Won' : 'Lost' }}</span>
                                    </div>
                                </div>
                            </div>
                        </Panel>
                    </template>

                    <!-- ═══════════ CONTESTS ═══════════ -->
                    <template v-else-if="activeTab === 'contests'">
                        <Panel :title="`Created Contests [${play_data.contests.created.length}]`" accent="amber">
                            <EmptyState
                                v-if="play_data.contests.created.length === 0"
                                title="No contests created"
                                body="Host a contest, invite friends, and see who calls the best picks."
                                cta-label="Create a contest"
                                cta-href="/contests/create"
                                accent="amber"
                            />
                            <div v-else class="divide-y divide-white/[0.06]">
                                <Link
                                    v-for="c in play_data.contests.created"
                                    :key="c.id"
                                    :href="`/contests/${c.uuid}/manage`"
                                    class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03] focus-visible:bg-white/[0.04] focus-visible:outline-none"
                                >
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex h-7 shrink-0 items-center justify-center rounded-lg bg-purple-500 px-2 text-xs font-black text-white">Host</span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-100">{{ c.name }}</p>
                                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                                {{ c.accepted_entries }} accepted
                                                <span v-if="c.pending_entries > 0" class="font-bold text-amber-300">· {{ c.pending_entries }} pending</span>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-white/[0.06] px-2.5 py-1 text-xs font-bold text-slate-300 capitalize">{{ c.status }}</span>
                                </Link>
                            </div>
                        </Panel>

                        <Panel :title="`Entered Contests [${play_data.contests.entered.length}]`" accent="sky">
                            <EmptyState
                                v-if="play_data.contests.entered.length === 0"
                                title="Not in any contests yet"
                                body="Join a contest to compete on picks and climb the leaderboard."
                                cta-label="Browse contests"
                                cta-href="/contests/mine"
                                accent="sky"
                            />
                            <div v-else class="divide-y divide-white/[0.06]">
                                <Link
                                    v-for="c in play_data.contests.entered"
                                    :key="c.id"
                                    :href="`/contests/${c.uuid}/picks`"
                                    class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03] focus-visible:bg-white/[0.04] focus-visible:outline-none"
                                >
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span class="flex h-7 shrink-0 items-center justify-center rounded-lg bg-sky-400 px-2 text-xs font-black text-[#070b14]">Play</span>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-100">{{ c.name }}</p>
                                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                                {{ c.picks_submitted }}/{{ c.legs_count }} picks
                                                <span v-if="c.is_locked" class="font-bold text-rose-300">· locked</span>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-white/[0.06] px-2.5 py-1 text-xs font-bold text-slate-300 capitalize">{{ c.entry_status }}</span>
                                </Link>
                            </div>
                        </Panel>
                    </template>

                    <!-- ═══════════ MATCHDAYS ═══════════ -->
                    <template v-else-if="activeTab === 'matchdays'">
                        <Panel :title="`Open Matchdays [${play_data.matchdays.open.length}]`" accent="rose">
                            <template #actions>
                                <Link href="/matchday" class="inline-flex min-h-9 items-center rounded-lg px-3 text-sm font-bold text-sky-300 transition-colors hover:bg-sky-400/10 hover:text-sky-200 focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none">Full view →</Link>
                            </template>
                            <EmptyState
                                v-if="play_data.matchdays.open.length === 0"
                                title="No open matchdays"
                                body="The next matchday is coming. Check back when the next round of fixtures drops."
                                accent="rose"
                            />
                            <div v-else class="divide-y divide-white/[0.06]">
                                <Link
                                    v-for="m in play_data.matchdays.open"
                                    :key="m.uuid"
                                    :href="`/contests/${m.uuid}/picks`"
                                    class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03] focus-visible:bg-white/[0.04] focus-visible:outline-none"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-100">MD{{ m.matchday_number }} · {{ m.name }}</p>
                                        <div class="mt-2 flex items-center gap-2.5">
                                            <div class="h-1.5 w-24 overflow-hidden rounded-full bg-white/[0.08]" aria-hidden="true">
                                                <div class="h-full rounded-full bg-gradient-to-r from-rose-400 to-amber-300" :style="{ width: (m.picks_required > 0 ? Math.min(100, (m.picks_submitted / m.picks_required) * 100) : 0) + '%' }"></div>
                                            </div>
                                            <p class="truncate text-xs text-slate-500 tabular-nums">
                                                {{ m.picks_submitted }}/{{ m.picks_required }} picks
                                                <span v-if="m.is_locked" class="font-bold text-rose-300">· locked</span>
                                                <span v-else-if="m.is_entered" class="font-bold text-amber-300">· in progress</span>
                                            </p>
                                        </div>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-white/[0.06] px-2.5 py-1 text-xs font-bold text-slate-300 capitalize">{{ m.status }}</span>
                                </Link>
                            </div>
                        </Panel>

                        <Panel :title="`Your Sub-Leagues [${play_data.matchdays.sub_leagues.length}]`" accent="sky">
                            <EmptyState
                                v-if="play_data.matchdays.sub_leagues.length === 0"
                                title="No sub-leagues yet"
                                body="Join or create a sub-league to compete with a small, private group of friends."
                                cta-label="Open matchday"
                                cta-href="/matchday"
                                accent="sky"
                            />
                            <div v-else class="divide-y divide-white/[0.06]">
                                <div v-for="s in play_data.matchdays.sub_leagues" :key="s.id" class="p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-bold text-slate-100">{{ s.name }}</p>
                                            <p class="mt-0.5 text-xs text-slate-500">
                                                {{ s.member_count }}/{{ s.max_members }} members
                                                <span v-if="s.is_owner" class="font-bold text-amber-300">· owner</span>
                                            </p>
                                        </div>
                                        <Link
                                            :href="`/matchday/sub-leagues/${s.id}/leaderboard`"
                                            class="inline-flex min-h-9 shrink-0 items-center rounded-lg px-3 text-sm font-bold text-sky-300 transition-colors hover:bg-sky-400/10 hover:text-sky-200 focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none"
                                        >Board →</Link>
                                    </div>
                                    <ol v-if="s.leaderboard_preview.length > 0" class="mt-3 space-y-1.5 rounded-2xl bg-black/25 p-3">
                                        <li v-for="(row, i) in s.leaderboard_preview" :key="i" class="flex items-center justify-between gap-3 text-sm">
                                            <span class="flex min-w-0 items-center gap-2.5">
                                                <span
                                                    class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg text-xs font-black tabular-nums"
                                                    :class="i === 0 ? 'bg-amber-400 text-[#070b14]' : 'bg-white/[0.07] text-slate-400'"
                                                >{{ i + 1 }}</span>
                                                <span class="truncate" :class="i === 0 ? 'font-bold text-white' : 'text-slate-300'">{{ row.name ?? '—' }}</span>
                                            </span>
                                            <span class="shrink-0 font-bold text-slate-200 tabular-nums">{{ row.points ?? '' }}</span>
                                        </li>
                                    </ol>
                                </div>
                            </div>
                        </Panel>

                        <section v-if="play_data.matchdays.summary" class="grid grid-cols-2 gap-3">
                            <div class="rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5">
                                <p class="text-sm text-slate-400">Matchdays settled</p>
                                <p class="mt-1 text-4xl font-black text-white tabular-nums">{{ play_data.matchdays.summary.matchdays_settled }}</p>
                            </div>
                            <div class="rounded-3xl border border-amber-400/20 bg-gradient-to-br from-amber-500/10 to-[#0b1220] p-5">
                                <p class="text-sm text-slate-400">Total points</p>
                                <p class="mt-1 text-4xl font-black text-amber-300 tabular-nums">{{ signed(play_data.matchdays.summary.total_points ?? 0) }}</p>
                            </div>
                        </section>
                    </template>

                    <!-- ═══════════ JACKPOTS ═══════════ -->
                    <template v-else-if="activeTab === 'jackpots'">
                        <Panel :title="`Open Jackpots [${play_data.jackpots.open.length}]`" accent="purple">
                            <template #actions>
                                <Link href="/jackpots" class="inline-flex min-h-9 items-center rounded-lg px-3 text-sm font-bold text-sky-300 transition-colors hover:bg-sky-400/10 hover:text-sky-200 focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none">All jackpots →</Link>
                            </template>
                            <EmptyState
                                v-if="play_data.jackpots.open.length === 0"
                                title="No open jackpots"
                                body="Jackpots open around big fixture blocks. Check back soon."
                                accent="purple"
                            />
                            <div v-else class="divide-y divide-white/[0.06]">
                                <Link
                                    v-for="j in play_data.jackpots.open"
                                    :key="j.uuid"
                                    :href="`/jackpots/${j.uuid}`"
                                    class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03] focus-visible:bg-white/[0.04] focus-visible:outline-none"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-100">{{ j.name }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ j.legs_count }} legs · {{ j.participant_count }} entered</p>
                                    </div>
                                    <span :class="['shrink-0 rounded-full px-2.5 py-1 text-xs font-bold', j.is_locked ? 'bg-rose-400/10 text-rose-300' : 'bg-emerald-400/10 text-emerald-300']">{{ j.is_locked ? 'Locked' : 'Open' }}</span>
                                </Link>
                            </div>
                        </Panel>

                        <Panel :title="`Recent Jackpots [${play_data.jackpots.recent.length}]`" accent="slate">
                            <EmptyState
                                v-if="play_data.jackpots.recent.length === 0"
                                title="No settled jackpots yet"
                                body="Settled jackpot results show up here once the round ends."
                                accent="slate"
                            />
                            <div v-else class="divide-y divide-white/[0.06]">
                                <Link
                                    v-for="j in play_data.jackpots.recent"
                                    :key="j.uuid"
                                    :href="`/jackpots/${j.uuid}`"
                                    class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03] focus-visible:bg-white/[0.04] focus-visible:outline-none"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-100">{{ j.name }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ j.legs_count }} legs · {{ j.participant_count }} played</p>
                                    </div>
                                    <span class="shrink-0 rounded-full bg-white/[0.06] px-2.5 py-1 text-xs font-bold text-slate-300">Settled</span>
                                </Link>
                            </div>
                        </Panel>
                    </template>

                    <!-- ═══════════ PORTFOLIO ═══════════ -->
                    <template v-else-if="activeTab === 'portfolio'">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <section class="relative overflow-hidden rounded-3xl border border-amber-400/20 bg-gradient-to-br from-amber-500/15 via-[#0b1220] to-[#0b1220] p-5 sm:p-6 md:col-span-2">
                                <div class="pointer-events-none absolute -top-20 -right-16 h-52 w-52 rounded-full bg-amber-400/15 blur-3xl" aria-hidden="true"></div>

                                <p class="relative text-sm text-slate-400">Current reputation</p>
                                <p class="relative mt-1 flex items-baseline gap-2">
                                    <span
                                        class="text-5xl font-black tracking-tight tabular-nums sm:text-6xl"
                                        :class="play_data.portfolio.stats.current_points > 0 ? 'text-emerald-300' : play_data.portfolio.stats.current_points < 0 ? 'text-rose-300' : 'text-white'"
                                    >{{ signed(play_data.portfolio.stats.current_points) }}</span>
                                    <span class="text-base font-bold text-slate-500">pts</span>
                                </p>

                                <div class="relative mt-5 flex items-center justify-between rounded-2xl bg-black/25 px-4 py-3.5">
                                    <div>
                                        <p class="text-xs text-slate-500">Global rank</p>
                                        <p class="text-xs text-slate-600">of {{ play_data.portfolio.stats.total_ranked }} ranked</p>
                                    </div>
                                    <p class="text-3xl font-black text-white tabular-nums">
                                        <template v-if="play_data.portfolio.stats.global_rank">#{{ play_data.portfolio.stats.global_rank }}</template>
                                        <template v-else>—</template>
                                    </p>
                                </div>
                            </section>

                            <section class="rounded-3xl border border-white/[0.07] bg-[#0b1220] p-5 sm:p-6">
                                <p class="text-sm text-slate-400">Lifetime accuracy</p>
                                <p class="mt-1 text-5xl font-black tracking-tight text-sky-300 tabular-nums">
                                    <template v-if="play_data.portfolio.stats.accuracy !== null">{{ play_data.portfolio.stats.accuracy }}%</template>
                                    <template v-else>—</template>
                                </p>
                                <p class="mt-3 text-sm text-slate-500">Based on settled picks only</p>
                            </section>
                        </div>

                        <Panel title="POINT LEDGER" accent="slate">
                            <template #actions>
                                <Link href="/portfolio" class="inline-flex min-h-9 items-center rounded-lg px-3 text-sm font-bold text-sky-300 transition-colors hover:bg-sky-400/10 hover:text-sky-200 focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none">Full history →</Link>
                            </template>
                            <EmptyState
                                v-if="play_data.portfolio.logs.length === 0"
                                title="No prediction history yet"
                                body="Make a pick to start building your reputation."
                                cta-label="Browse fixtures"
                                cta-href="/fixtures"
                                accent="slate"
                            />
                            <div v-else class="divide-y divide-white/[0.06]">
                                <div v-for="log in play_data.portfolio.logs" :key="log.id" class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03]">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-200">{{ log.description }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500 capitalize">{{ log.created_ago }} · {{ log.type }}</p>
                                    </div>
                                    <span
                                        :class="['shrink-0 rounded-lg px-2.5 py-1 text-sm font-black tabular-nums', log.amount > 0 ? 'bg-emerald-400/10 text-emerald-300' : 'bg-rose-400/10 text-rose-300']"
                                    >{{ signed(log.amount) }}</span>
                                </div>
                            </div>
                        </Panel>
                    </template>
                </div>
            </Transition>
        </div>

        <PlayTabs v-model="activeTab" :tabs="tabs" variant="bottom-bar" />
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

import Panel from '@/components/Panel.vue';
import EmptyState from '@/components/EmptyState.vue';
import AsOf from '@/components/AsOf.vue';
import PlayTabs from '@/components/PlayTabs.vue';
import DashboardSwitcher from '@/components/DashboardSwitcher.vue';
import SellerProgressCard from '@/components/SellerProgressCard.vue';

const page = usePage();
const play_data = page.props.play_data;

const TAB_KEYS = ['overview', 'betslips', 'contests', 'matchdays', 'jackpots', 'portfolio'];

const tabs = computed(() => [
    { key: 'overview',  label: 'Overview',  shortLabel: 'Home',     accent: 'sky' },
    { key: 'betslips',  label: 'Betslips',  shortLabel: 'Slips',    accent: 'emerald',
      badge: play_data.overview.stats.active_betslips || null },
    { key: 'contests',  label: 'Contests',  shortLabel: 'Contest',  accent: 'amber',
      badge: (play_data.overview.stats.created_contests + play_data.overview.stats.entered_contests) || null },
    { key: 'matchdays', label: 'Matchdays', shortLabel: 'MD',       accent: 'rose',
      badge: play_data.overview.stats.open_matchdays || null },
    { key: 'jackpots',  label: 'Jackpots',  shortLabel: 'Jackpots', accent: 'purple',
      badge: play_data.overview.stats.open_jackpots || null },
    { key: 'portfolio', label: 'Portfolio', shortLabel: 'Record',   accent: 'slate' },
]);

const readHash = () => {
    const hash = (window.location.hash || '').replace('#', '');
    return TAB_KEYS.includes(hash) ? hash : 'overview';
};

const activeTab = ref(readHash());

const onHashChange = () => { activeTab.value = readHash(); };

onMounted(() => window.addEventListener('hashchange', onHashChange));
onUnmounted(() => window.removeEventListener('hashchange', onHashChange));

watch(activeTab, (val) => {
    if (window.location.hash !== `#${val}`) {
        history.replaceState(null, '', `#${val}`);
    }
    window.scrollTo({ top: 0, behavior: 'smooth' });
});

const signed = (n) => {
    if (n === null || n === undefined) return '—';
    return n > 0 ? `+${n}` : `${n}`;
};
</script>