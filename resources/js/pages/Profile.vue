<template>
    <div
        class="min-h-screen bg-[#0b0f19] p-4 font-sans text-slate-300 select-none md:p-6 lg:p-8"
    >
        <div class="mx-auto max-w-[1200px] space-y-6">
            <!-- ================= HERO SECTION / PROFILE HEADER ================= -->
            <div
                class="flex flex-col items-start justify-between gap-6 rounded-lg border border-[#232d42] bg-[#161c2a] p-6 shadow-xl md:flex-row md:items-center"
            >
                <div class="flex items-center gap-4">
                    <!-- Avatar Frame -->
                    <div class="relative">
                        <img
                            :src="
                                profile.seller.avatar ||
                                'https://via.placeholder.com/150'
                            "
                            alt="Avatar"
                            class="h-20 w-20 rounded-full border-2 border-[#232d42] object-cover"
                        />
                        <span
                            v-if="profile.seller.is_verified"
                            class="absolute right-0 bottom-0 rounded-full bg-sky-500 px-1.5 py-0.5 text-[10px] font-black text-black shadow-md"
                            >✓ VERIFIED</span
                        >
                    </div>
                    <!-- Seller Metadata -->
                    <div class="space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1
                                class="text-xl font-black tracking-wide text-slate-100 uppercase"
                            >
                                {{ profile.seller.name }}
                            </h1>
                            <div
                                class="grid grid-cols-2 gap-1 md:flex md:items-center"
                            >
                                <span
                                    v-for="badge in profile.seller.badges"
                                    :key="badge"
                                    class="block rounded border border-sky-500/20 bg-sky-500/10 px-2 py-0.5 text-center text-[9px] font-black tracking-wider text-sky-400 uppercase md:inline-block"
                                >
                                    {{ badge }}
                                </span>
                            </div>
                        </div>
                        <p
                            class="line-clamp-2 max-w-md text-xs leading-relaxed text-[#64748b]"
                        >
                            {{ profile.seller.bio }}
                        </p>
                        <div
                            class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] font-bold text-[#64748b] uppercase"
                        >
                            <!-- Dynamic Location with Flag Proportion Alignment -->
                            <div class="flex items-center gap-1.5">
                                <img
                                    src="https://purecatamphetamine.github.io/country-flag-icons/3x2/KE.svg"
                                    alt="Kenya Flag"
                                    class="h-auto w-4 rounded-[1px] opacity-85 shadow-sm"
                                />
                                <span>{{ 'KE' }}</span>
                                <!-- <span>{{ profile.seller.location }}</span> -->
                            </div>

                            <!-- Clean Single Line Info (Removed the raw <br> to keep flex design flat) -->
                            <div class="flex items-center gap-1">
                                <span>⚓ Member Since</span>
                                <span class="text-slate-400">{{
                                    processMemberSince(
                                        profile.seller.member_since,
                                    )
                                }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Call to Actions -->
                <div
                    class="flex w-full items-center gap-3 border-t border-[#232d42]/40 pt-4 md:w-auto md:border-none md:pt-0"
                >
                    <button
                        @click="toggleFollow"
                        class="flex-1 cursor-pointer rounded border px-5 py-3 text-xs font-black tracking-widest uppercase transition-all md:flex-none"
                        :class="
                            profile.meta.is_following
                                ? 'border-[#232d42] bg-transparent text-slate-400 hover:text-slate-200'
                                : 'border-sky-500/30 bg-[#242f48] text-sky-400 hover:bg-[#2e3c5c]'
                        "
                    >
                        {{
                            profile.meta.is_following
                                ? 'Following'
                                : 'Follow Tipster'
                        }}
                    </button>
                    <button
                        v-if="profile.meta.can_message"
                        class="cursor-pointer rounded border border-[#232d42] bg-[#111622] px-4 py-3 text-xs font-black tracking-wider text-slate-300 uppercase transition-all hover:text-white"
                    >
                        ✉ Message
                    </button>
                </div>
            </div>
            <!-- ================= MAIN LAYOUT GRID ================= -->
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- LEFT COLUMN: PRIMARY STATS & PREDICTIVE INSIGHTS -->
                <div class="space-y-6 lg:col-span-2">
                    <!-- Core Performance Dashboard -->
                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div
                            class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4 text-center shadow-lg"
                        >
                            <span
                                class="mb-1 block text-[10px] font-bold tracking-wider text-[#64748b] uppercase"
                                >All-Time Win Rate</span
                            >
                            <span
                                class="font-mono text-2xl font-black text-emerald-400"
                                >{{ profile.performance.win_rate }}%</span
                            >
                        </div>
                        <div
                            class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4 text-center shadow-lg"
                        >
                            <span
                                class="mb-1 block text-[10px] font-bold tracking-wider text-[#64748b] uppercase"
                                >ROI Metrics</span
                            >
                            <span
                                class="font-mono text-2xl font-black text-sky-400"
                                >{{ profile.performance.roi }}%</span
                            >
                        </div>
                        <div
                            class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4 text-center shadow-lg"
                        >
                            <span
                                class="mb-1 block text-[10px] font-bold tracking-wider text-[#64748b] uppercase"
                                >Avg Slips Odds</span
                            >
                            <span
                                class="font-mono text-2xl font-black text-amber-400"
                                >{{ profile.performance.avg_odds }}</span
                            >
                        </div>
                        <div
                            class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4 text-center shadow-lg"
                        >
                            <span
                                class="mb-1 block text-[10px] font-bold tracking-wider text-[#64748b] uppercase"
                                >Recent Form</span
                            >
                            <div class="mt-2 flex flex-inverse justify-center gap-1">
                                <span
                                    v-for="(
                                        form, idx
                                    ) in profile.performance.recent_form.slice(
                                        0,
                                        5,
                                    ).reverse()"
                                    :key="idx"
                                    :class="[
                                        'flex h-6 w-6 items-center justify-center rounded-sm border-[0.5px] font-mono text-[10px] font-black',
                                        form.status === 'W'
                                            ? 'border-emerald-500 bg-emerald-500/20 text-emerald-400 shadow-sm shadow-emerald-500/10'
                                            : form.status === 'L'
                                              ? 'border-red-500 bg-red-500/20 text-red-400 shadow-sm shadow-red-500/10'
                                              : 'border-slate-500 bg-slate-500/20 text-slate-400',
                                    ]"
                                    :title="
                                        form.status === 'W'
                                            ? 'Won'
                                            : form.status === 'L'
                                              ? 'Lost'
                                              : 'Refunded / Void'
                                    "
                                >
                                    {{ form.status }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- HEATMAP ELEMENT PLACEMENT WINDOW -->
                    <HeatMap
                        :data="profile.performance.win_rate_trend.data"
                        :roi="profile.performance.roi + '%'"
                    />

                    <!-- Predictive Analysis Well -->
                    <div
                        class="space-y-4 rounded-lg border border-amber-500/20 bg-[#14203b]/30 p-5 shadow-lg"
                    >
                        <div
                            class="flex items-center justify-between border-b border-[#232d42]/60 pb-3"
                        >
                            <h3
                                class="flex items-center gap-2 text-xs font-black tracking-widest text-amber-400 uppercase"
                            >
                                🧠 Predictor Insights
                            </h3>
                            <span
                                class="rounded border border-amber-500/20 bg-amber-500/10 px-2.5 py-0.5 text-[10px] font-black text-amber-500 uppercase"
                                >Risk Profile:
                                {{ profile.predictive.risk_level }}</span
                            >
                        </div>
                        <div
                            class="grid grid-cols-1 gap-4 font-mono text-sm sm:grid-cols-3"
                        >
                            <div
                                class="rounded border border-[#232d42] bg-[#111622] p-3"
                            >
                                <span
                                    class="mb-1 block text-[9px] font-bold tracking-wider text-[#64748b] uppercase"
                                    >Projected Win Rate</span
                                >
                                <span
                                    class="text-base font-black text-slate-200"
                                    >{{
                                        profile.predictive.projected_win_rate
                                    }}%</span
                                >
                            </div>
                            <div
                                class="rounded border border-[#232d42] bg-[#111622] p-3"
                            >
                                <span
                                    class="mb-1 block text-[9px] font-bold tracking-wider text-[#64748b] uppercase"
                                    >Confidence Score</span
                                >
                                <span class="text-base font-black text-sky-400"
                                    >{{
                                        profile.predictive.confidence_score
                                    }}%</span
                                >
                            </div>
                            <div
                                class="rounded border border-[#232d42] bg-[#111622] p-3"
                            >
                                <span
                                    class="mb-1 block text-[9px] font-bold tracking-wider text-[#64748b] uppercase"
                                    >Best Day to Buy</span
                                >
                                <span
                                    class="text-base text-xs font-black tracking-wide text-emerald-400 uppercase"
                                    >{{
                                        profile.predictive.best_times[0]?.day
                                    }}
                                    ({{
                                        profile.predictive.best_times[0]
                                            ?.win_rate
                                    }}% WR)</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Expertise Metrics Breakdown -->
                    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                        <!-- Top Leagues -->
                        <div
                            class="space-y-3 rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                        >
                            <h3
                                class="border-b border-[#232d42]/60 pb-2 text-xs font-black tracking-widest text-slate-400 uppercase"
                            >
                                🏆 Top Leagues Performance
                            </h3>
                            <div class="space-y-2">
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
                                        class="flex gap-4 font-mono text-[11px]"
                                    >
                                        <span class="font-bold text-emerald-400"
                                            >{{ league.win_rate }}% WR</span
                                        >
                                        <span class="text-[#64748b]"
                                            >{{ league.betslips }} Slips</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Top Markets -->
                        <div
                            class="space-y-3 rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                        >
                            <h3
                                class="border-b border-[#232d42]/60 pb-2 text-xs font-black tracking-widest text-slate-400 uppercase"
                            >
                                🎯 Top Target Markets
                            </h3>
                            <div class="space-y-2">
                                <div
                                    v-for="market in profile.expertise
                                        .top_markets"
                                    :key="market.market_name"
                                    class="flex items-center justify-between text-xs"
                                >
                                    <span
                                        class="max-w-[150px] truncate font-bold text-slate-300"
                                        >{{
                                            market.market_name.replace(
                                                /_/g,
                                                ' ',
                                            )
                                        }}</span
                                    >
                                    <div
                                        class="flex gap-4 font-mono text-[11px]"
                                    >
                                        <span class="font-bold text-emerald-400"
                                            >{{ market.win_rate }}% WR</span
                                        >
                                        <span class="text-[#64748b]"
                                            >{{ market.betslips }} Slips</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Active Available Betslips -->
                    <div class="space-y-3">
                        <h2
                            class="text-xs font-black tracking-widest text-slate-400 uppercase"
                        >
                            ⚡ Active Slips For Sale
                        </h2>
                        <div
                            v-if="profile.available_betslips.length === 0"
                            class="rounded-lg border border-[#232d42] bg-[#161c2a] p-6 text-center text-xs text-[#64748b] uppercase"
                        >
                            No live entries listed right now.
                        </div>
                        <div
                            v-else
                            class="grid grid-cols-1 gap-4 sm:grid-cols-2 md:max-h-full max-h-[50vh] overflow-auto"
                        >
                            <div
                                v-for="slip in profile.available_betslips"
                                :key="slip.id"
                                class="flex flex-col justify-between space-y-3 rounded-lg border border-[#232d42] bg-[#161c2a] p-4 transition-colors hover:border-sky-500/40"
                            >
                                <div class="flex items-start justify-between">
                                    <div>
                                        <span
                                            class="rounded border border-[#232d42] bg-[#111622] px-2 py-0.5 font-mono text-[10px] text-slate-400 uppercase"
                                            >{{ slip.code }}</span
                                        >
                                        <p
                                            class="mt-1.5 text-[10px] font-bold text-[#64748b] uppercase"
                                        >
                                            {{ slip.legs }} Selections
                                            Accumulator
                                        </p>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            class="block text-[9px] font-bold text-[#64748b] uppercase"
                                            >Total Odds</span
                                        >
                                        <span
                                            class="font-mono text-sm font-black text-amber-400"
                                            >{{ slip.total_odds }}</span
                                        >
                                    </div>
                                </div>
                                <div
                                    class="flex items-center justify-between border-t border-[#232d42]/40 pt-3"
                                >
                                    <div class="text-left">
                                        <span
                                            class="block text-[8px] font-bold text-[#64748b] uppercase"
                                            >Slip Cost</span
                                        >
                                        <span
                                            class="font-mono text-sm font-black text-emerald-400"
                                            >KES {{ slip.price }}</span
                                        >
                                    </div>
                                    <button
                                        class="cursor-pointer rounded border-b border-amber-700 bg-[#ff8c00] px-3 py-2 text-[11px] font-black tracking-wide text-black uppercase shadow transition-all hover:bg-[#e07b00]"
                                    >
                                        Buy Betslip
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: REVENUE HISTORY, TIME MATRIX & REVIEWS -->
                <div class="space-y-6">
                    <!-- Sales Velocity Metric Box -->
                    <div
                        class="space-y-3 rounded-lg border border-[#232d42] bg-[#161c2a] p-4 shadow-lg"
                    >
                        <h3
                            class="border-b border-[#232d42]/60 pb-2 text-xs font-black tracking-widest text-slate-400 uppercase"
                        >
                            📈 Marketplace Dynamics
                        </h3>
                        <div
                            class="grid grid-cols-2 gap-4 py-1 text-center font-mono"
                        >
                            <div
                                class="rounded border border-[#232d42]/60 bg-[#111622] p-2.5"
                            >
                                <span
                                    class="block text-[8px] font-bold text-[#64748b] uppercase"
                                    >Sold Velocity</span
                                >
                                <span class="text-sm font-black text-slate-200"
                                    >{{ profile.performance.sold_rate }}%</span
                                >
                            </div>
                            <div
                                class="rounded border border-[#232d42]/60 bg-[#111622] p-2.5"
                            >
                                <span
                                    class="block text-[8px] font-bold text-[#64748b] uppercase"
                                    >Last Sale</span
                                >
                                <span
                                    class="text-sm text-xs font-black tracking-tight text-sky-400"
                                    >{{
                                        profile.transaction_history.quick_stats
                                            .last_sale
                                    }}</span
                                >
                            </div>
                        </div>
                        <div class="space-y-2 pt-1 text-xs">
                            <div
                                class="flex justify-between text-[10px] font-bold text-[#64748b] uppercase"
                            >
                                <span>Sales This Week:</span>
                                <span class="font-mono text-slate-300"
                                    >{{
                                        profile.transaction_history.quick_stats
                                            .sales_this_week
                                    }}
                                    Slips</span
                                >
                            </div>
                            <div
                                class="flex justify-between text-[10px] font-bold text-[#64748b] uppercase"
                            >
                                <span>Avg Processing Speed:</span>
                                <span class="font-mono text-slate-300"
                                    >{{
                                        profile.transaction_history.quick_stats
                                            .avg_time_to_sell
                                    }}
                                    Mins</span
                                >
                            </div>
                        </div>
                    </div>

                    <!-- Time Horizon Accuracy Matrix -->
                    <div
                        class="space-y-3 rounded-lg border border-[#232d42] bg-[#161c2a] p-4 shadow-lg"
                    >
                        <h3
                            class="border-b border-[#232d42]/60 pb-2 text-xs font-black tracking-widest text-slate-400 uppercase"
                        >
                            🗓 Accuracy Breakdown Window
                        </h3>
                        <div class="space-y-2 font-mono text-xs">
                            <div
                                v-for="(rate, title) in profile.performance
                                    .win_rate_breakdown"
                                :key="title"
                                class="flex items-center justify-between"
                            >
                                <span
                                    class="font-sans text-[10px] font-bold text-[#64748b] uppercase"
                                    >{{ title.replace(/_/g, ' ') }}:</span
                                >
                                <div
                                    class="flex w-2/3 items-center justify-end gap-3"
                                >
                                    <div
                                        class="h-1.5 w-24 overflow-hidden rounded-full border border-[#232d42] bg-[#111622]"
                                    >
                                        <div
                                            class="h-full bg-emerald-400"
                                            :style="{ width: rate + '%' }"
                                        ></div>
                                    </div>
                                    <span
                                        class="w-10 text-right font-black text-emerald-400"
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
import { usePage } from '@inertiajs/vue3';
import { router } from '@inertiajs/vue3';

const page = usePage();
const seller_data = page.props.seller_data;

const getLocation = () => {
    return 'Kenya';
};

const processMemberSince = (member_since) => {
    const date = new Date(member_since);

    return date.toLocaleDateString('en-US', {
        month: 'long',
        year: 'numeric',
    });
};

const profile = ref(seller_data);

// Extracted generation utility to component reactive state runtime storage context
const generateTrendData = () => {
    const totalDays = 30;
    const rand = (min, max) =>
        Math.round((Math.random() * (max - min) + min) * 10) / 10;
    const data = [];
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - totalDays);

    for (let i = 0; i < totalDays; i++) {
        const date = new Date(startDate);
        date.setDate(date.getDate() + i);

        let value;
        const randVal = Math.random();
        if (randVal < 0.65) {
            value = rand(1, 18);
        } else if (randVal < 0.85) {
            value = rand(-5, -1);
        } else {
            value = 0;
        }

        data.push({
            date: date.toISOString().split('T')[0],
            value: value,
            label: (value >= 0 ? '+' : '') + value + '%',
        });
    }
    return data;
};

// Evaluated trend data variable payload ready to bind directly into component prop tree
const trendData = ref(generateTrendData());

// const toggleFollow = () => {
//     // profile.value.meta.is_following = !profile.value.meta.is_following;
//     // What should be here?
// };


const toggleFollow = () => {
    console.dir(profile.value.seller)
    // Determine target route based on current state
    const url = profile.value.meta.is_following
        ? `/users/${profile.value.seller.id}/unfollow`
        : `/users/${profile.value.seller.id}/follow`;

    // Determine semantic HTTP verb matching web.php definitions
    const method = profile.value.meta.is_following ? 'delete' : 'post';

    router[method](url, {}, {
        preserveScroll: true,
        onStart: () => {
            // Optimistic UI updates (Instant visual layout response for the user)
            profile.value.meta.is_following = !profile.value.meta.is_following;
        },
        onError: (errors) => {
            // Revert state if backend validation layer rejects transaction
            profile.value.meta.is_following = !profile.value.meta.is_following;
            console.error('Follow state transaction failure:', errors);
        }
    });
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
