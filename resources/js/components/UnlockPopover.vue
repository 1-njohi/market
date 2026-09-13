<template>
    <div class="inline-block" ref="popoverContainer">
        <!-- Trigger -->
        <button
            type="button"
            @click.stop="togglePopover"
            :disabled="isProcessing"
            class="w-full cursor-pointer rounded border border-amber-500/40 bg-transparent py-3 text-xs font-black tracking-widest text-amber-400 uppercase transition-all duration-200 hover:border-amber-500 hover:bg-amber-500/10 disabled:pointer-events-none disabled:opacity-40"
        >
            <span>
                Unlock access ({{ currency }} {{ Number(amount).toFixed(2) }})
            </span>
        </button>

        <Teleport to="body">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                @click.self="closePopover"
            >
                <!-- Backdrop -->
                <div
                    class="absolute inset-0 bg-black/70 backdrop-blur-sm"
                ></div>

                <!-- Modal -->
                <div
                    ref="modalElement"
                    class="animate-fade-in relative w-full max-w-md rounded border border-gray-800 bg-[#0f1422] p-4 shadow-[0_20px_50px_rgba(7,11,20,0.9),0_0_25px_rgba(245,158,11,0.1)] sm:w-96"
                >
                    <!-- Header -->
                    <div
                        class="mb-3 flex items-center justify-between border-b border-gray-800 pb-2"
                    >
                        <span
                            class="text-[9px] font-black tracking-widest text-amber-400 uppercase"
                        >
                            Confirm purchase
                        </span>
                        <button
                            type="button"
                            @click="closePopover"
                            class="cursor-pointer font-mono text-xs text-slate-500 hover:text-slate-300"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Balance summary -->
                    <div
                        class="mb-3 space-y-2 rounded border border-[#232d42] bg-[#070b14] p-3"
                    >
                        <div
                            class="flex items-center justify-between text-[11px]"
                        >
                            <span class="font-mono text-slate-400 uppercase">
                                Betslip price
                            </span>
                            <span class="font-mono font-black text-white">
                                {{ currency }} {{ Number(amount).toFixed(2) }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between text-[11px]"
                        >
                            <span class="font-mono text-slate-400 uppercase">
                                Your balance
                            </span>
                            <span
                                class="font-mono font-black"
                                :class="
                                    hasSufficientBalance
                                        ? 'text-emerald-400'
                                        : 'text-rose-400'
                                "
                            >
                                {{ currency }}
                                {{ Number(userBalance).toFixed(2) }}
                            </span>
                        </div>
                    </div>

                    <!-- Sufficient balance -->
                    <template v-if="hasSufficientBalance">
                        <p
                            class="mb-3 text-[10px] leading-relaxed text-slate-400"
                        >
                            The amount will be held from your balance and
                            released to the seller only if the betslip wins. If
                            it loses, you get a full refund.
                        </p>

                        <div
                            v-if="errorMessage"
                            class="mb-3 rounded border border-rose-500/30 bg-rose-500/10 p-2 text-[10px] font-bold tracking-wide text-rose-400 uppercase"
                        >
                            {{ errorMessage }}
                        </div>

                        <button
                            type="button"
                            @click="confirmPurchase"
                            :disabled="isProcessing"
                            class="flex w-full cursor-pointer items-center justify-center rounded border border-amber-500 bg-amber-500 py-2.5 text-[10px] font-black tracking-widest text-[#070b14] uppercase transition-all duration-200 hover:bg-transparent hover:text-amber-400 disabled:pointer-events-none disabled:opacity-40"
                        >
                            <span
                                v-if="isProcessing"
                                class="flex items-center gap-1.5"
                            >
                                <span
                                    class="h-1.5 w-1.5 animate-ping rounded-full bg-[#070b14]"
                                ></span>
                                Processing…
                            </span>
                            <span v-else>Confirm purchase</span>
                        </button>
                    </template>

                    <!-- Insufficient balance -->
                    <template v-else>
                        <div
                            class="mb-3 rounded border border-rose-500/30 bg-rose-500/10 p-3"
                        >
                            <p
                                class="text-[10px] leading-relaxed font-bold text-rose-300"
                            >
                                You need
                                <span class="font-mono text-white">
                                    {{ currency }}
                                    {{
                                        Number(amount - userBalance).toFixed(2)
                                    }}
                                </span>
                                more to unlock this betslip.
                            </p>
                        </div>

                        <div class="space-y-2">
                            <span
                                class="block text-center font-mono text-[9px] font-bold text-slate-500 uppercase"
                            >
                                Add funds to continue
                            </span>

                            <DepositPopover
                                :currency="currency"
                                :initial_amount="
                                    Math.ceil(amount - userBalance)
                                "
                            />
                        </div>
                    </template>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import DepositPopover from '@/components/DepositPopover.vue';

const props = defineProps({
    amount: {
        type: Number,
        required: true,
    },
    betslip_id: {
        type: [String, Number],
        required: true,
    },
    currency: {
        type: String,
        default: 'KES',
    },
});

const page = usePage();
const isOpen = ref(false);
const isProcessing = ref(false);
const errorMessage = ref('');
const popoverContainer = ref(null);
const modalElement = ref(null);

const userBalance = computed(() => Number(page.props.auth?.balance || 0));
const hasSufficientBalance = computed(() => userBalance.value >= props.amount);

watch(isOpen, (v) => {
    document.body.classList.toggle('overflow-hidden', v);
});

const togglePopover = () => {
    if (isProcessing.value) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) errorMessage.value = '';
};

const closePopover = () => {
    if (isProcessing.value) return;
    isOpen.value = false;
};

const confirmPurchase = () => {
    if (!hasSufficientBalance.value) return;

    errorMessage.value = '';

    router.post(
        '/betslip/unlock',
        { betslip_id: props.betslip_id },
        {
            preserveScroll: false, // we're navigating away, don't preserve
            onStart: () => {
                isProcessing.value = true;
            },
            onSuccess: () => {
                isProcessing.value = false;
                isOpen.value = false;
            },
            onError: (err) => {
                errorMessage.value =
                    Object.values(err)[0] ||
                    'Purchase failed. Please try again.';
                isProcessing.value = false;
            },
            onFinish: () => {
                isProcessing.value = false;
            },
        },
    );
};

const handleClickOutside = (event) => {
    if (
        isOpen.value &&
        modalElement.value &&
        !modalElement.value.contains(event.target) &&
        popoverContainer.value &&
        !popoverContainer.value.contains(event.target)
    ) {
        closePopover();
    }
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    document.body.classList.remove('overflow-hidden');
});
</script>

<style scoped>
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}
.animate-fade-in {
    animation: fadeIn 0.15s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
