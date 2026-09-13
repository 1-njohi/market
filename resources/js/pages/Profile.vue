<template>
    <div
        class="min-h-screen bg-[#070b14] p-4 font-sans text-slate-200 select-none md:p-6 lg:p-8"
    >
        <div class="mx-auto max-w-[1200px] space-y-6">
            <!-- ═══ HERO ═══ -->
            <div
                class="flex flex-col items-start justify-between gap-6 rounded-xl border border-marketplace-border bg-marketplace-card p-6 shadow-lg md:flex-row md:items-center"
            >
                <div class="flex items-center gap-4">
                    <div class="relative">
                        <img
                            :src="profile.seller.avatar"
                            alt="Avatar"
                            class="h-20 w-20 rounded-full border-2 border-marketplace-border object-cover"
                        />
                        <span
                            v-if="profile.seller.is_verified"
                            class="absolute right-0 bottom-0 rounded-full bg-marketplace-gold px-1.5 py-0.5 text-[10px] font-black text-marketplace-bg shadow-md"
                            >✓</span
                        >
                    </div>
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1
                                class="text-xl font-black tracking-wide text-white uppercase"
                            >
                                {{ profile.seller.name }}
                            </h1>
                            <span
                                v-if="profile.seller.rank"
                                class="rounded border border-marketplace-gold/30 bg-marketplace-gold/10 px-2 py-0.5 text-[9px] font-black tracking-wider text-marketplace-gold uppercase"
                            >
                                #{{ profile.seller.rank }} Global
                            </span>
                        </div>

                        <div
                            v-if="profile.seller.badges.length"
                            class="flex flex-wrap gap-1"
                        >
                            <span
                                v-for="badge in profile.seller.badges"
                                :key="badge"
                                class="rounded border border-sky-500/20 bg-sky-500/10 px-2 py-0.5 text-[9px] font-black tracking-wider text-sky-400 uppercase"
                            >
                                {{ badge }}
                            </span>
                        </div>

                        <p
                            class="line-clamp-2 max-w-md text-xs leading-relaxed text-marketplace-muted"
                        >
                            {{ profile.seller.bio }}
                        </p>

                        <div
                            class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] font-bold text-marketplace-muted uppercase"
                        >
                            <div class="flex items-center gap-1.5">
                                <span>📍</span>
                                <span>{{ profile.seller.location }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span>⚓ Member since</span>
                                <span class="text-slate-400">{{
                                    processMemberSince(
                                        profile.seller.member_since,
                                    )
                                }}</span>
                            </div>
                            <div class="flex items-center gap-1">
                                <span>👥</span>
                                <span class="text-slate-400"
                                    >{{
                                        profile.seller.followers
                                    }}
                                    followers</span
                                >
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTAs -->
                <div
                    class="flex w-full items-center gap-3 border-t border-marketplace-border/40 pt-4 md:w-auto md:border-none md:pt-0"
                >
                    <button
                        v-if="!profile.meta.is_owner"
                        @click="toggleFollow"
                        class="flex-1 cursor-pointer rounded border px-5 py-3 text-xs font-black tracking-widest uppercase transition-all md:flex-none"
                        :class="
                            profile.meta.is_following
                                ? 'border-marketplace-border bg-transparent text-slate-400 hover:text-white'
                                : 'border-marketplace-gold/30 bg-marketplace-gold/10 text-marketplace-gold hover:bg-marketplace-gold/20'
                        "
                    >
                        {{ profile.meta.is_following ? 'Following' : 'Follow' }}
                    </button>
                    <button
                        v-if="profile.meta.can_message"
                        class="cursor-pointer rounded border border-marketplace-border bg-marketplace-card px-4 py-3 text-xs font-black tracking-wider text-slate-300 uppercase transition-all hover:text-white"
                    >
                        ✉ Message
                    </button>
                </div>
            </div>

            <!-- ═══ MAIN GRID ═══ -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- LEFT -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Core metrics -->
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div
                            class="rounded-xl border border-marketplace-border bg-marketplace-card p-4 text-center shadow-lg"
                        >
                            <span
                                class="mb-1 block text-[10px] font-bold tracking-wider text-marketplace-muted uppercase"
                            >
                                Win Rate
                            </span>
                            <span
                                class="font-mono text-2xl font-black text-marketplace-green"
                            >
                                {{ profile.performance.win_rate }}%
                            </span>
                        </div>
                        <div
                            class="rounded-xl border border-marketplace-border bg-marketplace-card p-4 text-center shadow-lg"
                        >
                            <span
                                class="mb-1 block text-[10px] font-bold tracking-wider text-marketplace-muted uppercase"
                            >
                                ROI
                            </span>
                            <span
                                class="font-mono text-2xl font-black text-sky-400"
                            >
                                {{ profile.performance.roi }}%
                            </span>
                        </div>
                        <div
                            class="rounded-xl border border-marketplace-border bg-marketplace-card p-4 text-center shadow-lg"
                        >
                            <span
                                class="mb-1 block text-[10px] font-bold tracking-wider text-marketplace-muted uppercase"
                            >
                                Total Slips
                            </span>
                            <span
                                class="font-mono text-2xl font-black text-marketplace-gold"
                            >
                                {{ profile.performance.total_betslips }}
                            </span>
                        </div>
                        <div
                            class="rounded-xl border border-marketplace-border bg-marketplace-card p-4 text-center shadow-lg"
                        >
                            <span
                                class="mb-1 block text-[10px] font-bold tracking-wider text-marketplace-muted uppercase"
                            >
                                Streak
                            </span>
                            <span
                                class="font-mono text-2xl font-black"
                                :class="
                                    profile.performance.current_streak.type ===
                                    'win'
                                        ? 'text-marketplace-green'
                                        : 'text-rose-400'
                                "
                            >
                                {{ profile.performance.current_streak.count }}
                                <span class="text-xs font-normal">
                                    {{
                                        profile.performance.current_streak
                                            .type === 'win'
                                            ? 'W'
                                            : 'L'
                                    }}
                                </span>
                            </span>
                        </div>
                    </div>

                    <!-- Recent Form + more metrics -->
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div
                            class="rounded-xl border border-marketplace-border bg-marketplace-card p-4"
                        >
                            <span
                                class="text-[10px] font-bold tracking-wider text-marketplace-muted uppercase"
                            >
                                Avg Odds
                            </span>
                            <div
                                class="mt-1 font-mono text-lg font-black text-marketplace-gold"
                            >
                                {{ profile.performance.avg_odds }}
                            </div>
                        </div>
                        <div
                            class="rounded-xl border border-marketplace-border bg-marketplace-card p-4"
                        >
                            <span
                                class="text-[10px] font-bold tracking-wider text-marketplace-muted uppercase"
                            >
                                Recent Form
                            </span>
                            <div class="mt-2 flex flex-wrap gap-1">
                                <span
                                    v-for="(
                                        form, idx
                                    ) in profile.performance.recent_form.slice(
                                        0,
                                        6,
                                    )"
                                    :key="idx"
                                    :class="[
                                        'flex h-5 w-5 items-center justify-center rounded-sm border font-mono text-[9px] font-black',
                                        form.status === 'W'
                                            ? 'border-marketplace-green/60 bg-marketplace-green/20 text-marketplace-green'
                                            : 'border-red-500/60 bg-red-500/20 text-red-400',
                                    ]"
                                    :title="form.date"
                                >
                                    {{ form.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Heatmap -->
                    <HeatMap
                        :data="profile.performance.win_rate_trend.data"
                        :roi="profile.performance.roi + '%'"
                    />

                    <!-- Predictive -->
                    <div
                        class="space-y-4 rounded-xl border border-amber-500/20 bg-marketplace-card p-5 shadow-lg"
                    >
                        <div
                            class="flex items-center justify-between border-b border-marketplace-border/60 pb-3"
                        >
                            <h3
                                class="flex items-center gap-2 text-xs font-black tracking-widest text-amber-400 uppercase"
                            >
                                Insights
                            </h3>
                            <span
                                class="rounded border border-amber-500/20 bg-amber-500/10 px-2.5 py-0.5 text-[10px] font-black text-amber-500 uppercase"
                            >
                                Risk: {{ profile.predictive.risk_level }}
                            </span>
                        </div>
                        <div
                            class="grid grid-cols-1 gap-4 font-mono text-sm sm:grid-cols-3"
                        >
                            <div
                                class="rounded border border-marketplace-border bg-marketplace-card/40 p-3"
                            >
                                <span
                                    class="mb-1 block text-[9px] font-bold tracking-wider text-marketplace-muted uppercase"
                                >
                                    Projected WR
                                </span>
                                <span class="text-base font-black text-white"
                                    >{{
                                        profile.predictive.projected_win_rate
                                    }}%</span
                                >
                            </div>
                            <div
                                class="rounded border border-marketplace-border bg-marketplace-card/40 p-3"
                            >
                                <span
                                    class="mb-1 block text-[9px] font-bold tracking-wider text-marketplace-muted uppercase"
                                >
                                    Confidence
                                </span>
                                <span class="text-base font-black text-sky-400"
                                    >{{
                                        profile.predictive.confidence_score
                                    }}%</span
                                >
                            </div>
                            <div
                                class="rounded border border-marketplace-border bg-marketplace-card/40 p-3"
                            >
                                <span
                                    class="mb-1 block text-[9px] font-bold tracking-wider text-marketplace-muted uppercase"
                                >
                                    Best Day
                                </span>
                                <span
                                    class="text-xs font-black tracking-wide text-marketplace-green uppercase"
                                >
                                    {{
                                        profile.predictive.best_times[0]?.day ??
                                        '—'
                                    }}
                                    ({{
                                        profile.predictive.best_times[0]
                                            ?.win_rate ?? 0
                                    }}%)
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- Expertise -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <div
                            class="space-y-3 rounded-xl border border-marketplace-border bg-marketplace-card p-4"
                        >
                            <h3
                                class="border-b border-marketplace-border/60 pb-2 text-xs font-black tracking-widest text-slate-400 uppercase"
                            >
                                Top leagues
                            </h3>
                            <div
                                v-if="!profile.expertise.top_leagues.length"
                                class="text-center text-[10px] text-marketplace-muted uppercase"
                            >
                                No data
                            </div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="league in profile.expertise
                                        .top_leagues"
                                    :key="league.league_name"
                                    class="flex items-center justify-between text-xs"
                                >
                                    <span
                                        class="max-w-[150px] truncate font-bold text-slate-300"
                                        >{{ league.league_name }}</span
                                    >
                                    <div
                                        class="flex gap-3 font-mono text-[11px]"
                                    >
                                        <span
                                            class="font-bold text-marketplace-green"
                                            >{{ league.win_rate }}%</span
                                        >
                                        <span class="text-marketplace-muted">{{
                                            league.betslips
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="space-y-3 rounded-xl border border-marketplace-border bg-marketplace-card p-4"
                        >
                            <h3
                                class="border-b border-marketplace-border/60 pb-2 text-xs font-black tracking-widest text-slate-400 uppercase"
                            >
                                Top Markets
                            </h3>
                            <div
                                v-if="!profile.expertise.top_markets.length"
                                class="text-center text-[10px] text-marketplace-muted uppercase"
                            >
                                No data
                            </div>
                            <div v-else class="space-y-2">
                                <div
                                    v-for="market in profile.expertise
                                        .top_markets"
                                    :key="market.market_name"
                                    class="flex items-center justify-between text-xs"
                                >
                                    <span
                                        class="max-w-[150px] truncate font-bold text-slate-300"
                                        >{{ market.market_name }}</span
                                    >
                                    <div
                                        class="flex gap-3 font-mono text-[11px]"
                                    >
                                        <span
                                            class="font-bold text-marketplace-green"
                                            >{{ market.win_rate }}%</span
                                        >
                                        <span class="text-marketplace-muted">{{
                                            market.betslips
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Available betslips -->
                    <div class="space-y-3">
                        <h2
                            class="text-xs font-black tracking-widest text-slate-400 uppercase"
                        >
                            Betslips for sale
                        </h2>
                        <div
                            v-if="!profile.available_betslips.length"
                            class="rounded-xl border border-marketplace-border bg-marketplace-card p-6 text-center text-xs text-marketplace-muted uppercase"
                        >
                            No live entries listed right now.
                        </div>
                        <div
                            v-else
                            class="grid grid-cols-1 gap-4 sm:grid-cols-2"
                        >
                            <div
                                v-for="slip in profile.available_betslips"
                                :key="slip.id"
                                class="flex flex-col justify-between space-y-3 rounded-xl border border-marketplace-border bg-marketplace-card p-4 transition-colors hover:border-sky-500/40"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <span
                                            class="rounded border border-marketplace-border bg-marketplace-card/60 px-2 py-0.5 font-mono text-[10px] text-slate-400 uppercase"
                                        >
                                            {{ slip.code }}
                                        </span>
                                        <p
                                            class="mt-1.5 text-[10px] font-bold text-marketplace-muted uppercase"
                                        >
                                            {{ slip.legs }} Selections
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            class="block text-[9px] font-bold text-marketplace-muted uppercase"
                                            >Odds</span
                                        >
                                        <span
                                            class="font-mono text-sm font-black text-marketplace-gold"
                                            >{{ slip.total_odds }}</span
                                        >
                                    </div>
                                </div>
                                <div
                                    class="flex items-center justify-between border-t border-marketplace-border/40 pt-3"
                                >
                                    <div>
                                        <span
                                            class="block text-[8px] font-bold text-marketplace-muted uppercase"
                                            >Cost</span
                                        >
                                        <span
                                            class="font-mono text-sm font-black text-marketplace-green"
                                            >KES {{ slip.price }}</span
                                        >
                                    </div>
                                    <button
                                        @click="viewBetslip(slip.code)"
                                        class="cursor-pointer rounded border-b border-amber-700 bg-marketplace-gold px-3 py-2 text-[11px] font-black tracking-wide text-marketplace-bg uppercase shadow transition-all hover:bg-marketplace-gold/80"
                                    >
                                        View Betslip
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT -->
                <div class="space-y-6">
                    <!-- Marketplace dynamics -->
                    <div
                        class="space-y-3 rounded-xl border border-marketplace-border bg-marketplace-card p-4 shadow-lg"
                    >
                        <h3
                            class="border-b border-marketplace-border/60 pb-2 text-xs font-black tracking-widest text-slate-400 uppercase"
                        >
                            Marketplace activity
                        </h3>
                        <div
                            class="grid grid-cols-2 gap-4 py-1 text-center font-mono"
                        >
                            <div
                                class="rounded border border-marketplace-border/60 bg-marketplace-card/40 p-2.5"
                            >
                                <span
                                    class="block text-[8px] font-bold text-marketplace-muted uppercase"
                                    >Sold Rate</span
                                >
                                <span class="text-sm font-black text-white"
                                    >{{ profile.performance.sold_rate }}%</span
                                >
                            </div>
                            <div
                                class="rounded border border-marketplace-border/60 bg-marketplace-card/40 p-2.5"
                            >
                                <span
                                    class="block text-[8px] font-bold text-marketplace-muted uppercase"
                                    >Last Sale</span
                                >
                                <span class="text-xs font-black text-sky-400">{{
                                    profile.transaction_history.quick_stats
                                        .last_sale
                                }}</span>
                            </div>
                        </div>
                        <div class="space-y-2 pt-1 text-xs">
                            <div
                                class="flex justify-between text-[10px] font-bold text-marketplace-muted uppercase"
                            >
                                <span>Sales this week</span>
                                <span class="font-mono text-slate-300">{{
                                    profile.transaction_history.quick_stats
                                        .sales_this_week
                                }}</span>
                            </div>
                            <div
                                class="flex justify-between text-[10px] font-bold text-marketplace-muted uppercase"
                            >
                                <span>Sales this month</span>
                                <span class="font-mono text-slate-300">{{
                                    profile.transaction_history.quick_stats
                                        .sales_this_month
                                }}</span>
                            </div>
                            <div
                                class="flex justify-between text-[10px] font-bold text-marketplace-muted uppercase"
                            >
                                <span>Avg sell time</span>
                                <span class="font-mono text-slate-300"
                                    >{{
                                        profile.transaction_history.quick_stats
                                            .avg_time_to_sell
                                    }}
                                    min</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Accuracy breakdown -->
                    <div
                        class="space-y-3 rounded-xl border border-marketplace-border bg-marketplace-card p-4 shadow-lg"
                    >
                        <h3
                            class="border-b border-marketplace-border/60 pb-2 text-xs font-black tracking-widest text-slate-400 uppercase"
                        >
                            Accuracy over time
                        </h3>
                        <div class="space-y-2 font-mono text-xs">
                            <div
                                v-for="(rate, title) in profile.performance
                                    .win_rate_breakdown"
                                :key="title"
                                class="flex items-center justify-between"
                            >
                                <span
                                    class="font-sans text-[10px] font-bold text-marketplace-muted uppercase"
                                >
                                    {{ title.replace(/_/g, ' ') }}
                                </span>
                                <div
                                    class="flex w-2/3 items-center justify-end gap-3"
                                >
                                    <div
                                        class="h-1.5 w-24 overflow-hidden rounded-full border border-marketplace-border bg-marketplace-card/40"
                                    >
                                        <div
                                            class="h-full bg-marketplace-green"
                                            :style="{ width: rate + '%' }"
                                        ></div>
                                    </div>
                                    <span
                                        class="w-10 text-right font-black text-marketplace-green"
                                        >{{ rate }}%</span
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import HeatMap from '@/components/dashboard/HeatMap.vue';
import { ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const profile = ref(page.props.seller_data);

const processMemberSince = (memberSince) => {
    const date = new Date(memberSince);
    return date.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
};

const toggleFollow = () => {
    const url = profile.value.meta.is_following
        ? `/users/${profile.value.seller.id}/unfollow`
        : `/users/${profile.value.seller.id}/follow`;

    const method = profile.value.meta.is_following ? 'delete' : 'post';

    router[method](
        url,
        {},
        {
            preserveScroll: true,
            onStart: () => {
                profile.value.meta.is_following =
                    !profile.value.meta.is_following;
                profile.value.seller.followers += profile.value.meta
                    .is_following
                    ? 1
                    : -1;
            },
            onError: () => {
                profile.value.meta.is_following =
                    !profile.value.meta.is_following;
                profile.value.seller.followers += profile.value.meta
                    .is_following
                    ? 1
                    : -1;
            },
        },
    );
};

const viewBetslip = (code) => {
    router.visit(`/betslip/view/g/${code}`);
};
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
