<template>
    <div class="flex-1" ref="popoverContainer">
        <button
            type="button"
            @click.stop="togglePopover"
            :disabled="disabled || isProcessing"
            class="flex min-h-12 w-full items-center justify-center rounded-xl border px-5 text-sm font-bold transition-all duration-200 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
            :class="
                !disabled
                    ? 'cursor-pointer border-white/15 bg-white/[0.05] text-white hover:border-amber-300/50 hover:bg-amber-300/10 hover:text-amber-200 active:scale-[0.98]'
                    : 'cursor-not-allowed border-white/5 bg-transparent text-slate-600'
            "
        >
            <span>Withdraw</span>
        </button>

        <Teleport to="body">
            <!-- Bottom sheet on phones, centered dialog on larger screens -->
            <div v-if="isOpen" class="fixed inset-0 z-[9999] flex items-end justify-center sm:items-center sm:p-4" @click.self="closePopover">
                <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="closePopover"></div>

                <div
                    ref="modalElement"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Withdraw"
                    class="animate-sheet relative flex max-h-[92svh] w-full flex-col overflow-hidden rounded-t-3xl border border-white/10 bg-[#0d1424] shadow-[0_-20px_60px_rgba(0,0,0,0.6)] sm:max-h-[90vh] sm:max-w-md sm:rounded-3xl"
                >
                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-white/[0.07] px-5 py-4">
                        <h2 class="text-base font-bold text-white">Withdraw</h2>
                        <button
                            type="button"
                            @click="closePopover"
                            aria-label="Close"
                            class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="overflow-y-auto px-5 py-5 pb-[max(1.25rem,env(safe-area-inset-bottom))]">
                        <!-- Balance summary -->
                        <div class="mb-4 flex items-center justify-between rounded-2xl border border-amber-300/20 bg-amber-300/5 px-4 py-3.5">
                            <span class="text-sm text-slate-400">Available</span>
                            <span class="text-lg font-black text-amber-200 tabular-nums">
                                {{ currency }}
                                {{ Number(availableBalance).toLocaleString(undefined, { minimumFractionDigits: 2 }) }}
                            </span>
                        </div>

                        <form @submit.prevent="handleWithdrawalSubmission" class="space-y-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-300">
                                    Amount ({{ currency }})
                                    <span class="font-normal text-slate-500">· minimum 10</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-bold text-slate-500">{{ currency }}</span>
                                    <input
                                        type="number"
                                        inputmode="decimal"
                                        v-model.number="amount"
                                        required
                                        min="10"
                                        step="any"
                                        placeholder="0.00"
                                        :disabled="isProcessing"
                                        class="min-h-12 w-full rounded-xl border border-white/10 bg-[#070b14] py-2 pr-4 pl-16 text-base font-semibold text-white tabular-nums placeholder-slate-600 focus:border-amber-300 focus:ring-2 focus:ring-amber-300/20 focus:outline-none disabled:opacity-50"
                                    />
                                </div>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-300">M-Pesa phone number</label>
                                <input
                                    type="tel"
                                    inputmode="tel"
                                    autocomplete="tel"
                                    v-model="phone"
                                    required
                                    placeholder="2547XXXXXXXX"
                                    pattern="^(?:254|\+254|0)?(7|1)\d{8}$"
                                    :disabled="isProcessing"
                                    class="min-h-12 w-full rounded-xl border border-white/10 bg-[#070b14] px-4 py-2 text-base font-semibold text-white placeholder-slate-600 focus:border-amber-300 focus:ring-2 focus:ring-amber-300/20 focus:outline-none disabled:opacity-50"
                                />
                                <p class="mt-1.5 text-sm text-slate-500">Use a Safaricom M-Pesa number (07XX, 01XX, or 2547XX).</p>
                            </div>

                            <div v-if="errorMessage" class="rounded-xl border border-rose-400/30 bg-rose-400/10 p-3 text-sm font-semibold text-rose-300" role="alert">
                                {{ errorMessage }}
                            </div>

                            <button
                                type="submit"
                                :disabled="isProcessing || !amount || amount < 10 || !phone"
                                class="flex min-h-12 w-full cursor-pointer items-center justify-center rounded-xl bg-amber-300 text-sm font-black text-[#070b14] transition-all duration-200 hover:bg-amber-200 focus-visible:ring-2 focus-visible:ring-amber-100 focus-visible:outline-none active:scale-[0.98] disabled:pointer-events-none disabled:opacity-40"
                            >
                                <span v-if="isProcessing" class="flex items-center gap-2">
                                    <span class="h-2 w-2 animate-ping rounded-full bg-[#070b14]"></span>
                                    Processing…
                                </span>
                                <span v-else>Confirm withdrawal</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';

const props = defineProps({
    currency: { type: String, default: 'KES' },
    availableBalance: { type: Number, default: 0 },
    disabled: { type: Boolean, default: false },
});

const isOpen = ref(false);
const isProcessing = ref(false);
const amount = ref(null);
const phone = ref('');
const errorMessage = ref('');

const popoverContainer = ref(null);
const modalElement = ref(null);

watch(isOpen, (v) => {
    document.body.classList.toggle('overflow-hidden', v);
});

const togglePopover = () => {
    if (isProcessing.value || props.disabled) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) errorMessage.value = '';
};

const closePopover = () => {
    if (isProcessing.value) return;
    isOpen.value = false;
    amount.value = null;
    phone.value = '';
    errorMessage.value = '';
};

const handleWithdrawalSubmission = async () => {
    errorMessage.value = '';

    if (!amount.value || amount.value < 10) {
        errorMessage.value = 'Minimum withdrawal is KES 10.';
        return;
    }
    if (amount.value > props.availableBalance) {
        errorMessage.value = 'Amount exceeds your available balance.';
        return;
    }
    if (!phone.value) {
        errorMessage.value = 'Please enter your M-Pesa phone number.';
        return;
    }

    try {
        isProcessing.value = true;
        const response = await axios.post(
            '/withdrawals',
            { amount: amount.value, phone: phone.value },
            { headers: { Accept: 'application/json' } },
        );

        const data = response.data;
        if (data.success) {
            closePopover();
            alert(
                data.message ||
                    'Withdrawal initiated. Check your phone for the M-Pesa prompt.',
            );
            window.location.reload();
        } else {
            errorMessage.value = data.message || 'Failed to initiate withdrawal.';
        }
    } catch (error) {
        console.error('Withdrawal error:', error);
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

const handleKeydown = (e) => {
    if (e.key === 'Escape' && isOpen.value) closePopover();
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
    document.body.classList.remove('overflow-hidden');
});
</script>

<style scoped>
@keyframes sheetIn {
    from {
        opacity: 0;
        transform: translateY(24px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-sheet {
    animation: sheetIn 0.22s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@media (prefers-reduced-motion: reduce) {
    .animate-sheet {
        animation: none;
    }
}
</style>