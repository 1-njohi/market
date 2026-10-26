<template>
    <SectionWrapper v-if="contests && contests.length > 0">
        <div class="flex items-end justify-between gap-4">
            <SectionHeading
                align="left"
                eyebrow="Open now"
                title="Jump into a public contest."
                subtitle="Free to enter. Pick every leg before the deadline."
            />

            <!-- Controls: the help button leads, arrows follow on desktop (phones swipe) -->
            <div class="flex shrink-0 items-center gap-2">
                <InfoPopover size="md" label="How public contests work" title="How public contests work">
                    <p>
                        <span class="font-bold text-slate-100">Public contests</span>
                        are run by our team. We copy the big weekend
                        games you see from bookies — same game, no
                        bookie.
                    </p>
                    <p>
                        <span class="font-bold text-slate-100">Private contests</span>
                        are yours. Invite your friends, pick the legs,
                        set a deadline. Same rules, just your circle.
                    </p>
                    <p>
                        <span class="font-bold text-slate-100">How you win:</span>
                        pick one selection per leg. Every pick you get
                        right counts. The player with the most correct
                        picks takes first place — if two players tie,
                        the one whose picks had bigger odds wins.
                    </p>
                    <p>
                        <span class="font-bold text-slate-100">Both are free.</span>
                        No money changes hands. What you win is the top
                        spot on the results board, seen by everyone who
                        played.
                    </p>
                    <p class="text-slate-400">
                        Tap a card to join a public one. Hit Create
                        Contest to start your own.
                    </p>
                </InfoPopover>

                <div class="hidden items-center gap-2 md:flex">
                    <button
                        type="button"
                        @click="scrollByCards(-1)"
                        :disabled="!canPrev"
                        aria-label="Previous contests"
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
                        aria-label="Next contests"
                        class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full border border-white/10 bg-white/[0.04] text-slate-200 transition-all duration-200 hover:border-amber-300/50 hover:text-amber-200 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-95 disabled:pointer-events-none disabled:opacity-30"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Track: bleeds to the screen edge on phones so the next card peeks in -->
        <div class="relative mt-10 -mx-5 md:mx-0">
            <div
                ref="scroller"
                @scroll.passive="onScroll"
                role="region"
                aria-roledescription="carousel"
                aria-label="Open public contests"
                class="no-scrollbar flex snap-x snap-mandatory items-stretch gap-4 overflow-x-auto scroll-smooth px-1 pb-4 md:px-0 motion-reduce:scroll-auto"
                style="scroll-padding-inline: 1.25rem"
            >
                <div v-for="c in contests" :key="c.uuid" class="flex w-[84%] shrink-0 snap-start sm:w-[24rem]">
                    <PublicContestCard :contest="c" class="w-full" />
                </div>
            </div>

            <!-- Soft edge on desktop when there's more to scroll -->
            <div class="pointer-events-none absolute inset-y-0 right-0 hidden w-24 bg-gradient-to-l from-[#0b0f19] to-transparent transition-opacity duration-300 md:block" :class="canNext ? 'opacity-100' : 'opacity-0'" aria-hidden="true"></div>
        </div>

        <!-- Position dots, also tap targets -->
        <div v-if="contests.length > 1" class="mt-4 flex items-center justify-center gap-1" role="group" aria-label="Choose a contest">
            <button
                v-for="(c, i) in contests"
                :key="c.uuid"
                type="button"
                @click="scrollToIndex(i)"
                :aria-label="`Go to contest ${i + 1} of ${contests.length}`"
                :aria-current="activeIndex === i ? 'true' : undefined"
                class="group flex h-8 w-8 cursor-pointer items-center justify-center focus-visible:outline-none"
            >
                <span
                    class="h-2 rounded-full transition-all duration-300 group-focus-visible:ring-2 group-focus-visible:ring-amber-300"
                    :class="activeIndex === i ? 'w-6 bg-amber-300' : 'w-2 bg-white/20 group-hover:bg-white/40'"
                ></span>
            </button>
        </div>
    </SectionWrapper>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import SectionWrapper from './SectionWrapper.vue';
import SectionHeading from './SectionHeading.vue';
import PublicContestCard from '@/components/PublicContestCard.vue';
import InfoPopover from '../InfoPopover.vue';

// The payload from getPublicContests(); the card documents the exact shape.
defineProps<{
    contests: Record<string, any>[];
}>();

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