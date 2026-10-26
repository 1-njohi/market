<template>
    <Head title="Play" />

    <div class="flex min-h-screen flex-col bg-[#0a1628] px-4 pt-6 pb-28 font-sans text-slate-200 selection:bg-amber-500/30 selection:text-white lg:px-8 lg:py-10">
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
                            class="absolute -right-0.5 -bottom-0.5 flex h-5 w-5 items-center justify-center rounded-full border-2 border-[#0a1628] bg-amber-400 text-[10px] font-black text-[#0a1628]"
                            title="Verified"
                        >✓</span>
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-widest text-amber-400">Play</p>
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

            <Transition
                appear
                enter-active-class="transition duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-1"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div :key="activeTab" class="space-y-6">

                    <!-- ═══════════ OVERVIEW ═══════════ -->
                    <template v-if="activeTab === 'overview'">
                        <section class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                            <div class="rounded-2xl border border-white/[0.07] bg-[#111622] p-4">
                                <p class="text-xs text-slate-500">Active slips</p>
                                <p class="mt-1 text-2xl font-black text-emerald-300 tabular-nums">{{ play_data.overview.stats.active_betslips }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/[0.07] bg-[#111622] p-4">
                                <p class="text-xs text-slate-500">Open matchdays</p>
                                <p class="mt-1 text-2xl font-black text-rose-300 tabular-nums">{{ play_data.overview.stats.open_matchdays }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/[0.07] bg-[#111622] p-4">
                                <p class="text-xs text-slate-500">Contests</p>
                                <p class="mt-1 text-2xl font-black text-amber-300 tabular-nums">{{ play_data.overview.stats.created_contests + play_data.overview.stats.entered_contests }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/[0.07] bg-[#111622] p-4">
                                <p class="text-xs text-slate-500">Jackpots</p>
                                <p class="mt-1 text-2xl font-black text-purple-300 tabular-nums">{{ play_data.overview.stats.open_jackpots }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/[0.07] bg-[#111622] p-4">
                                <p class="text-xs text-slate-500">Reputation</p>
                                <p class="mt-1 text-2xl font-black text-white tabular-nums">{{ signed(play_data.overview.stats.reputation_points) }}</p>
                            </div>
                            <div class="rounded-2xl border border-white/[0.07] bg-[#111622] p-4">
                                <p class="text-xs text-slate-500">Accuracy</p>
                                <p class="mt-1 text-2xl font-black text-sky-300 tabular-nums">
                                    {{ play_data.overview.stats.accuracy !== null ? play_data.overview.stats.accuracy + '%' : '—' }}
                                </p>
                            </div>
                        </section>

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
                                    class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03]"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-100">{{ item.title }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ item.body }}</p>
                                    </div>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-4 w-4 shrink-0 text-slate-500" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                            </div>
                        </Panel>

                        <!-- Seller progress — only when ineligible -->
                        <SellerProgressCard
                            v-if="play_data.seller_status === 'ineligible'"
                            :progress="play_data.seller_progress"
                            :status="play_data.seller_status"
                        />

                        <!-- Buyer dashboard teaser — only before first purchase -->
                        <Panel v-if="!play_data.user.has_purchased" title="BUYER DASHBOARD" accent="sky">
                            <div class="flex flex-col items-start gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-slate-100">The buyer dashboard unlocks after your first purchase</p>
                                    <p class="mt-1 text-sm text-slate-400">Once you buy a betslip, your buyer view will appear in the switcher — live tracking, settlement results, and buyer-side analytics.</p>
                                </div>
                                <Link
                                    href="/marketplace"
                                    class="inline-flex min-h-11 shrink-0 items-center gap-2 rounded-xl border border-sky-400/40 bg-sky-400/10 px-5 text-sm font-bold text-sky-200 transition-all hover:bg-sky-400 hover:text-[#0a1628] focus-visible:ring-2 focus-visible:ring-sky-300 focus-visible:outline-none active:scale-[0.98]"
                                >
                                    Browse marketplace
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3.5 w-3.5" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                                    </svg>
                                </Link>
                            </div>
                        </Panel>
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
                                <div
                                    v-for="b in play_data.betslips.active"
                                    :key="b.id"
                                    class="flex items-center justify-between gap-3 p-4"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate font-mono text-sm font-bold text-emerald-300">{{ b.code }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ b.legs_count }} legs · odds {{ b.total_odds.toFixed(2) }}</p>
                                    </div>
                                    <span class="rounded-full bg-amber-400/10 px-2 py-1 text-xs font-bold uppercase tracking-wider text-amber-300">{{ b.status }}</span>
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
                                <div
                                    v-for="b in play_data.betslips.recent_settled"
                                    :key="b.id"
                                    class="flex items-center justify-between gap-3 p-4"
                                >
                                    <div class="flex min-w-0 items-center gap-3">
                                        <span
                                            :class="[
                                                'flex h-8 w-8 shrink-0 items-center justify-center rounded-xl text-sm font-black select-none',
                                                b.is_winner ? 'bg-emerald-400 text-[#0a1628]' : 'bg-rose-400 text-white',
                                            ]"
                                        >
                                            {{ b.is_winner ? 'W' : 'L' }}
                                        </span>
                                        <div class="min-w-0">
                                            <p class="truncate font-mono text-sm font-bold text-slate-200">{{ b.code }}</p>
                                            <p class="mt-0.5 truncate text-xs text-slate-500">{{ b.legs_count }} legs · odds {{ b.total_odds.toFixed(2) }}</p>
                                        </div>
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
                                    class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03]"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-100">{{ c.name }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">
                                            {{ c.accepted_entries }} accepted
                                            <span v-if="c.pending_entries > 0" class="text-amber-300">· {{ c.pending_entries }} pending</span>
                                        </p>
                                    </div>
                                    <span class="rounded-full bg-white/[0.06] px-2 py-1 text-xs font-bold uppercase tracking-wider text-slate-400">{{ c.status }}</span>
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
                                    class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03]"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-100">{{ c.name }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">
                                            {{ c.picks_submitted }}/{{ c.legs_count }} picks
                                            <span v-if="c.is_locked" class="text-rose-300">· locked</span>
                                        </p>
                                    </div>
                                    <span class="rounded-full bg-white/[0.06] px-2 py-1 text-xs font-bold uppercase tracking-wider text-slate-400">{{ c.entry_status }}</span>
                                </Link>
                            </div>
                        </Panel>
                    </template>

                    <!-- ═══════════ MATCHDAYS ═══════════ -->
                    <template v-else-if="activeTab === 'matchdays'">
                        <Panel :title="`Open Matchdays [${play_data.matchdays.open.length}]`" accent="rose">
                            <template #actions>
                                <Link href="/matchday" class="text-xs font-bold uppercase tracking-wider text-sky-400 hover:text-sky-300">Full view →</Link>
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
                                    class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03]"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-100">MD{{ m.matchday_number }} · {{ m.name }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">
                                            {{ m.picks_submitted }}/{{ m.picks_required }} picks
                                            <span v-if="m.is_locked" class="text-rose-300">· locked</span>
                                            <span v-else-if="m.is_entered" class="text-amber-300">· in progress</span>
                                        </p>
                                    </div>
                                    <span class="rounded-full bg-white/[0.06] px-2 py-1 text-xs font-bold uppercase tracking-wider text-slate-400">{{ m.status }}</span>
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
                                                <span v-if="s.is_owner" class="text-amber-300">· owner</span>
                                            </p>
                                        </div>
                                        <Link :href="`/matchday/sub-leagues/${s.id}/leaderboard`" class="shrink-0 text-xs font-bold uppercase tracking-wider text-sky-400 hover:text-sky-300">Board →</Link>
                                    </div>
                                    <div v-if="s.leaderboard_preview.length > 0" class="mt-2 space-y-1">
                                        <div v-for="(row, i) in s.leaderboard_preview" :key="i" class="flex items-center justify-between text-xs text-slate-400">
                                            <span class="truncate">#{{ i + 1 }} {{ row.name ?? '—' }}</span>
                                            <span class="tabular-nums">{{ row.points ?? '' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Panel>

                        <Panel v-if="play_data.matchdays.summary" title="MATCHDAY SUMMARY" accent="amber">
                            <div class="grid grid-cols-2 gap-4 p-4 sm:grid-cols-4">
                                <div>
                                    <p class="text-xs text-slate-500">Matchdays settled</p>
                                    <p class="mt-1 text-lg font-black text-white tabular-nums">{{ play_data.matchdays.summary.matchdays_settled }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-500">Total points</p>
                                    <p class="mt-1 text-lg font-black text-amber-300 tabular-nums">{{ signed(play_data.matchdays.summary.total_points ?? 0) }}</p>
                                </div>
                            </div>
                        </Panel>
                    </template>

                    <!-- ═══════════ JACKPOTS ═══════════ -->
                    <template v-else-if="activeTab === 'jackpots'">
                        <Panel :title="`Open Jackpots [${play_data.jackpots.open.length}]`" accent="purple">
                            <template #actions>
                                <Link href="/jackpots" class="text-xs font-bold uppercase tracking-wider text-sky-400 hover:text-sky-300">All jackpots →</Link>
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
                                    class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03]"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-100">{{ j.name }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ j.legs_count }} legs · {{ j.participant_count }} entered</p>
                                    </div>
                                    <span
                                        :class="[
                                            'rounded-full px-2 py-1 text-xs font-bold uppercase tracking-wider',
                                            j.is_locked ? 'bg-rose-400/10 text-rose-300' : 'bg-emerald-400/10 text-emerald-300',
                                        ]"
                                    >{{ j.is_locked ? 'Locked' : 'Open' }}</span>
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
                                    class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03]"
                                >
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-100">{{ j.name }}</p>
                                        <p class="mt-0.5 truncate text-xs text-slate-500">{{ j.legs_count }} legs · {{ j.participant_count }} played</p>
                                    </div>
                                    <span class="rounded-full bg-white/[0.06] px-2 py-1 text-xs font-bold uppercase tracking-wider text-slate-400">Settled</span>
                                </Link>
                            </div>
                        </Panel>
                    </template>

                    <!-- ═══════════ PORTFOLIO ═══════════ -->
                    <template v-else-if="activeTab === 'portfolio'">
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <section class="rounded-3xl border border-amber-400/20 bg-gradient-to-br from-amber-500/10 via-[#111622] to-[#111622] p-6 md:col-span-2">
                                <p class="text-xs font-black uppercase tracking-widest text-slate-500">Current reputation</p>
                                <div class="mt-2 flex items-baseline gap-2">
                                    <span
                                        :class="[
                                            'font-mono text-4xl font-black',
                                            play_data.portfolio.stats.current_points > 0 ? 'text-emerald-400' : play_data.portfolio.stats.current_points < 0 ? 'text-rose-400' : 'text-white',
                                        ]"
                                    >{{ signed(play_data.portfolio.stats.current_points) }}</span>
                                    <span class="text-xs font-bold uppercase text-slate-500">pts</span>
                                </div>
                                <div class="mt-3 flex items-center justify-between border-t border-white/[0.06] pt-3">
                                    <p class="text-xs uppercase tracking-widest text-sky-400">Global rank</p>
                                    <p class="font-mono text-xl font-black text-white">
                                        <template v-if="play_data.portfolio.stats.global_rank">#{{ play_data.portfolio.stats.global_rank }}</template>
                                        <template v-else>—</template>
                                    </p>
                                </div>
                                <p class="mt-1 text-right text-[10px] text-slate-500">of {{ play_data.portfolio.stats.total_ranked }} ranked</p>
                            </section>

                            <section class="rounded-3xl border border-white/[0.07] bg-[#111622] p-6">
                                <p class="text-xs font-black uppercase tracking-widest text-slate-500">Lifetime accuracy</p>
                                <p class="mt-2 font-mono text-4xl font-black text-white">
                                    <template v-if="play_data.portfolio.stats.accuracy !== null">{{ play_data.portfolio.stats.accuracy }}%</template>
                                    <template v-else>—</template>
                                </p>
                                <p class="mt-1 text-[10px] text-slate-500">Based on settled picks only</p>
                            </section>
                        </div>

                        <Panel title="POINT LEDGER" accent="slate">
                            <template #actions>
                                <Link href="/portfolio" class="text-xs font-bold uppercase tracking-wider text-sky-400 hover:text-sky-300">Full history →</Link>
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
                                <div v-for="log in play_data.portfolio.logs" :key="log.id" class="flex items-center justify-between gap-3 p-4">
                                    <div class="min-w-0">
                                        <p class="truncate text-sm font-bold text-slate-200">{{ log.description }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500">{{ log.created_ago }} · {{ log.type }}</p>
                                    </div>
                                    <span
                                        :class="[
                                            'shrink-0 font-mono text-sm font-black tabular-nums',
                                            log.amount > 0 ? 'text-emerald-400' : 'text-rose-400',
                                        ]"
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