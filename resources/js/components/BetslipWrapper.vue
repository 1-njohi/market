<template>
    <div ref="betslipRef">
        <!-- ═══ Sticky sidebar (desktop, /fixtures page) ═══ -->
        <section
            v-if="variant === 'sticky-sidebar'"
            class="hidden md:block"
        >
            <div class="sticky top-4 max-h-[calc(100vh-2rem)] overflow-y-auto">
                <div
                    class="overflow-hidden rounded-xl border border-[#232d42] bg-[#0d1527] font-sans shadow-2xl"
                >
                    <BetslipCard />
                </div>
            </div>
        </section>

        <!-- ═══ Floating bottom sheet ═══ -->
        <section
            v-else-if="isFixed && isOpen"
            class="fixed inset-x-0 bottom-0 z-50 mx-auto w-full max-w-2xl overflow-hidden rounded-t-xl border border-[#232d42] bg-[#0d1527] shadow-2xl md:bottom-4 md:rounded-lg"
        >
            <button
                type="button"
                @click="isOpen = false"
                class="absolute top-3 right-3 z-10 flex h-7 w-7 cursor-pointer items-center justify-center rounded-full border border-[#232d42] bg-[#161c2a] text-slate-400 transition-colors hover:border-sky-500/50 hover:text-slate-200 md:hidden"
                aria-label="Close"
            >
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
                        d="M6 18 18 6M6 6l12 12"
                    />
                </svg>
            </button>

            <BetslipCard />
        </section>

        <!-- ═══ Inline (default, normal flow) ═══ -->
        <section
            v-else
            class="mb-2 hidden w-full max-w-2xl overflow-hidden rounded-xl border border-[#232d42] bg-[#0d1527] font-sans shadow-2xl md:block"
        >
            <BetslipCard />
        </section>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import BetslipCard from './BetslipCard.vue';

const props = defineProps({
    /**
     * 'inline'         — current default (fixed bottom sheet on scroll,
     *                    in-flow otherwise). Used by Welcome and Fixture pages.
     * 'sticky-sidebar' — always visible in a sticky container. Used by the
     *                    /fixtures page's two-column layout.
     */
    variant: {
        type: String,
        default: 'inline',
        validator: (v) => ['inline', 'sticky-sidebar'].includes(v),
    },
});

const betslipRef = ref(null);
const isFixed = ref(false);
const isOpen = ref(false);
let scrollListener = null;

const checkPosition = () => {
    if (props.variant === 'sticky-sidebar') return;
    if (!betslipRef.value) return;

    const rect = betslipRef.value.getBoundingClientRect();
    const windowHeight = window.innerHeight;

    const isOutOfView = rect.bottom < 0 || rect.top > windowHeight;
    const isNearBottom = rect.bottom < windowHeight - 100;
    const shouldBeFixed = isOutOfView || (isNearBottom && rect.top < 0);

    if (shouldBeFixed !== isFixed.value) {
        isFixed.value = shouldBeFixed;
    }
};

onMounted(() => {
    if (props.variant === 'sticky-sidebar') return;

    checkPosition();

    scrollListener = () => {
        requestAnimationFrame(checkPosition);
    };

    window.addEventListener('scroll', scrollListener, { passive: true });
    window.addEventListener('resize', checkPosition, { passive: true });

    window.addEventListener('betslipOpened', function () {
        isOpen.value = true;
        isFixed.value = true;
    });

    if (window.matchMedia('(min-width: 768px)').matches) {
        isOpen.value = true;
    } else {
        isOpen.value = false;
    }
});

onBeforeUnmount(() => {
    if (props.variant === 'sticky-sidebar') return;
    if (scrollListener) {
        window.removeEventListener('scroll', scrollListener);
        window.removeEventListener('resize', checkPosition);
    }
});
</script>