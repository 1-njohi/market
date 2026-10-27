<template>
    <div class="relative" ref="bellContainer">
        <!-- Trigger -->
        <button type="button" @click.stop="togglePanel" :aria-expanded="isOpen" aria-haspopup="dialog"
            :aria-label="unreadCount > 0 ? `Notifications, ${unreadCount} unread` : 'Notifications'"
            class="group relative flex h-11 w-11 cursor-pointer items-center justify-center rounded-xl border border-white/10 bg-white/[0.04] transition-all duration-200 hover:border-amber-300/50 focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none active:scale-95">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                stroke="currentColor" class="h-5 w-5 text-slate-200 transition-colors group-hover:text-amber-200"
                aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            <span v-if="unreadCount > 0"
                class="absolute -top-1.5 -right-1.5 flex h-5 min-w-5 items-center justify-center rounded-full border-2 border-[#0b1220] bg-rose-500 px-1 text-[11px] font-black text-white tabular-nums"
                aria-hidden="true">
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Panel -->
        <Teleport to="body">
            <div v-if="isOpen" class="fixed inset-0 z-[9998]" @click.self="closePanel">
                <!-- Dim on phones, invisible click-catcher on larger screens -->
                <div class="absolute inset-0 bg-black/50 backdrop-blur-[2px] sm:bg-transparent sm:backdrop-blur-none"
                    @click="closePanel"></div>

                <div ref="panelElement" role="dialog" aria-modal="true" aria-label="Notifications"
                    class="animate-panel absolute top-[5.25rem] right-3 left-3 flex max-h-[calc(100svh-7rem)] flex-col overflow-hidden rounded-3xl border border-white/10 bg-[#0d1424] shadow-[0_30px_80px_-20px_rgba(0,0,0,0.9)] sm:left-auto sm:w-[26rem]"
                    @click.stop>
                    <!-- Header -->
                    <div
                        class="flex shrink-0 items-center justify-between gap-2 border-b border-white/[0.07] py-2.5 pr-2 pl-5">
                        <div class="flex items-center gap-2.5">
                            <h2 class="text-base font-bold text-white">Notifications</h2>
                            <span v-if="unreadCount > 0"
                                class="rounded-full bg-rose-400/15 px-2 py-0.5 text-xs font-bold text-rose-300 tabular-nums">{{
                                unreadCount }} new</span>
                        </div>
                        <div class="flex items-center gap-1">
                            <button v-if="unreadCount > 0" type="button" @click="markAllAsRead" :disabled="isProcessing"
                                class="inline-flex min-h-9 cursor-pointer items-center rounded-lg px-3 text-sm font-bold text-sky-300 transition-colors hover:bg-sky-400/10 hover:text-sky-200 focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none disabled:opacity-40">
                                Mark all read
                            </button>
                            <button type="button" @click="closePanel" aria-label="Close notifications"
                                class="flex h-11 w-11 cursor-pointer items-center justify-center rounded-full text-slate-400 transition-colors hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-amber-300 focus-visible:outline-none">
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="no-scrollbar min-h-0 flex-1 overflow-y-auto">
                        <!-- Loading -->
                        <div v-if="isLoading" class="space-y-px" aria-busy="true" aria-label="Loading notifications">
                            <div v-for="i in 3" :key="i" class="flex items-start gap-3 p-4">
                                <span
                                    class="h-10 w-10 shrink-0 animate-pulse rounded-xl bg-white/[0.07] motion-reduce:animate-none"></span>
                                <div class="flex-1 space-y-2 pt-1">
                                    <span
                                        class="block h-3 w-2/5 animate-pulse rounded-full bg-white/[0.08] motion-reduce:animate-none"></span>
                                    <span
                                        class="block h-3 w-4/5 animate-pulse rounded-full bg-white/[0.05] motion-reduce:animate-none"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Empty -->
                        <div v-else-if="notifications.length === 0"
                            class="flex flex-col items-center gap-3 px-6 py-12 text-center">
                            <span
                                class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/[0.05] text-2xl"
                                aria-hidden="true">🔕</span>
                            <div>
                                <p class="text-base font-bold text-slate-100">No notifications yet</p>
                                <p class="mt-1 text-sm text-slate-500">Results, deposits and contest updates will show
                                    up here.</p>
                            </div>
                        </div>

                        <!-- List -->
                        <ul v-else class="divide-y divide-white/[0.05]">
                            <li v-for="n in notifications" :key="n.id">
                                <button type="button" @click="handleNotificationClick(n)" :class="[
                                    'flex w-full cursor-pointer items-start gap-3.5 p-4 text-left transition-colors hover:bg-white/[0.04] focus-visible:bg-white/[0.05] focus-visible:outline-none',
                                    !n.read ? 'bg-sky-400/[0.05]' : '',
                                ]">
                                    <!-- Icon -->
                                    <span
                                        :class="['flex h-10 w-10 shrink-0 items-center justify-center rounded-xl', iconBg(n.type, n.outcome)]"
                                        aria-hidden="true">
                                        <span class="text-lg">{{ iconEmoji(n.type, n.outcome) }}</span>
                                    </span>

                                    <!-- Content -->
                                    <span class="min-w-0 flex-1">
                                        <span class="flex items-start justify-between gap-2">
                                            <span class="text-sm font-bold text-white">{{ n.title || 'Notification'
                                                }}</span>
                                            <span v-if="!n.read"
                                                class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-sky-400 shadow-[0_0_8px_rgba(56,189,248,0.8)]"
                                                aria-label="Unread"></span>
                                        </span>
                                        <span class="mt-0.5 block text-sm leading-relaxed text-slate-400">{{ n.message
                                            }}</span>
                                        <span class="mt-1.5 flex items-center gap-2 text-xs text-slate-500">
                                            <span>{{ n.time }}</span>
                                            <span v-if="n.betslip_code" class="font-mono font-semibold text-sky-300">#{{
                                                n.betslip_code }}</span>
                                        </span>
                                    </span>
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Footer -->
                    <div v-if="notifications.length > 0"
                        class="flex shrink-0 items-center justify-between gap-3 border-t border-white/[0.07] bg-black/20 py-1.5 pr-2 pl-5">
                        <span class="text-xs text-slate-500">Showing last {{ notifications.length }}</span>
                        <Link href="/notifications" @click="closePanel"
                            class="inline-flex min-h-11 items-center rounded-lg px-3 text-sm font-bold text-sky-300 transition-colors hover:bg-sky-400/10 hover:text-sky-200 focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none">
                            View all →
                        </Link>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    initialUnreadCount: {
        type: Number,
        default: 0,
    },
});

const isOpen = ref(false);
const isLoading = ref(false);
const isProcessing = ref(false);
const unreadCount = ref(props.initialUnreadCount);
const notifications = ref([]);

const bellContainer = ref(null);
const panelElement = ref(null);

const togglePanel = async () => {
    if (isOpen.value) {
        closePanel();
        return;
    }

    isOpen.value = true;
    await fetchNotifications();
};

const closePanel = () => {
    isOpen.value = false;
};

const fetchNotifications = async () => {
    try {
        isLoading.value = true;
        const { data } = await axios.get('/notifications/feed', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (data.success) {
            notifications.value = data.notifications;
            unreadCount.value = data.unread_count;
        }
    } catch (error) {
        console.error('Failed to fetch notifications:', error);
    } finally {
        isLoading.value = false;
    }
};

const markAsRead = async (id) => {
    try {
        const { data } = await axios.post(
            `/notifications/${id}/read`,
            {},
            {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            },
        );
        if (data.success) {
            unreadCount.value = data.unread_count;
            const n = notifications.value.find((x) => x.id === id);
            if (n) n.read = true;
        }
    } catch (error) {
        console.error('Failed to mark notification as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        isProcessing.value = true;
        const { data } = await axios.post(
            '/notifications/read-all',
            {},
            {
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
            },
        );
        if (data.success) {
            unreadCount.value = 0;
            notifications.value = notifications.value.map((n) => ({
                ...n,
                read: true,
            }));
        }
    } catch (error) {
        console.error('Failed to mark all as read:', error);
    } finally {
        isProcessing.value = false;
    }
};

const handleNotificationClick = async (n) => {
    // 1. Mark as read immediately if unread
    if (!n.read) {
        await markAsRead(n.id);
    }

    // 2. Navigate based on notification content
    if (n.betslip_code) {
        closePanel();
        router.visit(`/betslip/view/g/${n.betslip_code}`);
    }
};

// Helpers
const iconEmoji = (type, outcome) => {
    if (type === 'watch_settled') {
        return outcome === 'won' ? '🏆' : outcome === 'voided' ? '↩️' : '💸';
    }
    if (type === 'contest_settled') {
        //if (rank === 1) return '🏆';
        //if (rank === 2) return '🥈';
        //if (rank === 3) return '🥉';
        return '🎯';
    }
    switch (type) {
        case 'betslip_won':
            return '🏆';
        case 'betslip_lost':
            return '💸';
        case 'betslip_voided':
            return '↩️';
        case 'deposit':
            return '💰';
        case 'withdrawal':
            return '🏦';
        case 'referral_reward':
            return '💰';
        case 'referral_attributed':
            return '🎁';
        case 'referral_signup':
            return '🎉';
        case 'contest_join_requested':
            return '🙋';
        case 'contest_entry_status':
            return '✅';
        default:
            return '🔔';
    }
};
const iconBg = (type, outcome) => {
    if (type === 'watch_settled') {
        return outcome === 'won'
            ? 'bg-emerald-500/15 text-emerald-400'
            : outcome === 'voided'
                ? 'bg-slate-500/15 text-slate-400'
                : 'bg-rose-500/15 text-rose-400';
    }
    switch (type) {
        case 'betslip_won':
            return 'bg-emerald-500/15 text-emerald-400';
        case 'betslip_lost':
            return 'bg-amber-500/15 text-amber-400';
        case 'betslip_voided':
            return 'bg-slate-500/15 text-slate-400';
        case 'deposit':
            return 'bg-sky-500/15 text-sky-400';
        case 'withdrawal':
            return 'bg-purple-500/15 text-purple-400';
        case 'referral_reward':
            return 'bg-amber-500/15 text-amber-400';
        case 'referral_attributed':
            return 'bg-amber-500/15 text-amber-400';
        case 'referral_signup':
            return 'bg-emerald-500/15 text-emerald-400';
        case 'contest_settled':
            return 'bg-amber-500/15 text-amber-400';
        case 'contest_join_requested':
            return 'bg-purple-500/15 text-purple-400';
        case 'contest_entry_status':
            return 'bg-sky-500/15 text-sky-400';
        default:
            return 'bg-slate-500/15 text-slate-400';
    }
};

// Click-outside dismissal
const handleClickOutside = (event) => {
    if (
        isOpen.value &&
        panelElement.value &&
        !panelElement.value.contains(event.target) &&
        bellContainer.value &&
        !bellContainer.value.contains(event.target)
    ) {
        closePanel();
    }
};

const handleKeydown = (e) => {
    if (e.key === 'Escape' && isOpen.value) closePanel();
};

// Auto-refresh unread count every 60s
let refreshInterval = null;

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);
    document.addEventListener('keydown', handleKeydown);

    refreshInterval = setInterval(async () => {
        if (!isOpen.value) {
            try {
                const { data } = await axios.get('/notifications/feed', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' },
                });
                if (data.success) {
                    unreadCount.value = data.unread_count;
                }
            } catch (e) {
                // silent
            }
        }
    }, 60000);
});

onUnmounted(() => {
    document.removeEventListener('mousedown', handleClickOutside);
    document.removeEventListener('keydown', handleKeydown);
    if (refreshInterval) clearInterval(refreshInterval);
});
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}

.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}

@keyframes panelIn {
    from {
        opacity: 0;
        transform: translateY(-8px) scale(0.98);
    }

    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.animate-panel {
    animation: panelIn 0.18s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@media (prefers-reduced-motion: reduce) {
    .animate-panel {
        animation: none;
    }
}
</style>