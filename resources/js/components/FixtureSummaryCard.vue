<template>
    <div
        class="rounded-xl border border-marketplace-border bg-marketplace-card shadow-lg"
    >
        <!-- HEADER -->
        <div
            class="flex items-center justify-between border-b border-marketplace-border/60 bg-marketplace-card/80 p-4"
        >
            <div class="flex items-center space-x-2 text-marketplace-gold">
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2.5"
                    stroke="currentColor"
                    class="h-4 w-4"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-6.75c-.621 0-1.125.504-1.125 1.125v3.375m9 0h-9M9 3.75h6M9 3.75a3 3 0 0 0-3 3v2.25c0 .621.503 1.125 1.125 1.125h6.75A1.125 1.125 0 0 0 15 9.375V6.75a3 3 0 0 0-3-3ZM9 3.75h6m-6 0c0-.621.503-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125M18.02 7.2h-1.5m1.5 0c.394 0 .736.228.892.56L19.8 11.53c.092.191.139.399.139.61v.61c0 .746-.604 1.35-1.35 1.35h-1.432a1.432 1.432 0 0 1-1.35-.97l-.63-1.89A1.432 1.432 0 0 0 13.816 10.5H11.5m-5.32 0h-1.5m1.5 0a1.125 1.125 0 0 1-.892-.44L3.92 7.76a1.125 1.125 0 0 1 .892-.56h1.432c.394 0 .736.228.892.56l1.36 2.74c.092.191.139.399.139.61v.61c0 .746-.604 1.35-1.35 1.35H5.85"
                    />
                </svg>
                <h3
                    class="text-xs font-bold tracking-widest text-white uppercase"
                >
                    Create Your Betslip
                </h3>
            </div>
            <span
                class="text-[10px] font-bold tracking-wider text-marketplace-muted uppercase"
            >
                Make some sales
            </span>
        </div>

        <!-- MAIN BODY -->
        <div
            class="bg-marketplace-bg p-2 font-sans text-marketplace-slate select-none md:p-6"
        >
            <div class="mx-auto max-w-[1200px]">
                <div
                    class="overflow-hidden rounded-lg border border-marketplace-border bg-marketplace-card"
                >
                    <!-- DESKTOP HEADER ROW -->
                    <div
                        class="hidden grid-cols-12 items-center gap-2 border-b border-marketplace-border bg-marketplace-card/60 px-4 py-2 text-center text-[10px] font-bold lg:grid"
                    >
                        <div
                            class="col-span-4 text-left text-marketplace-muted"
                        >
                            MATCH DETAILS
                        </div>
                        <div class="col-span-2 grid grid-cols-3 gap-1">
                            <div class="col-span-3 mb-1 text-marketplace-muted">
                                3 WAY
                            </div>
                            <div>HOME</div>
                            <div>DRAW</div>
                            <div>AWAY</div>
                        </div>
                        <div class="col-span-2 grid grid-cols-3 gap-1">
                            <div class="col-span-3 mb-1 text-marketplace-muted">
                                DOUBLE CHANCE
                            </div>
                            <div>1 OR X</div>
                            <div>X OR 2</div>
                            <div>1 OR 2</div>
                        </div>
                        <div class="col-span-2 grid grid-cols-2 gap-1">
                            <div class="col-span-2 mb-1 text-marketplace-muted">
                                OVER/UNDER 2.5
                            </div>
                            <div>OVER</div>
                            <div>UNDER</div>
                        </div>
                        <div class="col-span-2 grid grid-cols-2 gap-1">
                            <div class="col-span-2 mb-1 text-marketplace-muted">
                                BOTH TEAMS TO SCORE
                            </div>
                            <div>YES</div>
                            <div>NO</div>
                        </div>
                    </div>

                    <!-- LEAGUE BLOCK -->
                    <div
                        v-for="league in leagues"
                        :key="league.id"
                        class="border-b border-marketplace-border last:border-none"
                    >
                        <!-- LEAGUE TITLE HEADER -->
                        <div
                            @click="toggleLeague(league.id)"
                            class="flex cursor-pointer items-center justify-between bg-marketplace-card/60 px-4 py-3 transition-colors select-none hover:bg-marketplace-card/90"
                        >
                            <div class="flex items-center space-x-3">
                                <span
                                    class="h-2 w-2 rounded-full bg-marketplace-gold shadow-[0_0_8px_rgba(245,166,35,0.4)]"
                                ></span>
                                <div class="flex flex-col">
                                    <h4
                                        class="text-xs font-black tracking-widest text-white uppercase"
                                    >
                                        {{ league.name }}
                                    </h4>
                                    <span
                                        class="text-[9px] font-bold tracking-wider text-marketplace-muted uppercase"
                                    >
                                        {{ league.country }} •
                                        {{ league.fixtures?.length ?? 0 }} Matches
                                        Available
                                    </span>
                                </div>
                            </div>

                            <!-- Animated Chevron -->
                            <div
                                :class="[
                                    'rounded p-1 text-marketplace-muted transition-transform duration-200 ease-out hover:bg-marketplace-border/50 hover:text-white',
                                    isLeagueExpanded(league.id)
                                        ? 'rotate-180 text-marketplace-gold'
                                        : '',
                                ]"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="3"
                                    stroke="currentColor"
                                    class="h-3.5 w-3.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m19.5 8.25-7.5 7.5-7.5-7.5"
                                    />
                                </svg>
                            </div>
                        </div>

                        <!-- LEAGUE FIXTURES (COLLAPSIBLE) -->
                        <Transition
                            enter-active-class="transition-[max-height,opacity] duration-300 ease-out"
                            enter-from-class="max-height-0 opacity-0"
                            enter-to-class="max-height-[5000px] opacity-100"
                            leave-active-class="transition-[max-height,opacity] duration-200 ease-in"
                            leave-from-class="max-height-[5000px] opacity-100"
                            leave-to-class="max-height-0 opacity-0"
                        >
                            <div
                                v-show="isLeagueExpanded(league.id)"
                                class="divide-y divide-marketplace-border/50 overflow-hidden"
                            >
                                <div
                                    class="p-4 text-center text-xs font-bold text-marketplace-muted uppercase"
                                >
                                    <div
                                        class="divide-y divide-marketplace-border/50"
                                    >
                                        <div
                                            v-for="match in league.fixtures"
                                            :key="match.id"
                                            class="p-3 lg:p-0 bg--900 mt-6 border-b border-dashed border-marketplace-gold p-3 last:border-0 lg:p-0"
                                        >
                                            <!-- DESKTOP VIEW -->
                                            <div
                                                class="hidden grid-cols-12 items-center gap-2 bg-marketplace-card px-4 py-3 text-center text-xs text-white transition-colors hover:bg-marketplace-card/70 lg:grid"
                                            >
                                                <!-- Match Details -->
                                                <div
                                                    class="col-span-4 flex flex-col justify-center space-y-1 text-left"
                                                >
                                                    <div
                                                        class="flex items-center space-x-1.5 text-[10px] text-marketplace-muted"
                                                    >
                                                        <span
                                                            v-if="match.boosted"
                                                            class="rounded-[3px] bg-marketplace-gold px-1 text-[8px] font-black tracking-wider text-marketplace-bg uppercase"
                                                            >⚡ BOOSTED
                                                            ODDS</span
                                                        >
                                                        <span
                                                            class="text-marketplace-gold"
                                                            >{{
                                                                match.date
                                                            }}</span
                                                        >
                                                        <span
                                                            class="text-marketplace-border"
                                                            >|</span
                                                        >
                                                        <span
                                                            >ID:
                                                            {{
                                                                match.id_on_api
                                                            }}</span
                                                        >
                                                    </div>
                                                    <div
                                                        class="text-[11px] font-bold tracking-wide text-white uppercase"
                                                    >
                                                        <div>
                                                            {{
                                                                match.home_team
                                                            }}
                                                        </div>
                                                        <div>
                                                            {{
                                                                match.away_team
                                                            }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- 3 WAY -->
                                                <div
                                                    class="col-span-2 grid grid-cols-3 gap-1"
                                                >
                                                    <button
                                                        v-for="(odd, idx) in [
                                                            match.odds?.three_way
                                                                ?.home,
                                                            match.odds?.three_way
                                                                ?.draw,
                                                            match.odds?.three_way
                                                                ?.away,
                                                        ]"
                                                        :key="idx"
                                                        @click.stop.prevent="
                                                            () =>
                                                                handleSelectionMade(
                                                                    odd,
                                                                    match,
                                                                    'three_way',
                                                                    idx,
                                                                )
                                                        "
                                                        :class="[
                                                            'cursor-pointer rounded border bg-marketplace-card/80 py-2.5 text-center font-bold text-marketplace-gold transition-all hover:bg-marketplace-border/40',
                                                            isSelected(
                                                                match.id,
                                                                'three_way',
                                                                [
                                                                    'Home',
                                                                    'Draw',
                                                                    'Away',
                                                                ][idx],
                                                            )
                                                                ? 'border-marketplace-gold bg-marketplace-gold/20 text-white ring-1 ring-marketplace-gold/40'
                                                                : 'border-transparent',
                                                        ]"
                                                    >
                                                        {{ formatOdd(odd?.value) }}
                                                    </button>
                                                </div>

                                                <!-- DOUBLE CHANCE -->
                                                <div
                                                    class="col-span-2 grid grid-cols-3 gap-1"
                                                >
                                                    <button
                                                        v-for="(odd, idx) in [
                                                            match.odds
                                                                ?.double_chance
                                                                ?.one_x,
                                                            match.odds
                                                                ?.double_chance
                                                                ?.x_two,
                                                            match.odds
                                                                ?.double_chance
                                                                ?.one_two,
                                                        ]"
                                                        :key="idx"
                                                        @click.stop.prevent="
                                                            () =>
                                                                handleSelectionMade(
                                                                    odd,
                                                                    match,
                                                                    'double_chance',
                                                                    idx,
                                                                )
                                                        "
                                                        :class="[
                                                            'cursor-pointer rounded border bg-marketplace-card/80 py-2.5 text-center font-bold text-marketplace-slate transition-colors hover:bg-marketplace-border/40',
                                                            isSelected(
                                                                match.id,
                                                                'double_chance',
                                                                [
                                                                    'one_x',
                                                                    'x_two',
                                                                    'one_two',
                                                                ][idx],
                                                            )
                                                                ? 'border-marketplace-gold bg-marketplace-gold/20 text-white ring-1 ring-marketplace-gold/40'
                                                                : 'border-transparent',
                                                        ]"
                                                    >
                                                        {{ formatOdd(odd?.value) }}
                                                    </button>
                                                </div>

                                                <!-- OVER/UNDER 2.5 -->
                                                <div
                                                    class="col-span-2 grid grid-cols-2 gap-1"
                                                >
                                                    <button
                                                        @click.stop.prevent="
                                                            () =>
                                                                handleSelectionMade(
                                                                    match.odds
                                                                        ?.over_under
                                                                        ?.over,
                                                                    match,
                                                                    'over_under_2.5',
                                                                    'Over',
                                                                )
                                                        "
                                                        :class="[
                                                            'cursor-pointer rounded border bg-marketplace-card/80 py-2.5 text-center font-bold text-marketplace-slate transition-colors hover:bg-marketplace-border/40',
                                                            isSelected(
                                                                match.id,
                                                                'over_under_2.5',
                                                                'Over',
                                                            )
                                                                ? 'border-marketplace-gold bg-marketplace-gold/20 text-white ring-1 ring-marketplace-gold/40'
                                                                : 'border-transparent',
                                                        ]"
                                                    >
                                                        {{
                                                            formatOdd(
                                                                match.odds
                                                                    ?.over_under
                                                                    ?.over?.value,
                                                            )
                                                        }}
                                                    </button>
                                                    <button
                                                        @click.stop.prevent="
                                                            () =>
                                                                handleSelectionMade(
                                                                    match.odds
                                                                        ?.over_under
                                                                        ?.under,
                                                                    match,
                                                                    'over_under_2.5',
                                                                    'Under',
                                                                )
                                                        "
                                                        :class="[
                                                            'cursor-pointer rounded border bg-marketplace-card/80 py-2.5 text-center font-bold text-marketplace-slate transition-colors hover:bg-marketplace-border/40',
                                                            isSelected(
                                                                match.id,
                                                                'over_under_2.5',
                                                                'Under',
                                                            )
                                                                ? 'border-marketplace-gold bg-marketplace-gold/20 text-white ring-1 ring-marketplace-gold/40'
                                                                : 'border-transparent',
                                                        ]"
                                                    >
                                                        {{
                                                            formatOdd(
                                                                match.odds
                                                                    ?.over_under
                                                                    ?.under?.value,
                                                            )
                                                        }}
                                                    </button>
                                                </div>

                                                <!-- BTTS -->
                                                <div
                                                    class="group relative col-span-2 grid grid-cols-2 gap-1"
                                                >
                                                    <button
                                                        @click.stop.prevent="
                                                            () =>
                                                                handleSelectionMade(
                                                                    match.odds
                                                                        ?.both_team_to_score
                                                                        ?.yes,
                                                                    match,
                                                                    'both_team_to_score',
                                                                    'Yes',
                                                                )
                                                        "
                                                        :class="[
                                                            'cursor-pointer rounded border bg-marketplace-card/80 py-2.5 text-center font-bold text-marketplace-slate transition-colors hover:bg-marketplace-border/40',
                                                            isSelected(
                                                                match.id,
                                                                'both_team_to_score',
                                                                'Yes',
                                                            )
                                                                ? 'border-marketplace-gold bg-marketplace-gold/20 text-white ring-1 ring-marketplace-gold/40'
                                                                : 'border-transparent',
                                                        ]"
                                                    >
                                                        {{
                                                            formatOdd(
                                                                match.odds
                                                                    ?.both_team_to_score
                                                                    ?.yes?.value,
                                                            )
                                                        }}
                                                    </button>
                                                    <button
                                                        @click.stop.prevent="
                                                            () =>
                                                                handleSelectionMade(
                                                                    match.odds
                                                                        ?.both_team_to_score
                                                                        ?.no,
                                                                    match,
                                                                    'both_team_to_score',
                                                                    'No',
                                                                )
                                                        "
                                                        :class="[
                                                            'cursor-pointer rounded border bg-marketplace-card/80 py-2.5 text-center font-bold text-marketplace-slate transition-colors hover:bg-marketplace-border/40',
                                                            isSelected(
                                                                match.id,
                                                                'both_team_to_score',
                                                                'No',
                                                            )
                                                                ? 'border-marketplace-gold bg-marketplace-gold/20 text-white ring-1 ring-marketplace-gold/40'
                                                                : 'border-transparent',
                                                        ]"
                                                    >
                                                        {{
                                                            formatOdd(
                                                                match.odds
                                                                    ?.both_team_to_score
                                                                    ?.no?.value,
                                                            )
                                                        }}
                                                    </button>
                                                    <div
                                                        @click="
                                                            viewFixture(
                                                                match.id_on_api,
                                                            )
                                                        "
                                                        class="absolute top-1/2 -right-6 h-5 w-5 -translate-y-1/2 cursor-pointer rounded-xl bg-marketplace-gold/80 px-1 text-sm font-bold text-marketplace-bg opacity-60 transition-opacity group-hover:opacity-100"
                                                    >
                                                        ❯
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- MOBILE VIEW -->
                                            <div
                                                class="block text-white lg:hidden"
                                            >
                                                <div
                                                    class="mb-3 flex items-start justify-between"
                                                    @click="
                                                        viewFixture(
                                                            match.id_on_api,
                                                        )
                                                    "
                                                >
                                                    <div>
                                                        <div
                                                            v-if="match.boosted"
                                                            class="mb-1 inline-block rounded-[3px] bg-marketplace-gold px-1 py-0.5 text-[8px] font-black tracking-wider text-marketplace-bg uppercase"
                                                        >
                                                            ⚡ BOOSTED ODDS
                                                        </div>
                                                        <div
                                                            class="space-y-0.5 text-xs font-bold tracking-wide text-white uppercase md:text-sm"
                                                        >
                                                            <div
                                                                class="flex w-full flex-col items-start justify-start gap-1.5 select-none"
                                                            >
                                                                <div
                                                                    class="space-y-0.5 text-left text-xs font-black tracking-widest text-white uppercase md:text-sm"
                                                                >
                                                                    <div
                                                                        class="flex items-center gap-2"
                                                                    >
                                                                        <span
                                                                            class="text-[10px] text-marketplace-muted"
                                                                            >H</span
                                                                        >
                                                                        <span>{{
                                                                            match.home_team
                                                                        }}</span>
                                                                    </div>
                                                                    <div
                                                                        class="flex items-center gap-2"
                                                                    >
                                                                        <span
                                                                            class="text-[10px] text-marketplace-muted"
                                                                            >A</span
                                                                        >
                                                                        <span>{{
                                                                            match.away_team
                                                                        }}</span>
                                                                    </div>
                                                                </div>

                                                                <div
                                                                    class="flex items-center justify-start gap-1.5 text-[10px] font-bold tracking-wider text-marketplace-muted uppercase"
                                                                >
                                                                    <span
                                                                        class="text-marketplace-gold"
                                                                        >{{
                                                                            match.date
                                                                        }}</span
                                                                    >
                                                                    <span
                                                                        class="font-normal text-marketplace-border"
                                                                        >|</span
                                                                    >
                                                                    <span
                                                                        >ID:
                                                                        <span
                                                                            class="font-mono font-semibold text-marketplace-slate"
                                                                            >{{
                                                                                match.id_on_api
                                                                            }}</span
                                                                        ></span
                                                                    >
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="flex items-center space-x-1.5 self-center"
                                                    >
                                                        <button
                                                            class="rounded bg-marketplace-card/80 p-2 text-xs text-marketplace-slate hover:bg-marketplace-border/40"
                                                        >
                                                            📊
                                                        </button>
                                                        <button
                                                            class="rounded bg-marketplace-card/80 px-3 py-1.5 text-xs font-bold text-marketplace-slate hover:bg-marketplace-border/40"
                                                        >
                                                            {{
                                                                match.additional_markets_count
                                                            }}
                                                        </button>
                                                    </div>
                                                </div>

                                                <div class="space-y-3 text-xs">
                                                    <!-- 3 Way -->
                                                    <div>
                                                        <div
                                                            class="rounded-t border-b border-marketplace-border/50 bg-marketplace-card/60 px-3 py-1.5 text-[10px] font-bold tracking-wider text-marketplace-slate uppercase"
                                                        >
                                                            3 Way
                                                        </div>
                                                        <div
                                                            class="grid grid-cols-3 divide-x divide-marketplace-border/50 overflow-hidden rounded-b bg-marketplace-card/80"
                                                        >
                                                            <button
                                                                :class="[
                                                                    'rounded p-2.5 text-center hover:bg-marketplace-border/30',
                                                                    isSelected(
                                                                        match.id,
                                                                        'three_way',
                                                                        'Home',
                                                                    )
                                                                        ? 'border border-marketplace-gold bg-marketplace-gold/20'
                                                                        : '',
                                                                ]"
                                                                @click.stop.prevent="
                                                                    () =>
                                                                        handleSelectionMade(
                                                                            match
                                                                                .odds
                                                                                ?.three_way
                                                                                ?.home,
                                                                            match,
                                                                            'three_way',
                                                                            0,
                                                                        )
                                                                "
                                                            >
                                                                <div
                                                                    class="truncate px-0.5 text-[9px] tracking-tight text-marketplace-muted uppercase"
                                                                >
                                                                    {{
                                                                        match.home_team
                                                                    }}
                                                                </div>
                                                                <div
                                                                    class="mt-0.5 font-bold text-marketplace-gold"
                                                                >
                                                                    {{
                                                                        formatOdd(
                                                                            match
                                                                                .odds
                                                                                ?.three_way
                                                                                ?.home
                                                                                ?.value,
                                                                        )
                                                                    }}
                                                                </div>
                                                            </button>
                                                            <button
                                                                class="p-2.5 text-center hover:bg-marketplace-border/30"
                                                                :class="[
                                                                    'rounded p-2.5 text-center hover:bg-marketplace-border/30',
                                                                    isSelected(
                                                                        match.id,
                                                                        'three_way',
                                                                        'Draw',
                                                                    )
                                                                        ? 'border border-marketplace-gold bg-marketplace-gold/20'
                                                                        : '',
                                                                ]"
                                                                @click.stop.prevent="
                                                                    () =>
                                                                        handleSelectionMade(
                                                                            match
                                                                                .odds
                                                                                ?.three_way
                                                                                ?.draw,
                                                                            match,
                                                                            'three_way',
                                                                            1,
                                                                        )
                                                                "
                                                            >
                                                                <div
                                                                    class="text-[9px] tracking-tight text-marketplace-muted uppercase"
                                                                >
                                                                    Draw
                                                                </div>
                                                                <div
                                                                    class="mt-0.5 font-bold text-marketplace-gold"
                                                                >
                                                                    {{
                                                                        formatOdd(
                                                                            match
                                                                                .odds
                                                                                ?.three_way
                                                                                ?.draw
                                                                                ?.value,
                                                                        )
                                                                    }}
                                                                </div>
                                                            </button>
                                                            <button
                                                                class="p-2.5 text-center hover:bg-marketplace-border/30"
                                                                :class="[
                                                                    'rounded p-2.5 text-center hover:bg-marketplace-border/30',
                                                                    isSelected(
                                                                        match.id,
                                                                        'three_way',
                                                                        'Away',
                                                                    )
                                                                        ? 'border border-marketplace-gold bg-marketplace-gold/20'
                                                                        : '',
                                                                ]"
                                                                @click.stop.prevent="
                                                                    () =>
                                                                        handleSelectionMade(
                                                                            match
                                                                                .odds
                                                                                ?.three_way
                                                                                ?.away,
                                                                            match,
                                                                            'three_way',
                                                                            2,
                                                                        )
                                                                "
                                                            >
                                                                <div
                                                                    class="truncate px-0.5 text-[9px] tracking-tight text-marketplace-muted uppercase"
                                                                >
                                                                    {{
                                                                        match.away_team
                                                                    }}
                                                                </div>
                                                                <div
                                                                    class="mt-0.5 font-bold text-marketplace-gold"
                                                                >
                                                                    {{
                                                                        formatOdd(
                                                                            match
                                                                                .odds
                                                                                ?.three_way
                                                                                ?.away
                                                                                ?.value,
                                                                        )
                                                                    }}
                                                                </div>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Double Chance -->
                                                    <div>
                                                        <div
                                                            class="rounded-t border-b border-marketplace-border/50 bg-marketplace-card/60 px-3 py-1.5 text-[10px] font-bold tracking-wider text-marketplace-slate uppercase"
                                                        >
                                                            Double Chance
                                                        </div>
                                                        <div
                                                            class="grid grid-cols-3 divide-x divide-marketplace-border/50 overflow-hidden rounded-b bg-marketplace-card/80"
                                                        >
                                                            <button
                                                                class="p-2.5 text-center hover:bg-marketplace-border/30"
                                                                @click.stop.prevent="
                                                                    () =>
                                                                        handleSelectionMade(
                                                                            match
                                                                                .odds
                                                                                ?.double_chance
                                                                                ?.one_x,
                                                                            match,
                                                                            'double_chance',
                                                                            0,
                                                                        )
                                                                "
                                                                :class="[
                                                                    'cursor-pointer rounded border bg-marketplace-card/80 py-2.5 text-center font-bold text-marketplace-slate transition-colors hover:bg-marketplace-border/30',
                                                                    isSelected(
                                                                        match.id,
                                                                        'double_chance',
                                                                        'one_x',
                                                                    )
                                                                        ? 'border-marketplace-gold bg-marketplace-gold/20 text-white'
                                                                        : 'border-transparent',
                                                                ]"
                                                            >
                                                                <div
                                                                    class="text-[9px] text-marketplace-muted"
                                                                >
                                                                    1 or X
                                                                </div>
                                                                <div
                                                                    class="mt-0.5 font-bold text-marketplace-slate"
                                                                >
                                                                    {{
                                                                        formatOdd(
                                                                            match
                                                                                .odds
                                                                                ?.double_chance
                                                                                ?.one_x
                                                                                ?.value,
                                                                        )
                                                                    }}
                                                                </div>
                                                            </button>
                                                            <button
                                                                class="p-2.5 text-center hover:bg-marketplace-border/30"
                                                                @click.stop.prevent="
                                                                    () =>
                                                                        handleSelectionMade(
                                                                            match
                                                                                .odds
                                                                                ?.double_chance
                                                                                ?.x_two,
                                                                            match,
                                                                            'double_chance',
                                                                            1,
                                                                        )
                                                                "
                                                                :class="[
                                                                    'cursor-pointer rounded border bg-marketplace-card/80 py-2.5 text-center font-bold text-marketplace-slate transition-colors hover:bg-marketplace-border/30',
                                                                    isSelected(
                                                                        match.id,
                                                                        'double_chance',
                                                                        'x_two',
                                                                    )
                                                                        ? 'border-marketplace-gold bg-marketplace-gold/20 text-white'
                                                                        : 'border-transparent',
                                                                ]"
                                                            >
                                                                <div
                                                                    class="text-[9px] text-marketplace-muted"
                                                                >
                                                                    X or 2
                                                                </div>
                                                                <div
                                                                    class="mt-0.5 font-bold text-marketplace-slate"
                                                                >
                                                                    {{
                                                                        formatOdd(
                                                                            match
                                                                                .odds
                                                                                ?.double_chance
                                                                                ?.x_two
                                                                                ?.value,
                                                                        )
                                                                    }}
                                                                </div>
                                                            </button>
                                                            <button
                                                                class="p-2.5 text-center hover:bg-marketplace-border/30"
                                                                @click.stop.prevent="
                                                                    () =>
                                                                        handleSelectionMade(
                                                                            match
                                                                                .odds
                                                                                ?.double_chance
                                                                                ?.one_two,
                                                                            match,
                                                                            'double_chance',
                                                                            2,
                                                                        )
                                                                "
                                                                :class="[
                                                                    'cursor-pointer rounded border bg-marketplace-card/80 py-2.5 text-center font-bold text-marketplace-slate transition-colors hover:bg-marketplace-border/30',
                                                                    isSelected(
                                                                        match.id,
                                                                        'double_chance',
                                                                        'one_two',
                                                                    )
                                                                        ? 'border-marketplace-gold bg-marketplace-gold/20 text-white'
                                                                        : 'border-transparent',
                                                                ]"
                                                            >
                                                                <div
                                                                    class="text-[9px] text-marketplace-muted"
                                                                >
                                                                    1 or 2
                                                                </div>
                                                                <div
                                                                    class="mt-0.5 font-bold text-marketplace-slate"
                                                                >
                                                                    {{
                                                                        formatOdd(
                                                                            match
                                                                                .odds
                                                                                ?.double_chance
                                                                                ?.one_two
                                                                                ?.value,
                                                                        )
                                                                    }}
                                                                </div>
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <!-- Over/Under & BTTS -->
                                                    <div
                                                        class="grid grid-cols-2 gap-2"
                                                    >
                                                        <!-- Over/Under -->
                                                        <div>
                                                            <div
                                                                class="truncate rounded-t bg-marketplace-card/60 px-3 py-1.5 text-[10px] font-bold text-marketplace-slate uppercase"
                                                            >
                                                                Over/Under 2.5
                                                            </div>
                                                            <div
                                                                class="grid grid-cols-2 divide-x divide-marketplace-border/50 overflow-hidden rounded-b bg-marketplace-card/80"
                                                            >
                                                                <button
                                                                    class="p-2.5 text-center hover:bg-marketplace-border/30"
                                                                    @click.stop.prevent="
                                                                        () =>
                                                                            handleSelectionMade(
                                                                                match
                                                                                    .odds
                                                                                    ?.over_under
                                                                                    ?.over,
                                                                                match,
                                                                                'over_under_2.5',
                                                                                'Over',
                                                                            )
                                                                    "
                                                                    :class="[
                                                                        'cursor-pointer rounded border bg-marketplace-card/80 py-2.5 text-center font-bold text-marketplace-slate transition-colors hover:bg-marketplace-border/30',
                                                                        isSelected(
                                                                            match.id,
                                                                            'over_under_2.5',
                                                                            'Over',
                                                                        )
                                                                            ? 'border-marketplace-gold bg-marketplace-gold/20 text-white'
                                                                            : 'border-transparent',
                                                                    ]"
                                                                >
                                                                    <div
                                                                        class="text-[9px] text-marketplace-muted"
                                                                    >
                                                                        Over
                                                                        2.50
                                                                    </div>
                                                                    <div
                                                                        class="mt-0.5 font-bold text-marketplace-slate"
                                                                    >
                                                                        {{
                                                                            formatOdd(
                                                                                match
                                                                                    .odds
                                                                                    ?.over_under
                                                                                    ?.over
                                                                                    ?.value,
                                                                            )
                                                                        }}
                                                                    </div>
                                                                </button>
                                                                <button
                                                                    class="p-2.5 text-center hover:bg-marketplace-border/30"
                                                                    @click.stop.prevent="
                                                                        () =>
                                                                            handleSelectionMade(
                                                                                match
                                                                                    .odds
                                                                                    ?.over_under
                                                                                    ?.under,
                                                                                match,
                                                                                'over_under_2.5',
                                                                                'Under',
                                                                            )
                                                                    "
                                                                    :class="[
                                                                        'cursor-pointer rounded border bg-marketplace-card/80 py-2.5 text-center font-bold text-marketplace-slate transition-colors hover:bg-marketplace-border/30',
                                                                        isSelected(
                                                                            match.id,
                                                                            'over_under_2.5',
                                                                            'Under',
                                                                        )
                                                                            ? 'border-marketplace-gold bg-marketplace-gold/20 text-white'
                                                                            : 'border-transparent',
                                                                    ]"
                                                                >
                                                                    <div
                                                                        class="text-[9px] text-marketplace-muted"
                                                                    >
                                                                        Under
                                                                        2.50
                                                                    </div>
                                                                    <div
                                                                        class="mt-0.5 font-bold text-marketplace-slate"
                                                                    >
                                                                        {{
                                                                            formatOdd(
                                                                                match
                                                                                    .odds
                                                                                    ?.over_under
                                                                                    ?.under
                                                                                    ?.value,
                                                                            )
                                                                        }}
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>

                                                        <!-- BTTS -->
                                                        <div>
                                                            <div
                                                                class="truncate rounded-t bg-marketplace-card/60 px-3 py-1.5 text-[10px] font-bold text-marketplace-slate uppercase"
                                                            >
                                                                Both Teams To
                                                                Score
                                                            </div>
                                                            <div
                                                                class="grid grid-cols-2 divide-x divide-marketplace-border/50 overflow-hidden rounded-b bg-marketplace-card/80"
                                                            >
                                                                <button
                                                                    class="p-2.5 text-center hover:bg-marketplace-border/30"
                                                                    @click.stop.prevent="
                                                                        () =>
                                                                            handleSelectionMade(
                                                                                match
                                                                                    .odds
                                                                                    ?.both_team_to_score
                                                                                    ?.yes,
                                                                                match,
                                                                                'both_team_to_score',
                                                                                'Yes',
                                                                            )
                                                                    "
                                                                    :class="[
                                                                        'cursor-pointer rounded border bg-marketplace-card/80 py-2.5 text-center font-bold text-marketplace-slate transition-colors hover:bg-marketplace-border/30',
                                                                        isSelected(
                                                                            match.id,
                                                                            'both_team_to_score',
                                                                            'Yes',
                                                                        )
                                                                            ? 'border-marketplace-gold bg-marketplace-gold/20 text-white'
                                                                            : 'border-transparent',
                                                                    ]"
                                                                >
                                                                    <div
                                                                        class="text-[9px] text-marketplace-muted"
                                                                    >
                                                                        Yes (GG)
                                                                    </div>
                                                                    <div
                                                                        class="mt-0.5 font-bold text-marketplace-slate"
                                                                    >
                                                                        {{
                                                                            formatOdd(
                                                                                match
                                                                                    .odds
                                                                                    ?.both_team_to_score
                                                                                    ?.yes
                                                                                    ?.value,
                                                                            )
                                                                        }}
                                                                    </div>
                                                                </button>
                                                                <button
                                                                    class="p-2.5 text-center hover:bg-marketplace-border/30"
                                                                    @click.stop.prevent="
                                                                        () =>
                                                                            handleSelectionMade(
                                                                                match
                                                                                    .odds
                                                                                    ?.both_team_to_score
                                                                                    ?.no,
                                                                                match,
                                                                                'both_team_to_score',
                                                                                'No',
                                                                            )
                                                                    "
                                                                    :class="[
                                                                        'cursor-pointer rounded border bg-marketplace-card/80 py-2.5 text-center font-bold text-marketplace-slate transition-colors hover:bg-marketplace-border/30',
                                                                        isSelected(
                                                                            match.id,
                                                                            'both_team_to_score',
                                                                            'No',
                                                                        )
                                                                            ? 'border-marketplace-gold bg-marketplace-gold/20 text-white'
                                                                            : 'border-transparent',
                                                                    ]"
                                                                >
                                                                    <div
                                                                        class="text-[9px] text-marketplace-muted"
                                                                    >
                                                                        No (NG)
                                                                    </div>
                                                                    <div
                                                                        class="mt-0.5 font-bold text-marketplace-slate"
                                                                    >
                                                                        {{
                                                                            formatOdd(
                                                                                match
                                                                                    .odds
                                                                                    ?.both_team_to_score
                                                                                    ?.no
                                                                                    ?.value,
                                                                            )
                                                                        }}
                                                                    </div>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { defineProps, onMounted, ref, computed, onBeforeUnmount } from 'vue';

// Using a Set for constant O(1) time lookups across deep lists
const expandedLeagueIds = ref(new Set([1])); // Open the first league by default

const isLeagueExpanded = (id) => {
    return expandedLeagueIds.value.has(id);
};

const toggleLeague = (id) => {
    if (expandedLeagueIds.value.has(id)) {
        expandedLeagueIds.value.delete(id);
    } else {
        expandedLeagueIds.value.add(id);
    }
};

// Define the incoming data array structure as a prop
const props = defineProps({
    leagues: {
        type: Array,
        required: true,
        default: () => [],
    },
});
const selections = ref([]);

// Create a Set for O(1) lookups
const selectedSet = computed(() => {
    const set = new Set();
    selections.value.forEach((sel) => {
        set.add(`${sel.fixture_id}-${sel.market_name}-${sel.selection}`);
    });
    return set;
});

const isSelected = (fixture_id, market_name, selectionx) => {
    return selectedSet.value.has(`${fixture_id}-${market_name}-${selectionx}`);
};

const viewFixture = (id) => {
    window.location.href = `/fixture/${id}`;
};

// Safely format a numeric odd value to two decimal places.
// Handles strings (from MySQL DECIMAL), numbers, null, undefined, NaN.
const formatOdd = (value) => {
    const n = Number(value);
    return Number.isFinite(n) ? n.toFixed(2) : '—';
};

const updateSelections = () => {
    selections.value = getSelectionsFromLocalStorage();
};

const handleSelectionMade = (odd, match, type, index) => {
    // Coerce to number and validate
    const value = Number(odd?.value);

    if (!odd || !Number.isFinite(value)) {
        console.error('⚠️ Invalid odd value:', odd);
        return;
    }

    let odd_value = null;
    if (type == 'three_way') {
        const values = ['Home', 'Draw', 'Away'];
        odd_value = values[index];
    } else if (type == 'double_chance') {
        const values = ['one_x', 'x_two', 'one_two'];
        odd_value = values[index];
    } else if (type == 'over_under_2.5') {
        odd_value = index;
    } else if (type == 'both_team_to_score') {
        odd_value = index;
    }

    const selection = {
        fixture_id: match.id,
        home_team: match.home_team,
        away_team: match.away_team,
        market_name: type,
        selection: odd_value,
        odds: { ...odd, value }, // store numeric value
    };

    console.log('Selection', selection);

    // Save to localStorage
    saveSelectionToLocalStorage(selection);
};

const saveSelectionToLocalStorage = (selection) => {
    try {
        const stored_selections = localStorage.getItem('bet_selections');
        let selections = [];

        if (stored_selections) {
            selections = JSON.parse(stored_selections);
            if (!Array.isArray(selections)) {
                selections = [];
            }
        }

        // Check if selection exists, if so remove it (toggle)
        const existingIndex = selections.findIndex(
            (item) =>
                item.fixture_id === selection.fixture_id &&
                item.market_name === selection.market_name,
        );

        if (existingIndex !== -1) {
            selections.splice(existingIndex, 1);
        }

        // Add the new selection
        selections.push(selection);

        // Save back to localStorage
        localStorage.setItem('bet_selections', JSON.stringify(selections));

        // Dispatch event for other components
        window.dispatchEvent(
            new CustomEvent('betSelectionUpdated', {
                detail: { selections, selection },
            }),
        );
    } catch (error) {
        console.error('❌ Error saving selection to localStorage:', error);
    }
};

// Helper function to get all selections from localStorage
const getSelectionsFromLocalStorage = () => {
    try {
        const stored_selections = localStorage.getItem('bet_selections');
        if (stored_selections) {
            const selections = JSON.parse(stored_selections);
            return Array.isArray(selections) ? selections : [];
        }
        return [];
    } catch (error) {
        console.error('❌ Error reading selections from localStorage:', error);
        return [];
    }
};

// Expose to window for debugging
window.handleSelectionMade = handleSelectionMade;

onMounted(() => {
    updateSelections();
    window.addEventListener('betSelectionUpdated', updateSelections);
});

onBeforeUnmount(() => {
    window.removeEventListener('betSelectionUpdated', updateSelections);
});
</script>