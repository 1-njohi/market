<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AnalystPickCard from '@/components/AnalystPickCard.vue';
import AnalystRecordStrip from '@/components/AnalystRecordStrip.vue';

defineProps<{
    current: any[];
    last_settled: any | null;
    analyst: { name: string; slug: string | null; avatar_url: string | null } | null;
}>();
</script>

<template>
    <section v-if="current.length > 0 || last_settled" class="mx-auto w-full max-w-7xl px-4 py-10 md:px-6 lg:px-8">
        <header class="mb-5 flex flex-wrap items-end justify-between gap-3">
            <div>
                <div class="flex items-center gap-2">
                    <p class="text-[10px] font-black uppercase tracking-widest text-amber-400">
                        This week's picks
                    </p>
                    <span v-if="analyst" class="text-[10px] font-medium text-slate-500">
                        by
                        <Link
                            v-if="analyst.slug"
                            :href="`/analysts/${analyst.slug}`"
                            class="text-sky-400 hover:underline"
                        >
                            {{ analyst.name }}
                        </Link>
                        <span v-else>{{ analyst.name }}</span>
                    </span>
                </div>
                <h2 class="mt-1 text-xl font-black uppercase tracking-wider text-white md:text-2xl">
                    Analyst picks
                </h2>
            </div>
            <Link
                href="/analyst-picks"
                class="text-xs font-black uppercase tracking-widest text-sky-400 hover:underline"
            >
                View all →
            </Link>
        </header>

        <AnalystRecordStrip
            v-if="last_settled && last_settled.settled_count > 0"
            :record="last_settled"
            variant="compact"
            class="mb-4"
        />

        <div v-if="current.length > 0" class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <AnalystPickCard v-for="pick in current" :key="pick.id" :pick="pick" variant="compact" />
        </div>
        <p v-else class="text-sm text-slate-500">
            No open picks this week. <Link href="/analyst-picks" class="text-sky-400 hover:underline">Browse the archive →</Link>
        </p>
    </section>
</template>