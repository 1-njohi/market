<template>
    <div class="flex-1" ref="popoverContainer">
        <button
            type="button"
            @click.stop="togglePopover"
            :disabled="isProcessing"
            class="flex min-h-12 w-full cursor-pointer items-center justify-center rounded-xl bg-emerald-400 px-5 text-sm font-black text-[#070b14] shadow-[0_10px_30px_-12px_rgba(52,211,153,0.8)] transition-all duration-200 hover:bg-emerald-300 focus-visible:ring-2 focus-visible:ring-emerald-200 focus-visible:outline-none active:scale-[0.98] disabled:pointer-events-none disabled:opacity-40"
        >
            <span>Deposit</span>
        </button>

        <Teleport to="body">
            <!-- Bottom sheet on phones, centered dialog on larger screens -->
            <div v-if="isOpen" class="fixed inset-0 z-[9999] flex items-end justify-center sm:items-center sm:p-4" @click.self="closePopover">
                <div class="absolute inset-0 bg-black/70 backdrop-blur-sm" @click="closePopover"></div>

                <div
                    ref="modalElement"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Deposit"
                    class="animate-sheet relative flex max-h-[92svh] w-full flex-col overflow-hidden rounded-t-3xl border border-white/10 bg-[#0d1424] shadow-[0_-20px_60px_rgba(0,0,0,0.6)] sm:max-h-[90vh] sm:max-w-md sm:rounded-3xl"
                >
                    <!-- HEADER -->
                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-white/[0.07] px-5 py-4">
                        <h2 class="text-base font-bold text-white">
                            <template v-if="stage === 'form'">Add funds</template>
                            <template v-else-if="stage === 'awaiting'">Awaiting confirmation</template>
                            <template v-else>Deposit received</template>
                        </h2>
                        <button
                            type="button"
                            @click="closePopover"
                            :disabled="isProcessing"
                            aria-label="Close"
                            class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-emerald-400 focus-visible:outline-none disabled:opacity-40"
                        >
                            ✕
                        </button>
                    </div>

                    <div class="overflow-y-auto px-5 py-5 pb-[max(1.25rem,env(safe-area-inset-bottom))]">
                        <!-- FORM STAGE -->
                        <form v-if="stage === 'form'" @submit.prevent="handleDepositSubmission" class="space-y-4">
                            <div>
                                <label class="mb-1.5 block text-sm font-semibold text-slate-300">
                                    Amount ({{ currency }})
                                    <span class="font-normal text-slate-500">· minimum {{ minAmount }}</span>
                                </label>
                                <div class="relative">
                                    <span class="absolute inset-y-0 left-0 flex items-center pl-4 text-sm font-bold text-slate-500">{{ currency }}</span>
                                    <input
                                        type="number"
                                        inputmode="decimal"
                                        v-model.number="amount"
                                        required
                                        :min="minAmount"
                                        step="any"
                                        placeholder="0.00"
                                        :disabled="isProcessing"
                                        class="min-h-12 w-full rounded-xl border border-white/10 bg-[#070b14] py-2 pr-4 pl-16 text-base font-semibold text-white tabular-nums placeholder-slate-600 transition-all focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/20 focus:outline-none disabled:opacity-50"
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
                                    placeholder="0712345678"
                                    :disabled="isProcessing"
                                    class="min-h-12 w-full rounded-xl border border-white/10 bg-[#070b14] px-4 py-2 text-base font-semibold text-white placeholder-slate-600 transition-all focus:border-emerald-400 focus:ring-2 focus:ring-emerald-400/20 focus:outline-none disabled:opacity-50"
                                />
                                <p class="mt-1.5 text-sm text-slate-500">We'll send a payment prompt to this number.</p>
                            </div>

                            <div v-if="errorMessage" class="rounded-xl border border-rose-400/30 bg-rose-400/10 p-3 text-sm font-semibold text-rose-300" role="alert">
                                {{ errorMessage }}
                            </div>

                            <button
                                type="submit"
                                :disabled="isProcessing || !amount || amount < minAmount || !phone"
                                class="flex min-h-12 w-full cursor-pointer items-center justify-center rounded-xl bg-emerald-400 text-sm font-black text-[#070b14] transition-all duration-200 hover:bg-emerald-300 focus-visible:ring-2 focus-visible:ring-emerald-200 focus-visible:outline-none active:scale-[0.98] disabled:pointer-events-none disabled:opacity-40"
                            >
                                <span v-if="isProcessing" class="flex items-center gap-2">
                                    <span class="h-2 w-2 animate-ping rounded-full bg-[#070b14]"></span>
                                    Sending prompt…
                                </span>
                                <span v-else>Send M-Pesa prompt</span>
                            </button>
                        </form>

                        <!-- AWAITING STAGE -->
                        <div v-else-if="stage === 'awaiting'" class="space-y-4">
                            <div class="rounded-2xl border border-sky-400/25 bg-sky-400/5 p-5 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-sky-400/10">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6 animate-pulse text-sky-300" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
                                    </svg>
                                </div>

                                <p class="mt-3 text-base font-bold text-sky-200">Check your phone</p>
                                <p class="mt-1 text-sm leading-relaxed text-slate-400">
                                    A prompt for
                                    <span class="font-bold text-white">KES {{ Number(amount).toFixed(2) }}</span>
                                    was sent to
                                    <span class="font-semibold text-slate-200">{{ phone }}</span>.
                                </p>
                                <p class="mt-2 text-sm text-slate-500">Enter your M-Pesa PIN to complete the deposit.</p>
                            </div>

                            <div class="flex items-center justify-center gap-2 text-sm text-slate-500">
                                <span class="h-2 w-2 animate-pulse rounded-full bg-sky-400"></span>
                                Waiting for confirmation…
                            </div>

                            <button
                                type="button"
                                @click="cancelAwaiting"
                                class="min-h-12 w-full cursor-pointer rounded-xl border border-white/10 bg-white/[0.04] text-sm font-bold text-slate-300 transition-colors hover:border-white/25 hover:text-white focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none"
                            >
                                Cancel
                            </button>
                        </div>

                        <!-- SUCCESS STAGE -->
                        <div v-else class="space-y-4">
                            <div class="rounded-2xl border border-emerald-400/25 bg-emerald-400/5 p-5 text-center">
                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-400/15">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-6 w-6 text-emerald-300" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                                    </svg>
                                </div>

                                <p class="mt-3 text-base font-bold text-emerald-200">Deposit received</p>
                                <p class="mt-1 text-sm text-slate-400">
                                    <span class="font-bold text-white">KES {{ Number(amount).toFixed(2) }}</span>
                                    has been added to your wallet.
                                </p>
                            </div>

                            <button
                                type="button"
                                @click="closePopover"
                                class="min-h-12 w-full cursor-pointer rounded-xl bg-emerald-400 text-sm font-black text-[#070b14] transition-colors hover:bg-emerald-300 focus-visible:ring-2 focus-visible:ring-emerald-200 focus-visible:outline-none"
                            >
                                Done
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
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
    initial_phone: {
        type: String,
        default: '',
    },
});

const minAmount = 1;

const isOpen = ref(false);
const isProcessing = ref(false);
const amount = ref(0);
const phone = ref('');
const errorMessage = ref('');

// 'form' | 'awaiting' | 'success'
const stage = ref('form');

// Poll control
let pollTimer = null;
let pollDeadline = null;

const popoverContainer = ref(null);
const modalElement = ref(null);

watch(isOpen, (v) => {
    document.body.classList.toggle('overflow-hidden', v);
    if (!v) {
        stopPolling();
    }
});

const togglePopover = () => {
    if (isProcessing.value) return;
    isOpen.value = !isOpen.value;
    if (isOpen.value) {
        errorMessage.value = '';
        stage.value = 'form';
    }
};

const closePopover = () => {
    if (isProcessing.value) return;
    isOpen.value = false;
    amount.value = null;
    errorMessage.value = '';
    stage.value = 'form';
    stopPolling();
};

const cancelAwaiting = () => {
    stopPolling();
    stage.value = 'form';
};

const handleDepositSubmission = async () => {
    errorMessage.value = '';

    if (!amount.value || amount.value < minAmount) {
        errorMessage.value = `Minimum deposit is KES ${minAmount}.`;
        return;
    }

    if (!phone.value) {
        errorMessage.value = 'Enter your M-Pesa phone number.';
        return;
    }

    try {
        isProcessing.value = true;
        const response = await axios.post(
            '/deposit/mpesa/initiate',
            { amount: amount.value, phone: phone.value },
            { headers: { Accept: 'application/json' } },
        );

        const data = response.data;

        if (data.success) {
            stage.value = 'awaiting';
            startPolling(amount.value);
        } else {
            errorMessage.value = data.message || 'Failed to initiate deposit.';
        }
    } catch (error) {
        console.error('Deposit error:', error);
        errorMessage.value =
            error.response?.data?.message ||
            error.response?.data?.errors?.phone?.[0] ||
            error.response?.data?.errors?.amount?.[0] ||
            'An unexpected error occurred. Please try again.';
    } finally {
        isProcessing.value = false;
    }
};

/**
 * Poll the wallet balance every 3s for up to 90s. If the balance rises
 * above the pre-deposit snapshot, we treat the deposit as confirmed.
 * We rely on the callback doing the actual credit — this just detects it.
 */

const startPolling = (depositAmount) => {
    stopPolling();

    const initialBalance = currentBalance;
    pollDeadline = Date.now() + 90_000;

    pollTimer = setInterval(async () => {
        if (Date.now() > pollDeadline) {
            stopPolling();
            return;
        }

        try {
            const { data } = await axios.get('/wallet/balance', {
                headers: { Accept: 'application/json' },
            });

            const balance = Number(data?.balance ?? 0);
            if (balance > initialBalance) {
                stopPolling();
                stage.value = 'success';
                router.reload({ only: ['buyer_data'] });
            }
        } catch {
            // silent
        }
    }, 3000);
};

const captureBalance = () => {
    axios
        .get('/wallet/balance', { headers: { Accept: 'application/json' } })
        .then(({ data }) => {
            currentBalance = Number(data?.balance ?? 0);
        })
        .catch(() => {});
};

const stopPolling = () => {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
    pollDeadline = null;
};

// Snapshot the wallet balance at modal open so we can detect the delta.
let currentBalance = 0;

const handleClickOutside = (event) => {
    if (!isOpen.value) return;

    // Don't react to clicks inside our own modal or trigger
    if (
        modalElement.value?.contains(event.target) ||
        popoverContainer.value?.contains(event.target)
    ) {
        return;
    }

    // Don't react to clicks inside ANY other modal/dialog that was
    // teleported to <body> (e.g. DepositPopover stacked on top of us).
    if (
        event.target.closest?.('[role="dialog"]') ||
        event.target.closest?.('[data-popover-layer]')
    ) {
        return;
    }

    closePopover();
};
const handleKeydown = (e) => {
    if (e.key === 'Escape' && isOpen.value) closePopover();
};

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);
    amount.value = props.initial_amount;
    phone.value = props.initial_phone || '';
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
    document.body.classList.remove('overflow-hidden');
    stopPolling();
});

// Capture the balance when the user opens the modal.
watch(isOpen, (v) => {
    if (v) captureBalance();
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