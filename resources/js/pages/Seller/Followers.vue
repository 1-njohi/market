<template>
    <Head title="Followers — Betslip Pirates" />

    <ListPage
        label="Seller"
        title="All Followers"
        subtitle="Everyone following your tips, newest first."
        back-href="/seller/dashboard#followers"
        back-label="Seller Dashboard"
    >
        <div
            class="overflow-hidden rounded border border-gray-800/60 bg-[#111622]/40"
        >
            <!-- Empty state with share panel -->
            <div v-if="followers.data.length === 0" class="p-6 md:p-8">
                <div class="mx-auto w-full max-w-md space-y-6">
                    <div class="text-center">
                        <p
                            class="text-xs font-black tracking-widest text-slate-300 uppercase"
                        >
                            No followers yet
                        </p>
                        <p
                            class="mx-auto mt-2 max-w-sm text-[11px] leading-relaxed text-slate-500"
                        >
                            Share your profile so people can find you. Every
                            follower is a potential buyer of your tips.
                        </p>
                    </div>

                    <ShareProfilePanel :user="user" :share-url="profileUrl" />
                </div>
            </div>

            <!-- Populated list -->
            <div v-else class="divide-y divide-gray-800/40">
                <div
                    v-for="follower in followers.data"
                    :key="follower.id"
                    class="flex items-center justify-between gap-3 p-3 transition-colors hover:bg-[#111a30]/40"
                >
                    <div class="flex min-w-0 items-center gap-3">
                        <img
                            :src="follower.avatar"
                            :alt="follower.name"
                            class="h-8 w-8 flex-shrink-0 rounded border border-[#232d42] bg-[#070b14]"
                        />
                        <div class="min-w-0">
                            <p
                                class="truncate font-mono text-[11px] font-bold text-white uppercase"
                            >
                                {{ follower.name }}
                            </p>
                            <p
                                class="mt-0.5 truncate text-[10px] text-slate-500"
                            >
                                Code: {{ follower.code }} · Followed
                                {{ follower.followed_at }}
                            </p>
                        </div>
                    </div>

                    <Link
                        :href="`/profile/${follower.code}`"
                        class="flex-shrink-0 rounded border border-purple-500/20 bg-purple-500/5 px-3 py-1.5 font-mono text-[9px] font-black tracking-widest text-purple-400 uppercase transition-all hover:border-purple-500 hover:bg-purple-500 hover:text-[#070b14]"
                    >
                        View Profile →
                    </Link>
                </div>
            </div>
        </div>

        <template #pagination>
            <ListPagination :paginator="followers" />
        </template>
    </ListPage>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ListPage from '@/components/ListPage.vue';
import ListPagination from '@/components/ListPagination.vue';
import ShareProfilePanel from '@/components/ShareProfilePanel.vue';

defineProps({
    followers: { type: Object, required: true },
    user: { type: Object, required: true },
    profileUrl: { type: String, required: true },
});
</script>