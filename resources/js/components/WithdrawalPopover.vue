<template>
    <div class="flex-1" ref="popoverContainer">
        <button
            type="button"
            @click.stop="togglePopover"
            :disabled="disabled || isProcessing"
            class="flex w-full items-center justify-center rounded border border-emerald-500 py-2.5 text-[10px] font-black tracking-widest uppercase transition-all duration-200"
            :class="
                !disabled
                    ? 'cursor-pointer bg-emerald-500 text-[#070b14] shadow-[0_0_15px_rgba(16,185,129,0.1)] hover:bg-transparent hover:text-emerald-400 hover:shadow-[0_0_25px_rgba(16,185,129,0.2)]'
                    : 'border-gray-700 text-gray-500'
            "
        >
            <span>WITHDRAW FUNDS (→)</span>
        </button>

        <Teleport to="body">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-[9999] flex items-center justify-center p-4"
                @click.self="closePopover"
            >
                <!-- Backdrop -->
                <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

                <!-- Modal -->
                <div
                    ref="modalElement"
                    class="animate-fade-in relative w-full max-w-md rounded border border-gray-800 bg-[#0f1422] p-4 shadow-[0_20px_50px_rgba(7,11,20,0.9),0_0_25px_rgba(16,185,129,0.15)] sm:w-96"
                >
                    <div
                        class="mb-3 flex items-center justify-between border-b border-gray-800 pb-2"
                    >
                        <span
                            class="text-[9px] font-black tracking-widest text-emerald-400 uppercase"
                            >WITHDRAW</span
                        >
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
                        class="mb-3 flex items-center justify-between rounded border border-[#232d42] bg-[#0a101f] p-2.5"
                    >
                        <span
                            class="text-[9px] font-black tracking-wider text-slate-500 uppercase"
                            >Available</span
                        >
                        <span class="font-mono text-sm font-black text-emerald-400">
                            {{ currency }}
                            {{
                                Number(availableBalance).toLocaleString(undefined, {
                                    minimumFractionDigits: 2,
                                })
                            }}
                        </span>
                    </div>

                    <form @submit.prevent="handleWithdrawalSubmission" class="space-y-3">
                        <div>
                            <label
                                class="mb-1 block font-mono text-[9px] font-black tracking-wider text-slate-400 uppercase"
                            >
                                Amount ({{ currency }}) — Min 10
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
                                    min="10"
                                    step="any"
                                    placeholder="0.00"
                                    :disabled="isProcessing"
                                    class="w-full rounded border border-[#232d42] bg-[#070b14] py-2 pr-4 pl-14 font-mono text-xs font-semibold tracking-wide text-white placeholder-slate-600 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 focus:outline-none disabled:opacity-50"
                                />
                            </div>
                        </div>

                        <div>
                            <label
                                class="mb-1 block font-mono text-[9px] font-black tracking-wider text-slate-400 uppercase"
                            >
                                M-PESA Phone Number
                            </label>
                            <input
                                type="tel"
                                v-model="phone"
                                required
                                placeholder="2547XXXXXXXX"
                                pattern="^(?:254|\+254|0)?(7|1)\d{8}$"
                                :disabled="isProcessing"
                                class="w-full rounded border border-[#232d42] bg-[#070b14] px-4 py-2 font-mono text-xs font-semibold tracking-wide text-white placeholder-slate-600 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500/20 focus:outline-none disabled:opacity-50"
                            />
                            <p class="mt-1 font-mono text-[9px] text-slate-500">
                                Use Safaricom M-Pesa number (07XX, 01XX, or 2547XX)
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
                            :disabled="isProcessing || !amount || amount < 10 || !phone"
                            class="flex w-full cursor-pointer items-center justify-center rounded border border-emerald-500 bg-emerald-500 py-2.5 text-[10px] font-black tracking-widest text-[#070b14] uppercase transition-all duration-200 hover:bg-transparent hover:text-emerald-400 disabled:pointer-events-none disabled:opacity-40"
                        >
                            <span v-if="isProcessing" class="flex items-center gap-1.5">
                                <span
                                    class="h-1.5 w-1.5 animate-ping rounded-full bg-[#070b14]"
                                ></span>
                                Processing...
                            </span>
                            <span v-else>Confirm</span>
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