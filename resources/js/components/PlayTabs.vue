<template>
    <nav
        v-if="variant === 'bottom-bar'"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-white/[0.08] bg-[#0a1628]/95 backdrop-blur-xl lg:hidden"
        style="padding-bottom: env(safe-area-inset-bottom, 0px)"
        aria-label="Play sections"
    >
        <div class="no-scrollbar flex overflow-x-auto px-1">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                @click="$emit('update:modelValue', tab.key)"
                :aria-current="modelValue === tab.key ? 'page' : undefined"
                :class="[
                    'relative flex min-h-14 min-w-0 flex-1 flex-col items-center justify-center gap-1 px-1 pt-1.5 pb-2 text-xs font-semibold transition-colors',
                    modelValue === tab.key ? activeTextClass(tab.accent) : 'text-slate-500 active:text-slate-300',
                ]"
            >
                <span
                    :class="[
                        'h-1 w-6 rounded-full transition-all duration-200',
                        modelValue === tab.key ? activeBarClass(tab.accent) : 'bg-transparent',
                    ]"
                ></span>
                <span class="truncate">{{ tab.shortLabel ?? tab.label }}</span>
                <span
                    v-if="tab.badge"
                    class="absolute top-1 right-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-rose-500 px-1 text-[10px] font-black text-white tabular-nums"
                >
                    {{ tab.badge > 99 ? '99+' : tab.badge }}
                </span>
            </button>
        </div>
    </nav>

    <div v-else class="no-scrollbar hidden w-full overflow-x-auto lg:block">
        <div class="flex min-w-max items-center gap-1 rounded-2xl border border-white/[0.07] bg-white/[0.03] p-1.5" role="tablist">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                role="tab"
                :aria-selected="modelValue === tab.key"
                @click="$emit('update:modelValue', tab.key)"
                :class="[
                    'flex cursor-pointer items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition-all focus-visible:ring-2 focus-visible:ring-amber-400 focus-visible:outline-none',
                    modelValue === tab.key ? activeClass(tab.accent) : 'text-slate-400 hover:bg-white/[0.04] hover:text-white',
                ]"
            >
                <span :class="['h-1.5 w-1.5 rounded-full', modelValue === tab.key ? dotActiveClass(tab.accent) : 'bg-slate-600']"></span>
                <span>{{ tab.label }}</span>
                <span
                    v-if="tab.badge"
                    :class="[
                        'ml-0.5 rounded-full px-2 py-0.5 text-xs font-black tabular-nums',
                        modelValue === tab.key ? 'bg-[#0a1628]/25 text-inherit' : 'bg-white/10 text-slate-300',
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
    variant: {
        type: String,
        default: 'inline',
        validator: (v) => ['inline', 'bottom-bar'].includes(v),
    },
});

defineEmits(['update:modelValue']);

const activeClasses = {
    sky: 'bg-sky-400 text-[#0a1628] shadow-[0_8px_24px_-8px_rgba(56,189,248,0.6)]',
    emerald: 'bg-emerald-400 text-[#0a1628] shadow-[0_8px_24px_-8px_rgba(52,211,153,0.6)]',
    amber: 'bg-amber-400 text-[#0a1628] shadow-[0_8px_24px_-8px_rgba(251,191,36,0.6)]',
    rose: 'bg-rose-500 text-white shadow-[0_8px_24px_-8px_rgba(244,63,94,0.6)]',
    purple: 'bg-purple-500 text-white shadow-[0_8px_24px_-8px_rgba(168,85,247,0.6)]',
    slate: 'bg-slate-500 text-white',
};

const dotClasses = {
    sky: 'bg-[#0a1628]',
    emerald: 'bg-[#0a1628]',
    amber: 'bg-[#0a1628]',
    rose: 'bg-white',
    purple: 'bg-white',
    slate: 'bg-white',
};

const textClasses = {
    sky: 'text-sky-300',
    emerald: 'text-emerald-300',
    amber: 'text-amber-300',
    rose: 'text-rose-300',
    purple: 'text-purple-300',
    slate: 'text-slate-200',
};

const barClasses = {
    sky: 'bg-sky-400 shadow-[0_0_10px_rgba(56,189,248,0.7)]',
    emerald: 'bg-emerald-400 shadow-[0_0_10px_rgba(52,211,153,0.7)]',
    amber: 'bg-amber-400 shadow-[0_0_10px_rgba(251,191,36,0.7)]',
    rose: 'bg-rose-400 shadow-[0_0_10px_rgba(244,63,94,0.7)]',
    purple: 'bg-purple-400 shadow-[0_0_10px_rgba(168,85,247,0.7)]',
    slate: 'bg-slate-400',
};

const activeClass = (accent) => activeClasses[accent] ?? activeClasses.slate;
const dotActiveClass = (accent) => dotClasses[accent] ?? dotClasses.slate;
const activeTextClass = (accent) => textClasses[accent] ?? textClasses.slate;
const activeBarClass = (accent) => barClasses[accent] ?? barClasses.slate;
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar { display: none; }
.no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>