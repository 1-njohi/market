<template>
    <section class="w-full overflow-hidden rounded-3xl border border-white/[0.07] bg-[#0b1220] font-sans text-slate-200">
        <div class="flex items-center justify-between gap-3 border-b border-white/[0.06] px-5 py-4">
            <h2 class="text-base font-bold text-white">{{ title }}</h2>
            <span class="rounded-full bg-purple-400/10 px-2.5 py-1 text-xs font-bold text-purple-300 select-none">Following</span>
        </div>

        <div v-if="!following || following.length === 0" class="p-10 text-center text-sm text-slate-500">
            You are not following any sellers yet.
        </div>

        <div v-else class="w-full">
            <!-- Mobile view -->
            <ul class="block divide-y divide-white/[0.05] md:hidden">
                <li v-for="seller in following" :key="seller.code" class="flex items-center justify-between gap-3 p-4 transition-colors hover:bg-white/[0.03]">
                    <div class="flex min-w-0 items-center gap-3">
                        <img :src="seller.avatar" :alt="seller.name" class="h-12 w-12 shrink-0 rounded-full bg-[#070b14] object-cover ring-2 ring-white/10" />
                        <div class="min-w-0">
                            <h3 class="truncate text-base font-bold text-white">{{ seller.name }}</h3>
                            <p class="mt-0.5 truncate text-xs text-slate-500">
                                <span class="font-mono font-semibold text-sky-300">{{ seller.code }}</span>
                                · Followed {{ seller.followed_at }}
                            </p>
                        </div>
                    </div>

                    <Link
                        :href="`/profile/${seller.code}`"
                        class="inline-flex min-h-11 shrink-0 items-center justify-center rounded-xl border border-purple-400/30 bg-purple-400/10 px-4 text-sm font-bold text-purple-200 transition-all duration-200 hover:bg-purple-400 hover:text-[#070b14] focus-visible:ring-2 focus-visible:ring-purple-300 focus-visible:outline-none active:scale-[0.98]"
                    >
                        View
                    </Link>
                </li>
            </ul>

            <!-- Desktop view -->
            <div class="hidden overflow-x-auto md:block">
                <table class="w-full border-collapse text-left">
                    <thead>
                        <tr class="border-b border-white/[0.07] bg-black/20 text-xs font-semibold text-slate-500">
                            <th class="px-5 py-3.5">Name</th>
                            <th class="px-5 py-3.5">Code</th>
                            <th class="px-5 py-3.5">Followed since</th>
                            <th class="px-5 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/[0.05]">
                        <tr v-for="seller in following" :key="seller.code" class="text-sm transition-colors hover:bg-white/[0.03]">
                            <td class="flex items-center gap-3 px-5 py-4">
                                <img :src="seller.avatar" :alt="seller.name" class="h-10 w-10 rounded-full bg-[#070b14] object-cover ring-2 ring-white/10" />
                                <span class="font-bold text-white">{{ seller.name }}</span>
                            </td>
                            <td class="px-5 py-4 font-mono font-bold tracking-wide text-sky-300">{{ seller.code }}</td>
                            <td class="px-5 py-4 text-slate-400">{{ seller.followed_at }}</td>
                            <td class="px-5 py-4 text-right">
                                <Link
                                    :href="`/profile/${seller.code}`"
                                    class="inline-block rounded-lg border border-purple-400/30 bg-purple-400/10 px-4 py-2 text-xs font-bold text-purple-200 transition-all duration-200 hover:bg-purple-400 hover:text-[#070b14] focus-visible:ring-2 focus-visible:ring-purple-300 focus-visible:outline-none"
                                >
                                    View profile →
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    following: {
        type: Array,
        required: true,
        default: () => [],
    },
    title: {
        type: String,
        required: true,
        default: () => 'Sellers You Follow',
    },
});
</script>