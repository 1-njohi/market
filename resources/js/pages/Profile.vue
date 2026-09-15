<template>
    <div
        class="min-h-screen bg-[#070b14] px-4 py-6 font-sans text-slate-200 selection:bg-sky-500/30 selection:text-white lg:px-8 lg:py-10"
    >
        <div class="mx-auto w-full max-w-7xl space-y-6">
            <!-- ═══════════════ HEADER ═══════════════ -->
            <div
                class="flex w-full flex-col gap-4 border-b border-gray-800/60 pb-6 md:flex-row md:items-start md:justify-between"
            >
                <!-- LEFT: profile -->
                <div class="flex min-w-0 items-start gap-3 md:gap-4">
                    <div class="relative flex-shrink-0">
                        <img
                            :src="profile.seller.avatar"
                            :alt="profile.seller.name"
                            class="h-14 w-14 rounded border border-sky-500/30 bg-[#111622] object-cover md:h-16 md:w-16"
                        />
                        <span
                            v-if="profile.seller.is_verified"
                            class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500 text-[8px] font-black text-[#070b14]"
                            title="VERIFIED"
                            >✓</span
                        >
                    </div>

                    <div class="min-w-0 flex-1">
                        <!-- Eyebrow: role + rank -->
                        <div
                            class="flex flex-wrap items-center gap-2 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                        >
                            <span>Tipster</span>
                            <span
                                v-if="profile.seller.rank"
                                class="rounded border border-amber-500/30 bg-amber-500/10 px-1.5 py-0.5 font-mono text-[9px] text-amber-400"
                            >
                                #{{ profile.seller.rank }} Global
                            </span>
                        </div>

                        <!-- Name -->
                        <h1
                            class="mt-0.5 truncate font-mono text-lg font-black tracking-tight text-white uppercase md:text-xl"
                        >
                            {{ profile.seller.name }}
                        </h1>

                        <!-- Meta strip -->
                        <div
                            class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-[10px] font-medium text-slate-400"
                        >
                            <span v-if="profile.seller.location">
                                📍 {{ profile.seller.location }}
                            </span>
                            <span class="text-slate-600">•</span>
                            <span>
                                Member since
                                <span class="font-bold text-slate-300">{{
                                    processMemberSince(
                                        profile.seller.member_since,
                                    )
                                }}</span>
                            </span>
                            <span class="text-slate-600">•</span>
                            <span>
                                <span class="font-bold text-slate-300">{{
                                    profile.seller.followers
                                }}</span>
                                followers
                            </span>
                        </div>

                        <!-- Badges -->
                        <div
                            v-if="profile.seller.badges.length"
                            class="mt-2 flex flex-wrap gap-1"
                        >
                            <span
                                v-for="badge in profile.seller.badges"
                                :key="badge"
                                class="rounded border border-sky-500/20 bg-sky-500/10 px-2 py-0.5 text-[9px] font-black tracking-wider text-sky-400 uppercase"
                            >
                                {{ badge }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: CTAs -->
                <div class="flex flex-shrink-0 items-center gap-2">
                    
                    <button
                        v-if="!profile.meta.is_owner"
                        @click="toggleFollow"
                        class="cursor-pointer rounded border px-5 py-2 text-[10px] font-black tracking-widest uppercase transition-all"
                        :class="
                            profile.meta.is_following
                                ? 'border-[#232d42] bg-[#111622] text-slate-400 hover:text-white'
                                : 'border-emerald-500/30 bg-emerald-500/10 text-emerald-400 hover:bg-emerald-500/20'
                        "
                    >
                        {{ profile.meta.is_following ? 'Following' : 'Follow' }}
                    </button>
                </div>
            </div>

            <!-- ═══════════════ BIO ═══════════════ -->
            <div
                v-if="profile.seller.bio"
                class="rounded border border-[#232d42] bg-[#111622] p-4"
            >
                <span
                    class="mb-2 block text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >About</span
                >
                <p class="text-xs leading-relaxed text-slate-400">
                    {{ profile.seller.bio }}
                </p>
            </div>

            <!-- ═══════════════ METRIC CARDS ═══════════════ -->
            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                <!-- WIN RATE -->
                <div
                    class="flex flex-col justify-between rounded border border-[#232d42] bg-[#111622] p-4"
                >
                    <div>
                        <span
                            class="text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                            >Win rate</span
                        >
                        <div class="mt-2 flex items-baseline gap-2">
                            <span
                                class="font-mono text-3xl font-black tracking-tight text-white"
                                >{{ profile.performance.win_rate }}%</span
                            >
                        </div>
                    </div>
                    <div
                        class="mt-4 flex justify-between border-t border-gray-800/40 pt-2 font-mono text-[10px] tracking-tight text-slate-400 uppercase"
                    >
                        <span>Won / Settled:</span>
                        <span class="font-bold text-white">
                            {{ profile.performance.won_betslips }} /
                            {{ profile.performance.total_betslips }}
                        </span>
                    </div>
                </div>

                <!-- ROI -->
                <div
                    class="flex flex-col justify-between rounded border border-[#232d42] bg-[#111622] p-4"
                >
                    <div>
                        <span
                            class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                            >ROI</span
                        >
                        <div class="mt-2 flex items-baseline gap-2">
                            <span
                                class="font-mono text-3xl font-black tracking-tight text-white"
                                >{{ profile.performance.roi }}%</span
                            >
                        </div>
                    </div>
                    <div
                        class="mt-4 flex justify-between border-t border-gray-800/40 pt-2 font-mono text-[10px] tracking-tight text-slate-400 uppercase"
                    >
                        <span>Avg Odds:</span>
                        <span class="font-bold text-white">{{
                            profile.performance.avg_odds
                        }}</span>
                    </div>
                </div>

                <!-- TOTAL SLIPS -->
                <div
                    class="flex flex-col justify-between rounded border border-[#232d42] bg-[#111622] p-4"
                >
                    <div>
                        <span
                            class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                            >Total Betslips</span
                        >
                        <div class="mt-2 flex items-baseline gap-2">
                            <span
                                class="font-mono text-3xl font-black tracking-tight text-white"
                                >{{ profile.performance.total_betslips }}</span
                            >
                        </div>
                    </div>
                    <div
                        class="mt-4 flex justify-between border-t border-gray-800/40 pt-2 font-mono text-[10px] tracking-tight text-slate-400 uppercase"
                    >
                        <span>Active:</span>
                        <span class="font-bold text-white">{{
                            profile.available_betslips.length
                        }}</span>
                    </div>
                </div>

                <!-- STREAK -->
                <div
                    class="flex flex-col justify-between rounded border border-[#232d42] bg-[#111622] p-4"
                >
                    <div>
                        <span
                            class="text-[10px] font-black tracking-widest uppercase"
                            :class="
                                profile.performance.current_streak.type ===
                                'win'
                                    ? 'text-emerald-400'
                                    : 'text-rose-400'
                            "
                            >Current Streak</span
                        >
                        <div class="mt-2 flex items-baseline gap-2">
                            <span
                                class="font-mono text-3xl font-black tracking-tight text-white"
                                >{{
                                    profile.performance.current_streak.count
                                }}</span
                            >
                            <span
                                class="font-mono text-xs font-black uppercase"
                                :class="
                                    profile.performance.current_streak.type ===
                                    'win'
                                        ? 'text-emerald-400'
                                        : 'text-rose-400'
                                "
                            >
                                {{
                                    profile.performance.current_streak.type ===
                                    'win'
                                        ? 'WINS'
                                        : 'LOSSES'
                                }}
                            </span>
                        </div>
                    </div>
                    <div
                        class="mt-4 flex justify-between border-t border-gray-800/40 pt-2 font-mono text-[10px] tracking-tight text-slate-400 uppercase"
                    >
                        <span>Last 6:</span>
                        <span class="flex gap-0.5">
                            <span
                                v-for="(
                                    form, idx
                                ) in profile.performance.recent_form.slice(
                                    0,
                                    6,
                                )"
                                :key="idx"
                                :class="[
                                    'flex h-3 w-3 items-center justify-center rounded-sm font-mono text-[7px] font-black',
                                    form.status === 'W'
                                        ? 'bg-emerald-500/20 text-emerald-400'
                                        : 'bg-rose-500/20 text-rose-400',
                                ]"
                                :title="form.date"
                            >
                                {{ form.status }}
                            </span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- ═══════════════ MAIN GRID ═══════════════ -->
            <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
                <!-- LEFT TRACK -->
                <div class="space-y-6 lg:col-span-8">
                    <!-- HEATMAP -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                                >Win rate trend</span
                            >
                        </div>
                        <div class="p-4">
                            <HeatMap
                                :data="profile.performance.win_rate_trend.data"
                                :roi="profile.performance.roi + '%'"
                            />
                        </div>
                    </div>

                    <!-- EXPERTISE -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Top leagues -->
                        <div
                            class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                        >
                            <div
                                class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
                            >
                                <span
                                    class="text-[10px] font-black tracking-widest text-purple-400 uppercase"
                                    >Top leagues</span
                                >
                                <span
                                    class="font-mono text-[9px] text-slate-500 uppercase"
                                    >WR / Count</span
                                >
                            </div>
                            <div
                                v-if="!profile.expertise.top_leagues.length"
                                class="p-6 text-center text-[10px] font-bold text-slate-500 uppercase"
                            >
                                No data yet
                            </div>
                            <div v-else class="divide-y divide-gray-800/40">
                                <div
                                    v-for="league in profile.expertise
                                        .top_leagues"
                                    :key="league.league_name"
                                    class="flex items-center justify-between px-4 py-3"
                                >
                                    <span
                                        class="max-w-[160px] truncate text-[11px] font-bold text-slate-300 uppercase"
                                    >
                                        {{ league.league_name }}
                                    </span>
                                    <div
                                        class="flex gap-3 font-mono text-[11px]"
                                    >
                                        <span
                                            class="font-black text-emerald-400"
                                            >{{ league.win_rate }}%</span
                                        >
                                        <span class="text-slate-500">{{
                                            league.betslips
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Top markets -->
                        <div
                            class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                        >
                            <div
                                class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
                            >
                                <span
                                    class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                                    >Top markets</span
                                >
                                <span
                                    class="font-mono text-[9px] text-slate-500 uppercase"
                                    >WR / Count</span
                                >
                            </div>
                            <div
                                v-if="!profile.expertise.top_markets.length"
                                class="p-6 text-center text-[10px] font-bold text-slate-500 uppercase"
                            >
                                No data yet
                            </div>
                            <div v-else class="divide-y divide-gray-800/40">
                                <div
                                    v-for="market in profile.expertise
                                        .top_markets"
                                    :key="market.market_name"
                                    class="flex items-center justify-between px-4 py-3"
                                >
                                    <span
                                        class="max-w-[160px] truncate text-[11px] font-bold text-slate-300 uppercase"
                                    >
                                        {{ market.market_name }}
                                    </span>
                                    <div
                                        class="flex gap-3 font-mono text-[11px]"
                                    >
                                        <span
                                            class="font-black text-emerald-400"
                                            >{{ market.win_rate }}%</span
                                        >
                                        <span class="text-slate-500">{{
                                            market.betslips
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- BETSLIPS FOR SALE -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                            >
                                Betslips for sale [{{
                                    profile.available_betslips.length
                                }}]
                            </span>
                            <span
                                class="font-mono text-[9px] text-slate-500 uppercase"
                                >Live</span
                            >
                        </div>

                        <div
                            v-if="!profile.available_betslips.length"
                            class="p-8 text-center text-[10px] font-bold text-slate-500 uppercase"
                        >
                            No live entries listed right now
                        </div>

                        <div
                            v-else
                            class="grid grid-cols-1 gap-px bg-gray-800/40 sm:grid-cols-2"
                        >
                            <div
                                v-for="slip in profile.available_betslips"
                                :key="slip.id"
                                class="flex flex-col justify-between gap-4 bg-[#111622] p-4 transition-colors hover:bg-[#131a2a]"
                            >
                                <!-- Top row: code + odds -->
                                <div class="flex items-start justify-between">
                                    <div class="min-w-0">
                                        <span
                                            class="inline-block rounded border border-[#232d42] bg-[#0a101f] px-2 py-0.5 font-mono text-[10px] text-slate-400 uppercase"
                                        >
                                            {{ slip.code }}
                                        </span>
                                        <p
                                            class="mt-1.5 text-[10px] font-bold tracking-wider text-slate-500 uppercase"
                                        >
                                            {{ slip.legs }} Selections
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            class="block text-[9px] font-bold tracking-wider text-slate-500 uppercase"
                                            >Odds</span
                                        >
                                        <span
                                            class="font-mono text-base font-black text-amber-400"
                                            >{{ slip.total_odds }}</span
                                        >
                                    </div>
                                </div>

                                <!-- Bottom row: price + CTA -->
                                <div
                                    class="flex items-center justify-between border-t border-gray-800/40 pt-3"
                                >
                                    <div>
                                        <span
                                            class="block text-[9px] font-bold tracking-wider text-slate-500 uppercase"
                                            >Unlock Price</span
                                        >
                                        <span
                                            class="font-mono text-sm font-black text-emerald-400"
                                            >KES {{ slip.price }}</span
                                        >
                                    </div>
                                    <button
                                        @click="viewBetslip(slip.code)"
                                        class="cursor-pointer rounded border border-amber-500/30 bg-amber-500/10 px-3 py-1.5 text-[10px] font-black tracking-widest text-amber-400 uppercase transition-colors hover:bg-amber-500/20"
                                    >
                                        View
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT TRACK -->
                <div class="space-y-6 lg:col-span-4">
                    <!-- RECENT FORM -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-slate-200 uppercase"
                                >Recent form</span
                            >
                            <span
                                class="font-mono text-[9px] text-slate-500 uppercase"
                                >Last 6</span
                            >
                        </div>
                        <div class="p-4">
                            <div class="flex flex-wrap gap-1.5">
                                <div
                                    v-for="(form, index) in profile.performance
                                        .recent_form"
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
                        </div>
                    </div>

                    <!-- ACCURACY OVER TIME -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                                >Win rate over time</span
                            >
                        </div>
                        <div class="space-y-3.5 p-4">
                            <div
                                v-for="(rate, label) in profile.performance
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

                    <!-- INSIGHTS -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="flex items-center justify-between border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                                >Insights</span
                            >
                            <span
                                class="rounded border border-amber-500/20 bg-amber-500/10 px-2 py-0.5 font-mono text-[9px] font-black text-amber-400 uppercase"
                            >
                                Risk: {{ profile.predictive.risk_level }}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 gap-px bg-gray-800/40">
                            <div
                                class="flex items-center justify-between bg-[#111622] px-4 py-3"
                            >
                                <span
                                    class="text-[10px] font-bold tracking-wider text-slate-500 uppercase"
                                    >Projected WR</span
                                >
                                <span
                                    class="font-mono text-sm font-black text-white"
                                    >{{
                                        profile.predictive.projected_win_rate
                                    }}%</span
                                >
                            </div>
                            <div
                                class="flex items-center justify-between bg-[#111622] px-4 py-3"
                            >
                                <span
                                    class="text-[10px] font-bold tracking-wider text-slate-500 uppercase"
                                    >Confidence</span
                                >
                                <span
                                    class="font-mono text-sm font-black text-sky-400"
                                    >{{
                                        profile.predictive.confidence_score
                                    }}%</span
                                >
                            </div>
                            <div
                                class="flex items-center justify-between bg-[#111622] px-4 py-3"
                            >
                                <span
                                    class="text-[10px] font-bold tracking-wider text-slate-500 uppercase"
                                    >Best day</span
                                >
                                <span
                                    class="font-mono text-[11px] font-black tracking-wide text-emerald-400 uppercase"
                                >
                                    {{
                                        profile.predictive.best_times[0]?.day ??
                                        '—'
                                    }}
                                    {{
                                        profile.predictive.best_times[0]
                                            ?.win_rate
                                            ? `(${profile.predictive.best_times[0].win_rate}%)`
                                            : ''
                                    }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- REPUTATION -->
                    <div
                        class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
                    >
                        <div
                            class="border-b border-gray-800/60 bg-[#111a30] p-4"
                        >
                            <span
                                class="text-[10px] font-black tracking-widest text-purple-400 uppercase"
                                >Reputation</span
                            >
                        </div>
                        <div class="space-y-3 p-4">
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-[10px] font-bold tracking-wider text-slate-500 uppercase"
                                    >Global rank</span
                                >
                                <span
                                    class="font-mono text-sm font-black text-amber-400"
                                >
                                    {{
                                        profile.seller.rank
                                            ? `#${profile.seller.rank}`
                                            : '—'
                                    }}
                                </span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-[10px] font-bold tracking-wider text-slate-500 uppercase"
                                    >Followers</span
                                >
                                <span
                                    class="font-mono text-sm font-black text-white"
                                    >{{ profile.seller.followers }}</span
                                >
                            </div>
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-[10px] font-bold tracking-wider text-slate-500 uppercase"
                                    >Joined</span
                                >
                                <span
                                    class="font-mono text-[11px] font-black text-slate-300"
                                    >{{
                                        processMemberSince(
                                            profile.seller.member_since,
                                        )
                                    }}</span
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
import HeatMap from '@/components/dashboard/HeatMap.vue';
import { ref } from 'vue';
import { usePage, router } from '@inertiajs/vue3';

const page = usePage();
const profile = ref(page.props.seller_data);

const processMemberSince = (memberSince) => {
    const date = new Date(memberSince);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        year: 'numeric',
    });
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
