<template>
    <Head title="Following — Betslip Pirates" />

    <ListPage
        label="Buyer"
        title="Sellers You Follow"
        subtitle="Tipsters you're tracking. Newest follows first."
        back-href="/buyer/dashboard#following"
        back-label="Buyer Dashboard"
    >
        <div
            class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
        >
            <EmptyState
                v-if="following.data.length === 0"
                title="You're not following anyone yet"
                body="Follow tipsters to see their slips first and track their performance over time."
                cta-label="Browse marketplace"
                cta-href="/marketplace"
                accent="purple"
            />

            <div v-else class="divide-y divide-gray-800/40">
                <div
                    v-for="seller in following.data"
                    :key="seller.id"
                    class="flex items-center justify-between gap-3 p-3 transition-colors hover:bg-[#111a30]/40"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <img
                            :src="seller.avatar"
                            :alt="seller.name"
                            class="h-8 w-8 flex-shrink-0 rounded border border-[#232d42] bg-[#070b14]"
                        />
                        <div class="min-w-0">
                            <p
                                class="truncate font-mono text-[11px] font-bold text-white uppercase"
                            >
                                {{ seller.name }}
                            </p>
                            <p
                                class="mt-0.5 truncate text-[10px] text-slate-500"
                            >
                                Code: {{ seller.code }} · Followed
                                {{ seller.followed_at }}
                            </p>
                        </div>
                    </div>

                    <Link
                        :href="`/profile/${seller.code}`"
                        class="flex-shrink-0 rounded border border-purple-500/20 bg-purple-500/5 px-3 py-1.5 font-mono text-[9px] font-black tracking-widest text-purple-400 uppercase transition-all hover:border-purple-500 hover:bg-purple-500 hover:text-[#070b14]"
                    >
                        View Profile →
                    </Link>
                </div>
            </div>
        </div>

        <template #pagination>
            <ListPagination :paginator="following" />
        </template>
    </ListPage>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ListPage from '@/components/ListPage.vue';
import ListPagination from '@/components/ListPagination.vue';
import EmptyState from '@/components/EmptyState.vue';

defineProps({
    following: { type: Object, required: true },
});
</script>