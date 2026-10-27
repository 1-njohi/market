<template>
    <SectionWrapper v-if="cards.length > 0">
        <div class="flex items-end justify-between gap-4">
            <SectionHeading align="left" :eyebrow="copy.eyebrow" :title="copy.title" :subtitle="copy.subtitle" />

            <!-- Controls: help first, arrows after (desktop, and only when there is something to scroll) -->
            <div class="flex shrink-0 items-center gap-2">
                <InfoPopover size="md" :label="copy.infoTitle" :title="copy.infoTitle">
                    <p v-for="(row, i) in copy.info" :key="i">
                        <span class="font-bold text-slate-100">{{ row.lead }}</span>
                        {{ row.text }}
                    </p>
                </InfoPopover>

                <div v-if="!featured" class="hidden items-center gap-2 md:flex">
                    <button
                        type="button"
                        @click="scrollByCards(-1)"
                        :disabled="!canPrev"
                        aria-label="Previous"
                        class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-slate-200 transition-all duration-200 hover:border-amber-300/50 hover:text-amber-200 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-95 disabled:pointer-events-none disabled:opacity-30"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="scrollByCards(1)"
                        :disabled="!canNext"
                        aria-label="Next"
                        class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-slate-200 transition-all duration-200 hover:border-amber-300/50 hover:text-amber-200 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-95 disabled:pointer-events-none disabled:opacity-30"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- One round: a single wide card, no carousel -->
        <div v-if="featured" class="mt-10">
            <OpenRoundCard :round="cards[0]" :wide="true" />
        </div>

        <!-- Several rounds: swipeable carousel, same behaviour as the public contests one -->
        <template v-else>
            <div class="relative mt-10 -mx-5 md:mx-0">
                <div
                    ref="scroller"
                    @scroll.passive="onScroll"
                    role="region"
                    aria-roledescription="carousel"
                    :aria-label="copy.infoTitle"
                    class="no-scrollbar flex snap-x snap-mandatory items-stretch gap-4 overflow-x-auto scroll-smooth px-5 pb -4 md:px-0 motion-reduce:scroll-auto"
                    style="scroll-padding-inline: 1.25rem"
                >
                    <div v-for="c in cards" :key="c.key" class="flex w-[84%] shrink-0 snap-start sm:w-[24rem]">
                        <OpenRoundCard :round="c" class="w-full" />
                    </div>
                </div>

                <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-24 bg-gradient-to-l from-[#0b0f19] to-transparent transition-opacity duration-300 md:block" :class="canNext ? 'opacity-100' : 'opacity-0'" aria-hidden="true"></div>
            </div>

            <div class="mt-4 flex items-center justify-center gap-1" role="group" aria-label="Choose one">
                <button
                    v-for="(c, i) in cards"
                    :key="c.key"
                    type="button"
                    @click="scrollToIndex(i)"
                    :aria-label="`Go to ${i + 1} of ${cards.length}`"
                    :aria-current="activeIndex === i ? 'true' : undefined"
                    class="group flex h-8 w-8 cursor-pointer items-center justify-center focus-visible:outline-none"
                >
                    <span
                        class="h-2 rounded-full transition-all duration-300 group-focus-visible:ring-2 group-focus-visible:ring-amber-300"
                        :class="activeIndex === i ? 'w-6 bg-amber-300' : 'w-2 bg-white/20 group-hover:bg-white/40'"
                    ></span>
                </button>
            </div>
        </template>
    </SectionWrapper>
</template>

<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import SectionWrapper from './SectionWrapper.vue';
import SectionHeading from './SectionHeading.vue';
import OpenRoundCard from './OpenRoundCard.vue';
import InfoPopover from '../InfoPopover.vue';

// `rounds` is the raw payload: $page.props.open_matchdays or $page.props.open_jackpots.
const props = defineProps<{
    kind: 'matchday' | 'jackpot';
    rounds: Record<string, any>[];
}>();

// ── PAYLOAD → CARD ───────────────────────────────────────────
const cards = computed(() =>
    (props.rounds ?? []).map((r) =>
        props.kind === 'matchday'
            ? {
                  key: r.uuid,
                  // Entered matchdays go straight to your picks; otherwise to the matchday page.
                  href: r.is_joined ? `/contests/${r.uuid}/picks` : '/matchday',
                  accent: 'sky' as const,
                  chip: `Matchday ${r.matchday_number}`,
                  perk: 'No entry fee',
                  title: r.name,
                  subtitle: [r.league_code, r.season].filter(Boolean).join(' · ') || null,
                  legs: r.legs_count,
                  entries: r.entries_count,
                  entriesLabel: 'Entered',
                  prizePool: 0,
                  preview: r.fixture_preview ?? [],
                  deadline: r.locks_at,
                  deadlineLabel: 'Locks in',
                  isJoined: r.is_joined,
                  ctaLabel: r.is_joined ? (r.has_picks ? 'View your picks' : 'Make your picks') : 'Join matchday',
              }
            : {
                  key: r.uuid,
                  href: `/jackpots/${r.uuid}`,
                  accent: 'purple' as const,
                  chip: 'Jackpot',
                  perk: 'Open to everyone',
                  title: r.name,
                  subtitle: r.description ?? null,
                  legs: r.legs_count,
                  entries: r.entries_count,
                  entriesLabel: 'Entered',
                  prizePool: r.prize_pool ?? 0,
                  preview: r.fixture_preview ?? [],
                  deadline: r.entry_deadline_at,
                  deadlineLabel: 'Entries close',
                  isJoined: r.is_joined,
                  ctaLabel: r.is_joined ? 'View jackpot' : 'Enter jackpot',
              },
    ),
);

// One round gets a wide featured card; two or more become a carousel.
const featured = computed(() => cards.value.length === 1);

// ── HEADING + POPOVER COPY ───────────────────────────────────
const copy = computed(() =>
    props.kind === 'matchday'
        ? {
              eyebrow: 'Matchday',
              title: cards.value.length > 1 ? 'The matchdays are open.' : 'The matchday is open.',
              subtitle: 'No entry fee. Make a pick on every fixture before it locks.',
              infoTitle: 'How matchdays work',
              info: [
                  { lead: 'What it is:', text: 'A fresh set of fixtures every weekend. One pick per game, usually 10 picks at once. No entry fee.' },
                  { lead: 'Sub-leagues:', text: "If you're in a sub-league, you're entered automatically. If not, join one with an invite code." },
                  { lead: 'Your record:', text: 'Every pick counts toward your record once its game has finished.' },
              ],
          }
        : {
              eyebrow: 'Jackpots',
              title: 'Take on the crowd.',
              subtitle: 'Pick your legs, commit before the reveal, and see how the crowd read the fixtures.',
              infoTitle: 'How jackpots work',
              info: [
                  { lead: 'What it is:', text: 'Enter the public crowd. Pick every leg with confidence and commit before the reveal.' },
                  { lead: 'The reveal:', text: 'Commit your picks before the reveal, then see how the crowd read the same fixtures.' },
                  { lead: 'Your record:', text: 'Jackpot picks build your record like any other pick, once their games have finished.' },
              ],
          },
);

// ── CAROUSEL ─────────────────────────────────────────────────
const GAP = 16; // matches gap-4

const scroller = ref<HTMLElement | null>(null);
const activeIndex = ref(0);
const canPrev = ref(false);
const canNext = ref(false);

let frame = 0;

const cardStep = (): number => {
    const first = scroller.value?.firstElementChild as HTMLElement | null;
    return first ? first.getBoundingClientRect().width + GAP : 0;
};

const update = () => {
    const el = scroller.value;
    if (!el) return;

    const step = cardStep() || 1;
    const maxScroll = el.scrollWidth - el.clientWidth;

    canPrev.value = el.scrollLeft > 4;
    canNext.value = el.scrollLeft < maxScroll - 4;

    // At the far end the last card can never reach the start edge, so pin the last dot.
    const total = el.children.length;
    activeIndex.value = canNext.value ? Math.round(el.scrollLeft / step) : total - 1;
};

const onScroll = () => {
    cancelAnimationFrame(frame);
    frame = requestAnimationFrame(update);
};

const behavior = (): ScrollBehavior =>
    window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth';

const scrollByCards = (dir: 1 | -1) => {
    scroller.value?.scrollBy({ left: dir * cardStep(), behavior: behavior() });
};

const scrollToIndex = (i: number) => {
    scroller.value?.scrollTo({ left: i * cardStep(), behavior: behavior() });
};

onMounted(() => {
    update();
    window.addEventListener('resize', update);
});

// The scroller only exists in carousel mode; measure again when the cards change.
watch(
    () => cards.value.length,
    async () => {
        await nextTick();
        update();
    },
);

onBeforeUnmount(() => {
    window.removeEventListener('resize', update);
    cancelAnimationFrame(frame);
});
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