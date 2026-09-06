<template>
    <div ref="betslipRef">
        <!-- Relative version (normal flow) -->
        <section
            v-if="isFixed && isOpen"
            class="fixed right-0 bottom-4 left-0 z-50 w-full overflow-hidden rounded-lg bg-[#0d1527] p-4 shadow-2xl md:right-auto md:left-4 md:w-auto md:max-w-xl md:p-0"
        >
            <div class="flex justify-end gap-2 bg-gray-100 md:hidden">
                <button
                    class="sticky mb-[-100px] h-6 w-6 cursor-pointer rounded-xl bg-green-900 bg-red-500 px-1 text-sm font-bold text-white transition-colors hover:text-green-500"
                    @click="isOpen = false"
                >
                    x
                </button>
            </div>

            <BetslipCard />
        </section>

        <!-- Fixed version (floating) -->
        <section
            v-else
            class="mb-2 hidden w-full max-w-xl overflow-hidden rounded-xl border border-gray-800 bg-[#0d1527] font-sans shadow-2xl md:block"
        >
            <div
                class="overflow-hidden rounded-xl border border-gray-800 bg-[#0d1527] font-sans shadow-2xl"
            >
                <BetslipCard />
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import BetslipCard from './BetslipCard.vue';
const betslipRef = ref(null);
const isFixed = ref(false);
const isOpen = ref(false);
let scrollListener = null;

const checkPosition = () => {
    if (!betslipRef.value) return;

    const rect = betslipRef.value.getBoundingClientRect();
    const windowHeight = window.innerHeight;

    // Check if element is completely out of view (below the viewport)
    const isOutOfView = rect.bottom < 0 || rect.top > windowHeight;

    // Check if element is near the bottom of viewport
    const isNearBottom = rect.bottom < windowHeight - 100;

    // Only fix if it's out of view or near the bottom and scrolling down
    const shouldBeFixed = isOutOfView || (isNearBottom && rect.top < 0);

    // Only update if different
    if (shouldBeFixed !== isFixed.value) {
        isFixed.value = shouldBeFixed;
    }
};

onMounted(() => {
    // Check on mount
    checkPosition();

    // Use scroll listener with requestAnimationFrame for performance
    scrollListener = () => {
        requestAnimationFrame(checkPosition);
    };

    window.addEventListener('scroll', scrollListener, { passive: true });
    window.addEventListener('resize', checkPosition, { passive: true });

    window.addEventListener('betslipOpened', function () {
        isOpen.value = true;
        isFixed.value = true;
    });
    // Check if viewport is 768px wide or wider
    if (window.matchMedia('(min-width: 768px)').matches) {
        isOpen.value = true;
    } else {
        isOpen.value = false;
    }
});

onBeforeUnmount(() => {
    if (scrollListener) {
        window.removeEventListener('scroll', scrollListener);
        window.removeEventListener('resize', checkPosition);
    }
});
</script>
