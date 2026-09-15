<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => page.props.auth?.user);
const currentUrl = computed(() => page.url);

const navItems = [
    { label: 'Dashboard', href: '/admin' },
    { label: 'Metrics', href: '/admin/metrics' },
    { label: 'Users', href: '/admin/users' },
    { label: 'Withdrawals', href: '/admin/withdrawals' },
    { label: 'Transactions', href: '/admin/transactions' },
    { label: 'Reports', href: '/admin/reports' },
    { label: 'Betslips', href: '/admin/betslips' },
];

const isActive = (href: string) => {
    if (href === '/admin') return currentUrl.value === '/admin';
    return currentUrl.value.startsWith(href);
};
</script>

<template>
    <div class="flex min-h-screen bg-[#070b14] font-sans text-slate-300">
        <!-- Sidebar -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-60 flex-col border-r border-[#232d42] bg-[#0f1422]"
        >
            <!-- Brand -->
            <div class="flex h-16 items-center border-b border-[#232d42] px-5">
                <Link href="/admin" class="flex items-center gap-2">
                    <span
                        class="text-xs font-black tracking-widest text-white uppercase"
                    >
                        Admin
                    </span>
                </Link>
            </div>

            <!-- Nav -->
            <nav class="flex-1 space-y-1 overflow-y-auto p-3">
                <Link
                    v-for="item in navItems"
                    :key="item.href"
                    :href="item.disabled ? '#' : item.href"
                    :class="[
                        'flex items-center gap-3 rounded-lg px-3 py-2.5 text-xs font-bold tracking-wide uppercase transition-colors',
                        item.disabled
                            ? 'cursor-not-allowed text-slate-600'
                            : isActive(item.href)
                              ? 'bg-sky-500/10 text-sky-400'
                              : 'text-slate-400 hover:bg-[#161c2a] hover:text-slate-200',
                    ]"
                    @click="item.disabled && $event.preventDefault()"
                >
                    <span>{{ item.label }}</span>
                    <span
                        v-if="item.disabled"
                        class="ml-auto rounded border border-[#232d42] px-1.5 py-0.5 text-[8px] text-slate-600"
                    >
                        Soon
                    </span>
                </Link>
            </nav>

            <!-- Footer -->
            <div class="border-t border-[#232d42] p-3">
                <div
                    class="mb-2 flex items-center gap-2 rounded-lg bg-[#161c2a] px-3 py-2"
                >
                    <div
                        class="flex h-7 w-7 items-center justify-center rounded-full bg-sky-500/15 text-[10px] font-black text-sky-400"
                    >
                        {{ user?.name?.substring(0, 2).toUpperCase() || 'AD' }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="truncate text-[10px] font-bold text-slate-200"
                        >
                            {{ user?.name }}
                        </p>
                        <p class="truncate text-[9px] text-slate-500">
                            Administrator
                        </p>
                    </div>
                </div>
                <Link
                    href="/"
                    class="block rounded-lg px-3 py-2 text-[10px] font-bold tracking-widest text-slate-500 uppercase transition-colors hover:bg-[#161c2a] hover:text-slate-300"
                >
                    ← Exit to site
                </Link>
            </div>
        </aside>

        <!-- Main -->
        <main class="ml-60 flex-1">
            <div class="mx-auto w-full max-w-6xl px-6 py-8 lg:px-10">
                <slot />
            </div>
        </main>
    </div>
</template>
