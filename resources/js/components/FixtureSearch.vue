<template>
    <div class="relative w-full">
        <label :for="id" class="sr-only">Find a team</label>
        <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.604 10.604Z" />
            </svg>
        </div>
        <input
            :id="id"
            :value="modelValue"
            @input="$emit('update:modelValue', $event.target.value)"
            type="search"
            inputmode="search"
            autocomplete="off"
            placeholder="Find a team…"
            class="min-h-12 w-full rounded-xl border border-white/10 bg-white/[0.04] py-2 pr-12 pl-11 text-base font-medium text-white placeholder-slate-500 backdrop-blur transition-all focus:border-amber-300/60 focus:bg-white/[0.06] focus:ring-2 focus:ring-amber-300/20 focus:outline-none [&::-webkit-search-cancel-button]:hidden"
        />
        <button
            v-if="modelValue"
            type="button"
            @click="$emit('update:modelValue', '')"
            class="absolute inset-y-0 right-0 flex w-12 cursor-pointer items-center justify-center rounded-r-xl text-slate-400 transition-colors hover:text-white focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none"
            aria-label="Clear search"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</template>

<script setup>
defineProps({
    modelValue: { type: String, default: '' },
    id: { type: String, default: 'fixture-search' },
});

defineEmits(['update:modelValue']);
</script>