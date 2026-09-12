<template>
    <div class="relative" ref="bellContainer">
        <!-- Trigger -->
        <button
            type="button"
            @click.stop="togglePanel"
            class="group relative flex items-center gap-1.5 rounded border border-[#232d42] bg-[#111622] p-2 transition-colors hover:border-sky-500/50"
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="2"
                stroke="currentColor"
                class="h-4 w-4 text-slate-300 group-hover:text-sky-400"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0"
                />
            </svg>
            <span
                class="hidden text-[10px] font-black tracking-wider text-slate-400 uppercase md:inline"
                >Notifications</span
            >
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 flex h-4 min-w-4 animate-pulse items-center justify-center rounded-full bg-rose-500 px-1 font-mono text-[9px] font-black text-white"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown Panel -->
        <Teleport to="body">
            <div
                v-if="isOpen"
                class="fixed inset-0 z-[9998]"
                @click.self="closePanel"
            >
                <div
                    class="absolute inset-0 bg-transparent"
                    @click="closePanel"
                ></div>

                <div
                    ref="panelElement"
                    class="animate-fade-in absolute top-20 right-4 w-[calc(100vw-2rem)] max-w-md overflow-hidden rounded border border-gray-800 bg-[#0f1422] shadow-[0_20px_50px_rgba(7,11,20,0.9),0_0_25px_rgba(14,165,233,0.1)] sm:top-24 sm:right-8"
                    @click.stop
                >
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between border-b border-gray-800 bg-[#111a30] px-4 py-3"
                    >
                        <div class="flex items-center gap-2">
                            <span
                                class="text-[10px] font-black tracking-widest text-slate-200 uppercase"
                                >Notifications</span
                            >
                            <span
                                v-if="unreadCount > 0"
                                class="rounded-full bg-rose-500/20 px-1.5 py-0.5 font-mono text-[9px] font-black text-rose-400"
                            >
                                {{ unreadCount }} NEW
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <button
                                v-if="unreadCount > 0"
                                type="button"
                                @click="markAllAsRead"
                                :disabled="isProcessing"
                                class="cursor-pointer rounded border border-sky-500/30 bg-sky-500/5 px-2 py-1 font-mono text-[9px] font-black tracking-wider text-sky-400 uppercase transition-all hover:border-sky-500 hover:bg-sky-500/20 disabled:opacity-40"
                            >
                                Mark all read
                            </button>
                            <button
                                type="button"
                                @click="closePanel"
                                class="cursor-pointer font-mono text-xs text-slate-500 hover:text-slate-300"
                            >
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Body -->
                    <div class="no-scrollbar max-h-[60vh] overflow-y-auto">
                        <!-- Loading -->
                        <div
                            v-if="isLoading"
                            class="p-8 text-center font-mono text-[10px] tracking-wider text-slate-500 uppercase"
                        >
                            Loading...
                        </div>

                        <!-- Empty -->
                        <div
                            v-else-if="notifications.length === 0"
                            class="p-8 text-center"
                        >
                            <div class="mb-2 text-3xl">🔕</div>
                            <p
                                class="font-mono text-[10px] tracking-wider text-slate-500 uppercase"
                            >
                                No notifications yet
                            </p>
                        </div>

                        <!-- List -->
                        <div v-else class="divide-y divide-gray-800/50">
                            <div
                                v-for="n in notifications"
                                :key="n.id"
                                @click="handleNotificationClick(n)"
                                :class="[
                                    'flex cursor-pointer items-start gap-3 p-4 transition-colors hover:bg-[#111622]/60',
                                    !n.read ? 'bg-sky-500/[0.03]' : '',
                                ]"
                            >
                                <!-- Icon -->
                                <div
                                    :class="[
                                        'flex h-8 w-8 flex-shrink-0 items-center justify-center rounded',
                                        iconBg(n.type),
                                    ]"
                                >
                                    <span class="text-sm">{{
                                        iconEmoji(n.type)
                                    }}</span>
                                </div>

                                <!-- Content -->
                                <div class="min-w-0 flex-1">
                                    <div
                                        class="flex items-start justify-between gap-2"
                                    >
                                        <p
                                            class="text-[11px] font-bold tracking-wide text-white uppercase"
                                        >
                                            {{ n.title || 'Notification' }}
                                        </p>
                                        <span
                                            v-if="!n.read"
                                            class="mt-1 flex h-1.5 w-1.5 flex-shrink-0 rounded-full bg-sky-500"
                                        ></span>
                                    </div>
                                    <p
                                        class="mt-0.5 text-[11px] leading-relaxed text-slate-400"
                                    >
                                        {{ n.message }}
                                    </p>
                                    <div
                                        class="mt-1.5 flex items-center gap-2 font-mono text-[9px] text-slate-500"
                                    >
                                        <span>{{ n.time }}</span>
                                        <span
                                            v-if="n.betslip_code"
                                            class="text-sky-400"
                                            >• #{{ n.betslip_code }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div
                        v-if="notifications.length > 0"
                        class="border-t border-gray-800 bg-[#111a30] px-4 py-2 text-center"
                    >
                        <span
                            class="font-mono text-[9px] tracking-widest text-slate-500 uppercase"
                            >Showing last {{ notifications.length }}</span
                        >
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
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
        const { data } = await axios.get('/notifications', {
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

const iconEmoji = (type) => {
    switch (type) {
        case 'betslip_won':
            return '🏆';
        case 'betslip_lost':
            return '💸';
        case 'deposit':
            return '💰';
        case 'withdrawal':
            return '🏦';
        default:
            return '🔔';
    }
};

const iconBg = (type) => {
    switch (type) {
        case 'betslip_won':
            return 'bg-emerald-500/15 text-emerald-400';
        case 'betslip_lost':
            return 'bg-amber-500/15 text-amber-400';
        case 'deposit':
            return 'bg-sky-500/15 text-sky-400';
        case 'withdrawal':
            return 'bg-purple-500/15 text-purple-400';
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

// Auto-refresh unread count every 60s
let refreshInterval = null;

onMounted(() => {
    document.addEventListener('mousedown', handleClickOutside);

    refreshInterval = setInterval(async () => {
        if (!isOpen.value) {
            try {
                const { data } = await axios.get('/notifications', {
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
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(-4px) scale(0.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}
.animate-fade-in {
    animation: fadeIn 0.15s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
