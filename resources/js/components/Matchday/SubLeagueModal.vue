<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';

const props = defineProps({
    open: { type: Boolean, default: false },
    mode: {
        type: String,
        default: 'join',
        validator: (v) => ['join', 'create'].includes(v),
    },
    seasons: { type: Array, default: () => [] },
    activeSeason: { type: String, default: null },
    leagueOptions: { type: Array, default: () => [] },   // <— new
});

const leagueCodes = ref<string[]>([]);

const emit = defineEmits(['close', 'success']);

// ────────────── Form state ──────────────
const inviteCode = ref('');
const name = ref('');
const season = ref<string | null>(null);
const submitting = ref(false);
const errorMessage = ref<string | null>(null);
const fieldErrors = ref<Record<string, string>>({});

// Reset whenever the modal opens or its mode changes.
watch(
    () => [props.open, props.mode],
    ([isOpen]) => {
        if (!isOpen) return;

        inviteCode.value = '';
        name.value = '';
        season.value = props.activeSeason ?? props.seasons[0] ?? null;
        leagueCodes.value = [];
        submitting.value = false;
        errorMessage.value = null;
        fieldErrors.value = {};
    },
    { immediate: true },
);

const toggleLeague = (code: string) => {
    if (leagueCodes.value.includes(code)) {
        leagueCodes.value = leagueCodes.value.filter((c) => c !== code);
    } else {
        leagueCodes.value = [...leagueCodes.value, code];
    }
};

// ────────────── Submit ──────────────
const submit = async () => {
    if (submitting.value) return;
    submitting.value = true;
    errorMessage.value = null;
    fieldErrors.value = {};

    const url = props.mode === 'join'
        ? '/sub-leagues/join'
        : '/sub-leagues';

    const payload = props.mode === 'join'
        ? { invite_code: inviteCode.value.trim() }
        : {
            name: name.value.trim(),
            season: season.value,
            league_codes: leagueCodes.value,
        };

    try {
        await axios.post(url, payload);
        router.reload({ only: ['sub_leagues', 'current_matchday'] });
        emit('success', { mode: props.mode });
        emit('close');
    } catch (err: any) {
        const status = err?.response?.status;
        const data = err?.response?.data;

        if (status === 422 && data?.errors) {
            fieldErrors.value = Object.fromEntries(
                Object.entries(data.errors).map(([k, v]) => [
                    k,
                    Array.isArray(v) ? v[0] : String(v),
                ]),
            );
        } else if (status === 404) {
            errorMessage.value = 'That invite code doesn’t match any sub-league.';
        } else {
            errorMessage.value =
                data?.message ?? 'Something went wrong. Please try again.';
        }
    } finally {
        submitting.value = false;
    }
};

// ────────────── Close handling ──────────────
const close = () => {
    if (!submitting.value) emit('close');
};

const onKeydown = (e: KeyboardEvent) => {
    if (!props.open) return;
    if (e.key === 'Escape') close();
};

onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));

const title = computed(() =>
    props.mode === 'join' ? 'Join a sub-league' : 'Create a sub-league',
);

const subtitle = computed(() =>
    props.mode === 'join'
        ? 'Paste the invite code your friend shared.'
        : 'Start a private pool for the season. Share the code once it’s created.',
);

const canSubmit = computed(() => {
    if (submitting.value) return false;
    if (props.mode === 'join') return inviteCode.value.trim().length > 0;
    return (
        name.value.trim().length > 0 &&
        !!season.value &&
        leagueCodes.value.length > 0
    );
});
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition-opacity duration-150 ease-out" enter-from-class="opacity-0"
            enter-to-class="opacity-100" leave-active-class="transition-opacity duration-100 ease-in"
            leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="open"
                class="fixed inset-0 z-[60] flex items-end justify-center bg-black/70 backdrop-blur-sm md:items-center md:p-6"
                @click.self="close">
                <div
                    class="flex w-full max-w-md flex-col overflow-hidden rounded-t-2xl border border-[#232d42] bg-[#0f1422] md:rounded-2xl">
                    <!-- Header -->
                    <div
                        class="flex items-center justify-between gap-4 border-b border-[#232d42] bg-[#121824] px-5 py-4">
                        <div class="min-w-0">
                            <p class="text-[9px] font-black tracking-widest text-amber-400 uppercase">
                                Matchday League
                            </p>
                            <h3 class="mt-0.5 truncate font-mono text-sm font-black tracking-wide text-white">
                                {{ title }}
                            </h3>
                        </div>
                        <button type="button" @click="close" :disabled="submitting" aria-label="Close"
                            class="shrink-0 cursor-pointer rounded border border-[#232d42] px-2 py-1 font-mono text-xs font-black text-slate-400 transition-colors hover:border-rose-500/60 hover:text-rose-400 disabled:cursor-not-allowed disabled:opacity-40">
                            ✕
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-5 py-4">
                        <p class="text-[11px] leading-relaxed text-slate-400">
                            {{ subtitle }}
                        </p>

                        <!-- Top-level error (non-field) -->
                        <div v-if="errorMessage"
                            class="mt-4 rounded border border-rose-500/30 bg-rose-500/5 px-3 py-2 text-[11px] text-rose-400">
                            {{ errorMessage }}
                        </div>

                        <!-- Join mode -->
                        <div v-if="mode === 'join'" class="mt-4">
                            <label for="invite_code"
                                class="block text-[10px] font-black tracking-widest text-sky-400 uppercase">
                                Invite code
                            </label>
                            <input id="invite_code" v-model="inviteCode" type="text" maxlength="12" autocomplete="off"
                                autocapitalize="characters" placeholder="ABCDEF"
                                class="mt-2 w-full rounded-lg border border-[#232d42] bg-[#070b14] px-4 py-3 font-mono text-base font-black tracking-widest text-white uppercase placeholder-slate-700 focus:border-amber-500 focus:outline-none"
                                @keyup.enter="canSubmit && submit()" />
                            <p v-if="fieldErrors.invite_code" class="mt-1 text-[11px] text-rose-400">
                                {{ fieldErrors.invite_code }}
                            </p>
                        </div>

                        <!-- Create mode -->
                        <template v-else>
                            <div class="mt-4">
                                <label for="name"
                                    class="block text-[10px] font-black tracking-widest text-sky-400 uppercase">
                                    Sub-league name
                                </label>
                                <input id="name" v-model="name" type="text" maxlength="80" placeholder="Sunday Crew"
                                    class="mt-2 w-full rounded-lg border border-[#232d42] bg-[#070b14] px-4 py-3 text-sm text-white placeholder-slate-700 focus:border-amber-500 focus:outline-none"
                                    @keyup.enter="canSubmit && submit()" />
                                <p v-if="fieldErrors.name" class="mt-1 text-[11px] text-rose-400">
                                    {{ fieldErrors.name }}
                                </p>
                            </div>

                            <div class="mt-4">
                                <label class="block text-[10px] font-black tracking-widest text-sky-400 uppercase">
                                    Leagues
                                </label>
                                <p class="mt-1 mb-2 text-[10px] text-slate-500">
                                    Pick at least one. You'll only be entered into matchdays for the leagues you select.
                                </p>
                                <div class="flex flex-wrap gap-2">
                                    <button v-for="opt in leagueOptions" :key="opt.code" type="button"
                                        data-testid="league-pill" :aria-pressed="leagueCodes.includes(opt.code)"
                                        @click="toggleLeague(opt.code)" :class="[
                                            'cursor-pointer rounded-full border px-3 py-1.5 text-[10px] font-black tracking-widest uppercase transition-all',
                                            leagueCodes.includes(opt.code)
                                                ? 'border-amber-500 bg-amber-500 text-black'
                                                : 'border-[#232d42] bg-[#070b14] text-slate-400 hover:border-amber-500/50 hover:text-amber-400',
                                        ]">
                                        {{ opt.label }}
                                    </button>
                                </div>
                                <p v-if="fieldErrors.league_codes" class="mt-1 text-[11px] text-rose-400">
                                    {{ fieldErrors.league_codes }}
                                </p>
                                <p v-for="(msg, key) in fieldErrors" :key="key" v-show="key.startsWith('league_codes.')"
                                    class="mt-1 text-[11px] text-rose-400">
                                    {{ msg }}
                                </p>
                            </div>

                            <div class="mt-4">
                                <label for="season"
                                    class="block text-[10px] font-black tracking-widest text-sky-400 uppercase">
                                    Season
                                </label>
                                <select id="season" v-model="season"
                                    class="mt-2 w-full rounded-lg border border-[#232d42] bg-[#070b14] px-4 py-3 font-mono text-sm text-white focus:border-amber-500 focus:outline-none">
                                    <option v-for="s in seasons" :key="s" :value="s">
                                        {{ s }}
                                    </option>
                                </select>
                                <p v-if="fieldErrors.season" class="mt-1 text-[11px] text-rose-400">
                                    {{ fieldErrors.season }}
                                </p>
                            </div>
                        </template>

                        <!-- Helper line -->
                        <p v-if="mode === 'create'" class="mt-4 text-[10px] leading-relaxed text-slate-500">
                            You’ll be able to share an invite code as soon as
                            it’s created. Sub-leagues are scoped to a single
                            season — everyone starts fresh next year.
                        </p>
                    </div>

                    <!-- Footer -->
                    <div
                        class="flex flex-col-reverse items-stretch gap-2 border-t border-[#232d42] bg-[#0b101b] px-5 py-4 sm:flex-row sm:items-center sm:justify-end">
                        <button type="button" @click="close" :disabled="submitting"
                            class="cursor-pointer rounded-lg border border-[#232d42] bg-transparent px-5 py-2.5 text-[10px] font-black tracking-widest text-slate-400 uppercase transition-colors hover:border-sky-500/50 hover:text-slate-200 disabled:cursor-not-allowed disabled:opacity-40">
                            Cancel
                        </button>
                        <button type="button" @click="submit" :disabled="!canSubmit" data-testid="submit"
                            class="cursor-pointer rounded-lg bg-amber-500 px-6 py-2.5 text-[10px] font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02] active:scale-95 disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:scale-100">
                            {{ submitting ? 'Saving…' : mode === 'join' ? 'Join' : 'Create' }}
                        </button>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>