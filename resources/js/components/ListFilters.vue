<template>
    <div class="flex flex-wrap items-center gap-2">
        <span
            class="font-mono text-[9px] font-bold tracking-widest text-slate-500 uppercase"
        >
            {{ label }}
        </span>
        <button
            v-for="opt in options"
            :key="String(opt.value)"
            type="button"
            @click="$emit('update:modelValue', opt.value)"
            :class="[
                'cursor-pointer rounded border px-2.5 py-1 font-mono text-[9px] font-black tracking-widest uppercase transition-all',
                modelValue === opt.value
                    ? 'border-sky-500 bg-sky-500 text-[#070b14] shadow-[0_0_10px_rgba(14,165,233,0.3)]'
                    : 'border-[#232d42] bg-[#111622] text-slate-400 hover:border-slate-600 hover:text-slate-200',
            ]"
        >
            {{ opt.label }}
        </button>
    </div>
</template>

<script setup>
defineProps({
    modelValue: { type: [String, Number, null], default: null },
    options: { type: Array, required: true },
    label: { type: String, default: 'Filter' },
});

defineEmits(['update:modelValue']);
</script>