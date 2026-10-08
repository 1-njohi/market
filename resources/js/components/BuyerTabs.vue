<template>
    <!-- ═══════════════ BOTTOM BAR — mobile only ═══════════════ -->
    <nav
        v-if="variant === 'bottom-bar'"
        class="fixed inset-x-0 bottom-0 z-40 border-t border-[#232d42] bg-[#0a101f]/95 backdrop-blur-md lg:hidden"
        style="padding-bottom: env(safe-area-inset-bottom, 0px)"
    >
        <div class="no-scrollbar flex overflow-x-auto">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                type="button"
                @click="$emit('update:modelValue', tab.key)"
                :class="[
                    'relative flex min-w-0 flex-1 flex-col items-center gap-1 px-2 py-2.5 transition-colors',
                    'font-mono text-[8px] font-black tracking-widest uppercase',
                    modelValue === tab.key
                        ? activeTextClass(tab.accent)
                        : 'text-slate-500 hover:text-slate-300',
                ]"
            >
                <span
                    :class="[
                        'h-0.5 w-6 rounded-full transition-all duration-200',
                        modelValue === tab.key
                            ? activeBarClass(tab.accent)
                            : 'bg-transparent',
                    ]"
                ></span>
                <span class="truncate">{{ tab.label }}</span>
                <span
                    v-if="tab.badge"
                    class="absolute top-1 right-2 flex h-3.5 min-w-3.5 items-center justify-center rounded-full bg-rose-500 px-1 font-mono text-[8px] font-black text-white"
                >
                    {{ tab.badge > 99 ? '99+' : tab.badge }}
                </span>
            </button>
        </div>
    </nav>

    <!-- ═══════════════ INLINE — desktop only ═══════════════ -->
    <div
        v-else
        class="no-scrollbar hidden w-full overflow-x-auto lg:block"
    >
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
    variant: {
        type: String,
        default: 'inline',
        validator: (v) => ['inline', 'bottom-bar'].includes(v),
    },
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

const textClasses = {
    sky: 'text-sky-400',
    emerald: 'text-emerald-400',
    amber: 'text-amber-400',
    rose: 'text-rose-400',
    purple: 'text-purple-400',
    slate: 'text-slate-300',
};

const barClasses = {
    sky: 'bg-sky-500 shadow-[0_0_8px_rgba(14,165,233,0.6)]',
    emerald: 'bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.6)]',
    amber: 'bg-amber-500 shadow-[0_0_8px_rgba(245,158,11,0.6)]',
    rose: 'bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.6)]',
    purple: 'bg-purple-500 shadow-[0_0_8px_rgba(168,85,247,0.6)]',
    slate: 'bg-slate-400',
};

const activeClass = (accent) => activeClasses[accent] ?? activeClasses.slate;
const dotActiveClass = (accent) => dotClasses[accent] ?? dotClasses.slate;
const activeTextClass = (accent) => textClasses[accent] ?? textClasses.slate;
const activeBarClass = (accent) => barClasses[accent] ?? barClasses.slate;
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