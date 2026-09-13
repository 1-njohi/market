<template>
    <div class="flex-1" ref="popoverContainer">
        <button
            type="button"
            @click.stop="togglePopover"
            :disabled="isProcessing"
            class="flex w-full cursor-pointer items-center justify-center rounded border border-sky-500/40 bg-transparent py-2.5 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-all duration-200 hover:border-sky-500 hover:bg-sky-500/10 disabled:pointer-events-none disabled:opacity-40"
        >
            <span>DEPOSIT</span>
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
                    class="animate-fade-in relative w-full max-w-md rounded border border-gray-800 bg-[#0f1422] p-4 shadow-[0_20px_50px_rgba(7,11,20,0.9),0_0_25px_rgba(14,165,233,0.15)] sm:w-96"
                >
                    <div
                        class="mb-3 flex items-center justify-between border-b border-gray-800 pb-2"
                    >
                        <span
                            class="text-[9px] font-black tracking-widest text-sky-400 uppercase"
                            >Add funds (KES {{ initial_amount }} MINIMUM)</span
                        >
                        <button
                            type="button"
                            @click="closePopover"
                            class="cursor-pointer font-mono text-xs text-slate-500 hover:text-slate-300"
                        >
                            ✕
                        </button>
                    </div>

                    <form
                        @submit.prevent="handleDepositSubmission"
                        class="space-y-3"
                    >
                        <div>
                            <label
                                class="mb-1 block font-mono text-[9px] font-black tracking-wider text-slate-400 uppercase"
                            >
                                Amount ({{ currency }}) — Min 100
                            </label>
                            <div class="relative">
                                <span
                                    class="absolute inset-y-0 left-0 flex items-center pl-3 font-mono text-[10px] font-bold text-slate-500"
                                >
                                    {{ currency }}
                                </span>
                                <input
                                    type="number"
                                    v-model.number="amount"
                                    required
                                    min="100"
                                    step="any"
                                    placeholder="0.00"
                                    :disabled="isProcessing"
                                    class="w-full rounded border border-[#232d42] bg-[#070b14] py-2 pr-4 pl-14 font-mono text-xs font-semibold tracking-wide text-white placeholder-slate-600 transition-all focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none disabled:opacity-50"
                                />
                            </div>
                            <p class="mt-1 font-mono text-[9px] text-slate-500">
                                You will be redirected to Paystack to complete
                                payment.
                            </p>
                        </div>

                        <div
                            v-if="errorMessage"
                            class="rounded border border-rose-500/30 bg-rose-500/10 p-2 text-[10px] font-bold tracking-wide text-rose-400 uppercase"
                        >
                            {{ errorMessage }}
                        </div>

                        <button
                            type="submit"
                            :disabled="isProcessing || !amount || amount < 100"
                            class="flex w-full cursor-pointer items-center justify-center rounded border border-sky-500 bg-sky-500 py-2.5 text-[10px] font-black tracking-widest text-[#070b14] uppercase transition-all duration-200 hover:bg-transparent hover:text-sky-400 disabled:pointer-events-none disabled:opacity-40"
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
                            <span v-else>Add funds</span>
                        </button>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    currency: {
        type: String,
        default: 'KES',
    },
    initial_amount: {
        type: Number,
        default: 100,
    },
});

const isOpen = ref(false);
const isProcessing = ref(false);
const amount = ref(0);
const errorMessage = ref('');

const popoverContainer = ref(null);
const modalElement = ref(null);

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
    amount.value = null;
    errorMessage.value = '';
};

const handleDepositSubmission = async () => {
    errorMessage.value = '';

    if (!amount.value || amount.value < 100) {
        errorMessage.value = 'Minimum deposit is KES 100.';
        return;
    }

    try {
        isProcessing.value = true;
        const response = await axios.post(
            '/deposit/initiate',
            { amount: amount.value },
            { headers: { Accept: 'application/json' } },
        );

        const data = response.data;

        if (data.success) {
            closePopover();
            window.location.href = data.authorization_url;
        } else {
            errorMessage.value = data.message || 'Failed to initiate deposit.';
        }
    } catch (error) {
        console.error('Deposit error:', error);
        errorMessage.value =
            error.response?.data?.message ||
            'An unexpected error occurred. Please try again.';
    } finally {
        isProcessing.value = false;
    }
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
    amount.value = props.initial_amount;
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
