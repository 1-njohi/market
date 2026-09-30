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
                <div
                    class="absolute inset-0 bg-black/70 backdrop-blur-sm"
                ></div>

                <div
                    ref="modalElement"
                    class="animate-fade-in relative w-full max-w-md rounded border border-gray-800 bg-[#0f1422] p-4 shadow-[0_20px_50px_rgba(7,11,20,0.9),0_0_25px_rgba(14,165,233,0.15)] sm:w-96"
                >
                    <!-- HEADER -->
                    <div
                        class="mb-3 flex items-center justify-between border-b border-gray-800 pb-2"
                    >
                        <span
                            class="text-[9px] font-black tracking-widest text-sky-400 uppercase"
                        >
                            <template v-if="stage === 'form'">
                                Add funds (KES {{ minAmount }} MINIMUM)
                            </template>
                            <template v-else-if="stage === 'awaiting'">
                                Awaiting confirmation
                            </template>
                            <template v-else>Deposit received</template>
                        </span>
                        <button
                            type="button"
                            @click="closePopover"
                            :disabled="isProcessing"
                            class="cursor-pointer font-mono text-xs text-slate-500 hover:text-slate-300 disabled:opacity-40"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- FORM STAGE -->
                    <form
                        v-if="stage === 'form'"
                        @submit.prevent="handleDepositSubmission"
                        class="space-y-3"
                    >
                        <div>
                            <label
                                class="mb-1 block font-mono text-[9px] font-black tracking-wider text-slate-400 uppercase"
                            >
                                Amount ({{ currency }}) — Min {{ minAmount }}
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
                                    :min="minAmount"
                                    step="any"
                                    placeholder="0.00"
                                    :disabled="isProcessing"
                                    class="w-full rounded border border-[#232d42] bg-[#070b14] py-2 pr-4 pl-14 font-mono text-xs font-semibold tracking-wide text-white placeholder-slate-600 transition-all focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none disabled:opacity-50"
                                />
                            </div>
                        </div>

                        <div>
                            <label
                                class="mb-1 block font-mono text-[9px] font-black tracking-wider text-slate-400 uppercase"
                            >
                                M-Pesa phone number
                            </label>
                            <input
                                type="tel"
                                v-model="phone"
                                required
                                placeholder="0712345678"
                                :disabled="isProcessing"
                                class="w-full rounded border border-[#232d42] bg-[#070b14] px-3 py-2 font-mono text-xs font-semibold tracking-wide text-white placeholder-slate-600 transition-all focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none disabled:opacity-50"
                            />
                            <p class="mt-1 font-mono text-[9px] text-slate-500">
                                We'll send a payment prompt to this number.
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
                            :disabled="
                                isProcessing ||
                                !amount ||
                                amount < minAmount ||
                                !phone
                            "
                            class="flex w-full cursor-pointer items-center justify-center rounded border border-sky-500 bg-sky-500 py-2.5 text-[10px] font-black tracking-widest text-[#070b14] uppercase transition-all duration-200 hover:bg-transparent hover:text-sky-400 disabled:pointer-events-none disabled:opacity-40"
                        >
                            <span
                                v-if="isProcessing"
                                class="flex items-center gap-1.5"
                            >
                                <span
                                    class="h-1.5 w-1.5 animate-ping rounded-full bg-[#070b14]"
                                ></span>
                                Sending prompt…
                            </span>
                            <span v-else>Send M-Pesa prompt</span>
                        </button>
                    </form>

                    <!-- AWAITING STAGE -->
                    <div v-else-if="stage === 'awaiting'" class="space-y-4">
                        <div
                            class="rounded border border-sky-500/30 bg-sky-500/5 p-4 text-center"
                        >
                            <div
                                class="mx-auto flex h-10 w-10 items-center justify-center rounded-full border border-sky-500/40 bg-sky-500/10"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2"
                                    stroke="currentColor"
                                    class="h-5 w-5 animate-pulse text-sky-400"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3"
                                    />
                                </svg>
                            </div>

                            <p
                                class="mt-3 text-[10px] font-black tracking-widest text-sky-400 uppercase"
                            >
                                Check your phone
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                A prompt for
                                <span class="font-bold text-white"
                                    >KES {{ Number(amount).toFixed(2) }}</span
                                >
                                was sent to
                                <span class="font-mono text-slate-300">{{
                                    phone
                                }}</span
                                >.
                            </p>
                            <p class="mt-2 text-[11px] text-slate-500">
                                Enter your M-Pesa PIN to complete the deposit.
                            </p>
                        </div>

                        <div
                            class="flex items-center justify-center gap-2 font-mono text-[10px] text-slate-500"
                        >
                            <span
                                class="h-1.5 w-1.5 animate-pulse rounded-full bg-sky-400"
                            ></span>
                            Waiting for confirmation…
                        </div>

                        <button
                            type="button"
                            @click="cancelAwaiting"
                            class="w-full cursor-pointer rounded border border-[#232d42] bg-transparent py-2 text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors hover:border-slate-600 hover:text-slate-200"
                        >
                            Cancel
                        </button>
                    </div>

                    <!-- SUCCESS STAGE -->
                    <div v-else class="space-y-4">
                        <div
                            class="rounded border border-emerald-500/30 bg-emerald-500/5 p-4 text-center"
                        >
                            <div
                                class="mx-auto flex h-10 w-10 items-center justify-center rounded-full border border-emerald-500/40 bg-emerald-500/10"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2.5"
                                    stroke="currentColor"
                                    class="h-5 w-5 text-emerald-400"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m4.5 12.75 6 6 9-13.5"
                                    />
                                </svg>
                            </div>

                            <p
                                class="mt-3 text-[10px] font-black tracking-widest text-emerald-400 uppercase"
                            >
                                Deposit received
                            </p>
                            <p class="mt-1 text-xs text-slate-400">
                                <span class="font-bold text-white"
                                    >KES {{ Number(amount).toFixed(2) }}</span
                                >
                                has been added to your wallet.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="closePopover"
                            class="w-full cursor-pointer rounded border border-emerald-500 bg-emerald-500 py-2.5 text-[10px] font-black tracking-widest text-[#070b14] uppercase transition-colors hover:bg-emerald-400"
                        >
                            Done
                        </button>
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
    phone.value = props.initial_phone || '';
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    document.body.classList.remove('overflow-hidden');
    stopPolling();
});

// Capture the balance when the user opens the modal.
watch(isOpen, (v) => {
    if (v) captureBalance();
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
