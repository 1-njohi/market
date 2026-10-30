<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{
    pick: any;
}>();

const form = useForm({
    analyst_title: props.pick.analyst_title ?? '',
    reasoning: props.pick.reasoning ?? '',
    legs: props.pick.legs.map((l: any) => ({
        odd_id: l.odd_id,
        comment: l.comment ?? '',
        confidence: l.confidence ?? 3,
    })),
});

const reasoningWordCount = computed(() =>
    (form.reasoning ?? '').trim().split(/\s+/).filter(Boolean).length,
);

const canSave = computed(() => {
    if ((form.analyst_title ?? '').trim().length === 0) return false;
    if (reasoningWordCount.value < 100 || reasoningWordCount.value > 500) return false;
    if (form.legs.some((l: any) => (l.comment ?? '').trim().length === 0)) return false;
    return true;
});

function save() {
    if (!canSave.value) return;
    form.put(`/dashboard/analyst/picks/${props.pick.id}`);
}
</script>

<template>
    <Head title="Edit Pick — Analyst" />

    <div class="mx-auto max-w-3xl p-6">
        <nav class="flex items-center gap-2 text-[10px] font-black uppercase tracking-widest text-slate-500">
            <Link href="/dashboard/analyst" class="hover:text-sky-400">Dashboard</Link>
            <span>›</span>
            <span>Edit Pick</span>
        </nav>

        <h1 class="mt-4 text-2xl font-black uppercase tracking-wider text-white">Edit pick</h1>

        <form @submit.prevent="save" class="mt-6 space-y-5">
            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-sky-400">Headline</label>
                <input v-model="form.analyst_title" type="text" maxlength="140"
                    class="mt-2 w-full rounded-lg border border-[#232d42] bg-[#070b14] px-4 py-2.5 text-sm text-white focus:border-sky-500 focus:outline-none" />
            </div>

            <div>
                <label class="block text-[10px] font-black uppercase tracking-widest text-sky-400">Reasoning</label>
                <textarea v-model="form.reasoning" rows="10"
                    class="mt-2 w-full resize-none rounded-lg border border-[#232d42] bg-[#070b14] px-4 py-2.5 text-sm text-white focus:border-sky-500 focus:outline-none"></textarea>
                <p class="mt-1 text-right text-[10px] font-bold uppercase tracking-widest" :class="reasoningWordCount >= 100 && reasoningWordCount <= 500 ? 'text-emerald-400' : 'text-amber-400'">
                    {{ reasoningWordCount }} / 100–500 words
                </p>
            </div>

            <div class="space-y-3">
                <div v-for="(leg, idx) in pick.legs" :key="leg.odd_id" class="rounded border border-[#232d42] bg-[#0f1422] p-4">
                    <p class="text-sm font-bold text-white">{{ leg.fixture.home_team }} vs {{ leg.fixture.away_team }}</p>
                    <div class="mt-3 grid grid-cols-1 gap-3 md:grid-cols-[1fr_auto]">
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Comment</label>
                            <textarea v-model="form.legs[idx].comment" rows="2" maxlength="200"
                                class="mt-1 w-full resize-none rounded-lg border border-[#232d42] bg-[#070b14] px-3 py-2 text-sm text-white focus:border-sky-500 focus:outline-none"></textarea>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase tracking-widest text-slate-500">Confidence</label>
                            <select v-model.number="form.legs[idx].confidence"
                                class="mt-1 rounded-lg border border-[#232d42] bg-[#070b14] px-3 py-2 text-sm text-white focus:border-sky-500 focus:outline-none">
                                <option v-for="n in [1,2,3,4,5]" :key="n" :value="n">{{ n }}</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <p v-if="form.errors.general" class="text-xs text-red-400">{{ form.errors.general }}</p>

            <div class="flex items-center justify-between gap-3 pt-2">
                <Link href="/dashboard/analyst" class="rounded-lg border border-[#232d42] px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400">Cancel</Link>
                <button type="submit" :disabled="!canSave || form.processing"
                    class="rounded-lg bg-amber-500 px-6 py-2 text-[10px] font-black uppercase tracking-widest text-black disabled:opacity-40">
                    {{ form.processing ? 'Saving…' : 'Save changes' }}
                </button>
            </div>
        </form>
    </div>
</template>