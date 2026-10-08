<template>
    <span
        :class="[
            'inline-flex items-center gap-1 transition-colors duration-300',
            direction === 'up' ? 'text-emerald-400' : '',
            direction === 'down' ? 'text-rose-400' : '',
        ]"
    >
        <Money :value="balance" :currency="currency" />
        <Transition
            enter-active-class="transition-opacity duration-150"
            leave-active-class="transition-opacity duration-500"
            enter-from-class="opacity-0"
            leave-to-class="opacity-0"
        >
            <span
                v-if="direction"
                :class="[
                    'font-mono text-[9px] font-black',
                    direction === 'up' ? 'text-emerald-400' : 'text-rose-400',
                ]"
            >
                {{ direction === 'up' ? '▲' : '▼' }}
            </span>
        </Transition>
    </span>
</template>

<script setup>
import Money from '@/components/Money.vue';
import { useLiveBalance } from '@/composables/useLiveBalance';

const props = defineProps({
    initial: { type: Number, default: 0 },
    currency: { type: String, default: 'KES' },
});

const { balance, direction } = useLiveBalance(props.initial);
</script>