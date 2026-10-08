<template>
    <div class="w-full overflow-x-auto no-scrollbar">
        <div
            class="flex min-w-max items-center gap-1 rounded border border-[#232d42] bg-[#111622] p-1"
        >
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                @click="$emit('update:modelValue', tab.key)"
                :class="[
                    'flex cursor-pointer items-center gap-1.5 rounded px-3 py-2 text-[10px] font-black tracking-widest uppercase transition-all',
                    modelValue === tab.key
                        ? activeClass(tab.accent)
                        : 'text-slate-400 hover:text-white',
                ]"
            >
                <span
                    :class="[
                        'h-1.5 w-1.5 rounded-full',
                        modelValue === tab.key
                            ? dotActiveClass(tab.accent)
                            : 'bg-slate-600',
                    ]"
                ></span>
                <span>{{ tab.label }}</span>
                <span
                    v-if="tab.badge"
                    :class="[
                        'ml-1 rounded-full px-1.5 py-0.5 font-mono text-[9px] font-black',
                        modelValue === tab.key
                            ? 'bg-[#070b14]/30 text-[#070b14]'
                            : 'bg-[#232d42] text-slate-300',
                    ]"
                >
                    {{ tab.badge }}
                </span>
            </button>
        </div>
    </div>
</template>

<script setup>
defineProps({
    modelValue: { type: String, required: true },
    tabs: { type: Array, required: true },
});

defineEmits(['update:modelValue']);

const activeClasses = {
    sky: 'bg-sky-500 text-[#070b14] shadow-[0_0_10px_rgba(14,165,233,0.3)]',
    emerald:
        'bg-emerald-500 text-[#070b14] shadow-[0_0_10px_rgba(16,185,129,0.3)]',
    amber:
        'bg-amber-500 text-[#070b14] shadow-[0_0_10px_rgba(245,158,11,0.3)]',
    rose: 'bg-rose-500 text-white shadow-[0_0_10px_rgba(244,63,94,0.3)]',
    purple:
        'bg-purple-500 text-white shadow-[0_0_10px_rgba(168,85,247,0.3)]',
    slate: 'bg-slate-500 text-white',
};

const dotClasses = {
    sky: 'bg-[#070b14]',
    emerald: 'bg-[#070b14]',
    amber: 'bg-[#070b14]',
    rose: 'bg-white',
    purple: 'bg-white',
    slate: 'bg-white',
};

const activeClass = (accent) => activeClasses[accent] ?? activeClasses.slate;
const dotActiveClass = (accent) => dotClasses[accent] ?? dotClasses.slate;
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
</style>