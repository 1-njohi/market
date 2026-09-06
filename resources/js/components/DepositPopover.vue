<template>
    <div class="flex-1" ref="popoverContainer">
        <button
            type="button"
            @click="togglePopover"
            :disabled="isProcessing"
            class="flex w-full cursor-pointer items-center justify-center rounded border border-sky-500/40 bg-transparent py-2.5 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-all duration-200 hover:border-sky-500 hover:bg-sky-500/10 disabled:pointer-events-none disabled:opacity-40"
        >
            <span>DEPOSIT</span>
        </button>

        <div
            v-if="isOpen"
            class="animate-fade-in fixed top-1/2 left-1/2 isolate !z-[9999] w-[calc(100vw-2rem)] -translate-x-1/2 -translate-y-1/2 rounded border border-gray-800 bg-[#0f1422] p-4 shadow-[0_20px_50px_rgba(7,11,20,0.9),0_0_25px_rgba(14,165,233,0.15)] sm:w-80"
        >
            <div
                class="mb-3 flex items-center justify-between border-b border-gray-800 pb-2"
            >
                <span
                    class="text-[9px] font-black tracking-widest text-sky-400 uppercase"
                    >INITILIZE PAYMENT (KES {{ initial_amount ? initial_amount : 10 }} MINIMUM)</span
                >
                <button
                    type="button"
                    @click="closePopover"
                    class="cursor-pointer font-mono text-xs text-slate-500 hover:text-slate-300"
                >
                    ✕
                </button>
            </div>

            <form @submit.prevent="handleDepositSubmission" class="space-y-3">
                <div>
                    <label
                        class="mb-1 block font-mono text-[9px] font-black tracking-wider text-slate-400 uppercase"
                    >
                        SPECIFY AMOUNT ({{ currency }})
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
                            class="w-full rounded border border-[#232d42] bg-[#070b14] py-2 pr-4 pl-12 font-mono text-xs font-semibold tracking-wide text-white placeholder-slate-600 transition-all focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none disabled:opacity-50"
                        />
                    </div>
                </div>

                <button
                    type="submit"
                    :disabled="isProcessing || !amount || amount <= 0"
                    class="flex w-full cursor-pointer items-center justify-center rounded border border-sky-500 bg-sky-500 py-2 text-[10px] font-black tracking-widest text-[#070b14] uppercase transition-all duration-200 hover:bg-transparent hover:text-sky-400 disabled:pointer-events-none disabled:opacity-40"
                >
                    <span v-if="isProcessing" class="flex items-center gap-1.5">
                        <span
                            class="h-1.5 w-1.5 animate-ping rounded-full bg-[#070b14]"
                        ></span>
                        INITIALIZING...
                    </span>
                    <span v-else>INITIALIZE PAYMENT</span>
                </button>
            </form>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import axios from 'axios';

const props= defineProps({
    currency: {
        type: String,
        default: 'KES',
    },
    initial_amount: {
        type: Number,
        required: true,
        default: () => 100,
    },
});


const isOpen = ref(false);
const isProcessing = ref(false);
const amount = ref(0);
const popoverContainer = ref(null);

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
        amount.value = null;
    }
};

const handleDepositSubmission = async () => {
    if (!amount.value || amount.value <= 99) return;
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
            isProcessing.value = false;
            alert(data.message || 'Failed to initiate deposit');
        }
    } catch (error) {
        console.error('Deposit error:', error);
        alert('An unexpected error occurred. Please try again.');
        isProcessing.valvue = false;
    }
};

const handleClickOutside = (event) => {
    // If popover is open, we want to see if the click target was inside the popped window element itself
    const modalElement = document.querySelector('.fixed');
    if (
        isOpen.value &&
        modalElement &&
        !modalElement.contains(event.target) &&
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
        transform: translate(-1/2, -45%) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translate(-1/2, -50%) scale(1);
    }
}
.animate-fade-in {
    /* Merges tailwind centering translations into the transform animation step safely */
    animation: fadeIn 0.15s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
