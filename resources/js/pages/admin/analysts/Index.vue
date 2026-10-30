<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    analysts: any[];
}>();

// Simple state for the promote modal
const promoteId = ref<number | null>(null);
const promoteSlug = ref('');
const promoteName = ref('');

function openPromote(analyst: any) {
    promoteId.value = analyst.id;
    promoteName.value = analyst.name;
    promoteSlug.value = analyst.slug || '';
}

function submitPromote() {
    if (!promoteId.value || !promoteSlug.value) return;
    router.post(`/admin/analysts/${promoteId.value}/promote`, {
        slug: promoteSlug.value,
    }, {
        onSuccess: () => { promoteId.value = null; }
    });
}

function demote(analyst: any) {
    if (!confirm(`Remove ${analyst.name} as an analyst? Past picks will remain public.`)) return;
    router.post(`/admin/analysts/${analyst.id}/demote`);
}
</script>

<template>
    <Head title="Analyst Roster" />

    <div class="p-6 max-w-5xl mx-auto">
        <header class="mb-6 border-b border-[#232d42] pb-4">
            <h1 class="text-2xl font-black uppercase tracking-wider text-white">Analyst Roster</h1>
            <p class="mt-1 text-sm text-slate-400">Manage who can publish picks.</p>
        </header>

        <div class="grid gap-4">
            <div v-for="analyst in analysts" :key="analyst.id" class="rounded-xl border border-[#232d42] bg-[#111622] p-4 flex items-center justify-between">
                <div>
                    <p class="text-sm font-bold text-white">{{ analyst.display_name }} ({{ analyst.name }})</p>
                    <p class="text-[11px] text-slate-500">@{{ analyst.slug }} · {{ analyst.record.won }}–{{ analyst.record.lost }}</p>
                </div>
                <div class="flex gap-2">
                    <button @click="openPromote(analyst)" class="rounded border border-[#232d42] px-3 py-1 text-[10px] font-black uppercase tracking-widest text-sky-400 hover:border-sky-500/50">Edit Profile</button>
                    <button @click="demote(analyst)" class="rounded border border-[#232d42] px-3 py-1 text-[10px] font-black uppercase tracking-widest text-red-400 hover:border-red-500/50">Demote</button>
                </div>
            </div>
        </div>

        <!-- promote modal placeholder -->
        <div v-if="promoteId" class="fixed inset-0 z-50 bg-black/80 flex items-center justify-center">
            <div class="bg-[#111622] p-6 rounded-xl border border-[#232d42] w-full max-w-sm">
                <h2 class="text-white font-bold mb-4">Edit {{ promoteName }}</h2>
                <input v-model="promoteSlug" placeholder="Analyst Slug (e.g. jarvis)" class="w-full bg-[#070b14] border border-[#232d42] rounded p-2 text-white mb-2" />
                <div class="flex justify-end gap-2 mt-4">
                    <button @click="promoteId = null" class="text-slate-400 text-xs">Cancel</button>
                    <button @click="submitPromote" class="bg-sky-500 text-black px-4 py-1 rounded text-xs font-bold">Save</button>
                </div>
            </div>
        </div>
    </div>
</template>