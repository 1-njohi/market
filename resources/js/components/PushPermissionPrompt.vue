<script setup>
import { onMounted } from 'vue';
import { usePushNotifications } from '@/composables/usePushNotifications';

const { state, ensureRefreshed, subscribe, dismiss, shouldShowPrompt } =
    usePushNotifications();

onMounted(() => {
    // Idempotent — safe if the component re-mounts.
    ensureRefreshed();
});

const onEnable = async () => {
    await subscribe();
};

const onDismiss = () => {
    dismiss();
};
</script>

<template>
    <Transition
        enter-active-class="transition ease-out duration-300"
        enter-from-class="translate-y-4 opacity-0 sm:translate-y-0 sm:translate-x-4"
        enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
        leave-active-class="transition ease-in duration-200"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div
            v-if="shouldShowPrompt"
            class="fixed inset-x-0 bottom-0 z-50 mx-auto max-h-[85vh] w-full max-w-md overflow-y-auto rounded-t-xl border border-[#232d42] bg-[#0d1527] shadow-2xl sm:top-6 sm:right-6 sm:bottom-auto sm:left-auto sm:mx-0 sm:rounded-lg"
        >
            <div class="p-5">
                <div class="flex items-start gap-3">
                    <div
                        class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full border border-[#ff8c00]/30 bg-[#ff8c00]/10 text-[#ff8c00]"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="h-5 w-5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"
                            />
                        </svg>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p
                            class="text-[10px] font-black tracking-widest text-[#ff8c00] uppercase"
                        >
                            Enable notifications
                        </p>
                        <p
                            class="mt-1 text-sm font-bold tracking-wide text-white"
                        >
                            Know the moment it matters.
                        </p>
                        <p
                            class="mt-1.5 text-[11px] leading-relaxed text-slate-400"
                        >
                            Betslip sold, contest joined, bet settled, buyer
                            messages — delivered straight to this device.
                        </p>
                    </div>
                </div>

                <ul
                    class="mt-4 space-y-1.5 rounded border border-[#232d42] bg-[#111622] p-3 text-[10px] leading-relaxed text-slate-500"
                >
                    <li class="flex gap-2">
                        <span class="text-slate-600">·</span>
                        <span>
                            Applies to
                            <span class="text-slate-300">this browser only</span>.
                            You'll need to enable it separately on your phone or
                            other computers.
                        </span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-slate-600">·</span>
                        <span>
                            Your browser asks only once — if you choose
                            <span class="text-slate-300">Block</span>, you can't
                            be re-prompted. You'd have to change it manually in
                            browser settings.
                        </span>
                    </li>
                    <li class="flex gap-2">
                        <span class="text-slate-600">·</span>
                        <span>
                            Turn them off anytime from
                            <span class="text-slate-300">Settings</span>, or when
                            you see a notification.
                        </span>
                    </li>
                </ul>

                <div class="mt-5 flex items-stretch gap-2">
                    <button
                        type="button"
                        @click="onDismiss"
                        :disabled="state === 'loading'"
                        class="flex-1 cursor-pointer rounded-lg border border-[#232d42] px-4 py-2.5 text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors hover:border-slate-600 hover:text-slate-200 disabled:cursor-not-allowed disabled:opacity-40"
                    >
                        Not now
                    </button>
                    <button
                        type="button"
                        @click="onEnable"
                        :disabled="state === 'loading'"
                        class="flex-1 cursor-pointer rounded-lg bg-[#ff8c00] px-4 py-2.5 text-[10px] font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02] active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:scale-100"
                    >
                        {{ state === 'loading' ? 'Enabling…' : 'Enable' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>