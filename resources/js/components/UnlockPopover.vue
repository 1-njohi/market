<template>
    <div class="inline-block" ref="popoverContainer">
        <button
            type="button"
            @click="togglePopover"
            class="fw-full cursor-pointer rounded border-b-2 border-amber-700 bg-[#ff8c00] py-3 text-xs font-black tracking-widest text-black uppercase shadow-lg transition-all hover:bg-blue-500"
        >
            <span
                >UNLOCK ACCESS ({{ currency }}
                {{ Number(amount).toFixed(2) }})</span
            >
        </button>

        <div
            v-if="isOpen"
            class="animate-fade-in fixed top-1/2 left-1/2 isolate !z-[9999] w-[calc(100vw-2rem)] -translate-x-1/2 -translate-y-1/2 rounded border p-4 shadow-[0_20px_50px_rgba(7,11,20,0.9)] sm:w-85"
            :class="
                hasSufficientBalance
                    ? 'border-amber-500/30 bg-[#0f1422]'
                    : 'border-rose-500/30 bg-[#120b11]'
            "
        >
            <div
                class="mb-3 flex items-center justify-between border-b pb-2"
                :class="
                    hasSufficientBalance
                        ? 'border-gray-800'
                        : 'border-rose-950/40'
                "
            >
                <span
                    class="flex items-center gap-1.5 font-mono text-[9px] font-black tracking-widest uppercase"
                    :class="
                        hasSufficientBalance
                            ? 'text-amber-400'
                            : 'text-rose-400'
                    "
                >
                    <span
                        class="h-1.5 w-1.5 rounded-full"
                        :class="
                            hasSufficientBalance
                                ? 'bg-amber-400'
                                : 'animate-pulse bg-rose-500'
                        "
                    ></span>
                    {{
                        hasSufficientBalance ? 'CONFIRM' : 'INSUFFICIENT FUNDS'
                    }}
                </span>
                <button
                    type="button"
                    @click="closePopover"
                    class="cursor-pointer font-mono text-xs text-slate-500 hover:text-slate-300"
                >
                    ✕
                </button>
            </div>

            <div v-if="hasSufficientBalance" class="space-y-4">
                <div
                    class="rounded border border-[#232d42] bg-[#070b14] p-3 font-mono text-[11px] leading-relaxed text-slate-400 uppercase"
                >
                    <p class="mb-1 font-bold text-white">JUST TO BE CLEAR:</p>
                    Confirming this deployment will immediately release
                    <span class="font-black text-amber-400"
                        >{{ currency }} {{ Number(amount).toFixed(2) }}</span
                    >
                    from your available balance secure escrow lock. It will be
                    released to the tipster only after this bet has been settled
                    as a win, otherwise you will be refunded.
                </div>

                <div
                    class="flex items-center justify-between rounded border border-gray-800 bg-[#111622]/40 p-2 font-mono text-[10px]"
                >
                    <span class="font-bold text-slate-500">YOUR BALANCE:</span>
                    <span class="font-black text-emerald-400"
                        >{{ currency }}
                        {{
                            Number(userBalance).toLocaleString(undefined, {
                                minimumFractionDigits: 2,
                            })
                        }}</span
                    >
                </div>

                <button
                    type="button"
                    @click="executeUnlockSequence"
                    :disabled="isProcessing"
                    class="flex w-full cursor-pointer items-center justify-center rounded border border-amber-500 bg-amber-500 py-2.5 text-[10px] font-black tracking-widest text-[#070b14] uppercase transition-all duration-200 hover:bg-transparent hover:text-amber-400 disabled:pointer-events-none disabled:opacity-40"
                >
                    <span v-if="isProcessing" class="flex items-center gap-1.5">
                        <span
                            class="h-1.5 w-1.5 animate-ping rounded-full bg-[#070b14]"
                        ></span>
                        INITIALIZING DECRYPTION...
                    </span>
                    <span v-else>CONFIRM & REVEAL SELECTIONS</span>
                </button>
            </div>

            <div v-else class="space-y-4">
                <div
                    class="rounded border border-rose-500/20 bg-rose-950/20 p-3 font-mono text-[11px] leading-relaxed text-white uppercase"
                >
                    <p class="mb-1 font-black text-rose-400">CAUTION !!</p>
                    Account ledger balance holds insufficient to unlock
                    selections. Deposit
                    <span class="font-mono font-black text-rose-300/90"
                        >{{ currency }}
                        {{ Number(amount - userBalance).toFixed(2) }}
                    </span>
                    to immediately unlock the selections. The amount will be
                    held in a secure escrow lock. It will be released to the
                    tipster only after this bet has been settled as a win,
                    otherwise you will be refunded.
                </div>

                <div
                    class="grid grid-cols-2 gap-2 rounded border border-rose-950/30 bg-[#070b14] p-2.5 font-mono text-[10px]"
                >
                    <div>
                        <span class="block text-[8px] font-bold text-slate-500"
                            >REQUIRED</span
                        >
                        <span class="font-bold text-white"
                            >{{ currency }}
                            {{ Number(amount).toFixed(2) }}</span
                        >
                    </div>
                    <div>
                        <span class="block text-[8px] font-bold text-slate-500"
                            >AVAILABLE</span
                        >
                        <span class="font-bold text-rose-400"
                            >{{ currency }}
                            {{ Number(userBalance).toFixed(2) }}</span
                        >
                    </div>
                </div>

                <div class="flex flex-col space-y-2 pt-1">
                    <span
                        class="text-center font-mono text-[9px] font-bold text-slate-500 uppercase"
                        >DEPOSIT EXTRA {{ currency }}
                        {{
                            Number(amount - userBalance).toFixed(2)
                        }}
                        INSTANTLY</span
                    >
                    <DepositPopover
                        :currency="currency"
                        :initial_amount="amount"
                        @success="handleDepositInjectedEvent"
                    />
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import axios from 'axios';
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
const popoverContainer = ref(null);

// Extract reactive authenticated global balance allocations directly from shared state array
const userBalance = computed(() => {
    return Number(page.props.auth?.balance || 0);
});

// Structural branching check conditional state
const hasSufficientBalance = computed(() => {
    return userBalance.value >= props.amount;
});

// Window tracking context scroll locks
watch(isOpen, (newValue) => {
    if (newValue) {
        document.body.classList.add('overflow-hidden');
    } else {
        document.body.classList.remove('overflow-hidden');
    }
});

const togglePopover = () => {
    if (!isProcessing.value) {
        isOpen.value = !isOpen.value;
    }
};

const closePopover = () => {
    if (!isProcessing.value) {
        isOpen.value = false;
    }
};

// Dispatch server pipeline request handlers
const executeUnlockSequence = async () => {
    if (!hasSufficientBalance.value) return;

    // router.post(
    //     `/betslip/unlock`,
    //     {
    //         betslip_id: props.betslip_id,
    //     },
    //     {
    //         preserveScroll: true,
    //         onStart: () => {
    //             isProcessing.value = true;
    //         },
    //         onFinish: () => {
    //             isProcessing.value = false;
    //         },
    //         onSuccess: () => {
    //             closePopover();
    //         },
    //         onError: (err) => {
    //             console.error('Decryption deployment failure:', err);
    //             isProcessing.value = false;
    //         },
    //     },
    // );

    try {
        isProcessing.value = true;
        const response = await axios.post(
            '/betslip/unlock',
            { betslip_id: props.betslip_id },
            { headers: { Accept: 'application/json' } },
        );

        const data = response.data;

        if (data.success) {
            closePopover();
            // window.location.href = data.authorization_url;
        } else {
            isProcessing.value = false;
            alert(data.message || 'Failed to initiate deposit');
        }
    } catch (error) {
        console.error('Deposit error:', error);
        alert('An unexpected error occurred. Please try again.');
        isProcessing.valvue = false;
    }
};

// React gracefully if inline capital balance injection event finishes cleanly
const handleDepositInjectedEvent = () => {
    console.log(
        'Capital injection sequence completed successfully. Re-evaluating balance thresholds.',
    );
};

const handleClickOutside = (event) => {
    const activeModal = document.querySelector('.fixed');
    if (
        isOpen.value &&
        activeModal &&
        !activeModal.contains(event.target) &&
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
        transform: translate(-1/2, -45%) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translate(-1/2, -50%) scale(1);
    }
}
.animate-fade-in {
    animation: fadeIn 0.15s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
