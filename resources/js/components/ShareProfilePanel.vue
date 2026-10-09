<template>
    <div
        class="overflow-hidden rounded border border-purple-500/30 bg-gradient-to-br from-purple-500/10 via-purple-500/5 to-transparent"
    >
        <div class="border-b border-purple-500/20 bg-[#111a30]/60 p-4">
            <span
                class="text-[10px] font-black tracking-widest text-purple-400 uppercase"
            >
                Share your profile
            </span>
        </div>

        <div class="space-y-4 p-4">
            <!-- Preview -->
            <div class="flex items-center gap-3">
                <img
                    :src="user.avatar"
                    :alt="user.name"
                    class="h-12 w-12 flex-shrink-0 rounded border border-purple-500/30 bg-[#111622]"
                />
                <div class="min-w-0">
                    <p
                        class="truncate font-mono text-sm font-black tracking-wide text-white uppercase"
                    >
                        {{ user.name }}
                    </p>
                    <p
                        class="mt-0.5 truncate font-mono text-[10px] text-slate-500"
                    >
                        Code: {{ user.code }}
                    </p>
                </div>
            </div>

            <!-- URL + copy -->
            <div
                class="flex items-center gap-2 rounded border border-[#232d42] bg-[#0a101f] p-2"
            >
                <span
                    class="flex-1 truncate font-mono text-[11px] text-slate-300"
                >
                    {{ shareUrl }}
                </span>
                <button
                    type="button"
                    @click="copyUrl"
                    :class="[
                        'flex-shrink-0 cursor-pointer rounded border px-2 py-1 font-mono text-[9px] font-black tracking-widest uppercase transition-all',
                        copied
                            ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-400'
                            : 'border-sky-500/40 bg-sky-500/5 text-sky-400 hover:border-sky-500 hover:bg-sky-500/10',
                    ]"
                >
                    {{ copied ? '✓ Copied' : 'Copy' }}
                </button>
            </div>

            <!-- Channels -->
            <div class="flex flex-wrap gap-2">
                <a
                    v-for="channel in channels"
                    :key="channel.name"
                    :href="channel.href"
                    target="_blank"
                    rel="noopener noreferrer"
                    :class="[
                        'flex cursor-pointer items-center gap-1.5 rounded border px-3 py-1.5 font-mono text-[9px] font-black tracking-widest uppercase transition-all',
                        channel.class,
                    ]"
                >
                    {{ channel.label }}
                </a>
                <button
                    v-if="canNativeShare"
                    type="button"
                    @click="nativeShare"
                    class="flex cursor-pointer items-center gap-1.5 rounded border border-slate-500/40 bg-slate-500/5 px-3 py-1.5 font-mono text-[9px] font-black tracking-widest text-slate-300 uppercase transition-all hover:border-slate-400 hover:bg-slate-500/10"
                >
                    More…
                </button>
            </div>

            <p class="text-[10px] leading-relaxed text-slate-500">
                Anyone with this link can view your public tipster profile and
                follow you.
            </p>
        </div>
    </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';

const props = defineProps({
    user: { type: Object, required: true }, // { name, code, avatar }
    shareUrl: { type: String, required: true },
    shareText: { type: String, default: '' },
});

const copied = ref(false);
const canNativeShare = ref(false);
let copyTimer = null;

onMounted(() => {
    canNativeShare.value =
        typeof navigator !== 'undefined' &&
        typeof navigator.share === 'function';
});

onUnmounted(() => {
    if (copyTimer) clearTimeout(copyTimer);
});

const resolvedShareText = computed(
    () =>
        props.shareText ||
        `Follow my tips on Betslip Pirates — ${props.user.name}`,
);

const encodedUrl = computed(() => encodeURIComponent(props.shareUrl));
const encodedText = computed(() => encodeURIComponent(resolvedShareText.value));

const channels = computed(() => [
    {
        name: 'whatsapp',
        label: 'WhatsApp',
        href: `https://wa.me/?text=${encodedText.value}%20${encodedUrl.value}`,
        class:
            'border-emerald-500/40 bg-emerald-500/5 text-emerald-400 hover:border-emerald-500 hover:bg-emerald-500/10',
    },
    {
        name: 'telegram',
        label: 'Telegram',
        href: `https://t.me/share/url?url=${encodedUrl.value}&text=${encodedText.value}`,
        class:
            'border-sky-500/40 bg-sky-500/5 text-sky-400 hover:border-sky-500 hover:bg-sky-500/10',
    },
    {
        name: 'x',
        label: 'X',
        href: `https://twitter.com/intent/tweet?url=${encodedUrl.value}&text=${encodedText.value}`,
        class:
            'border-slate-500/40 bg-slate-500/5 text-slate-300 hover:border-slate-400 hover:bg-slate-500/10',
    },
]);

const copyUrl = async () => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(props.shareUrl);
        } else {
            const ta = document.createElement('textarea');
            ta.value = props.shareUrl;
            ta.style.position = 'fixed';
            ta.style.top = '0';
            ta.style.left = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        }

        copied.value = true;
        if (copyTimer) clearTimeout(copyTimer);
        copyTimer = setTimeout(() => {
            copied.value = false;
        }, 2000);
    } catch (err) {
        console.error('Copy failed:', err);
    }
};

const nativeShare = async () => {
    try {
        await navigator.share({
            title: props.user.name,
            text: resolvedShareText.value,
            url: props.shareUrl,
        });
    } catch {
        // cancelled — no-op
    }
};
</script>