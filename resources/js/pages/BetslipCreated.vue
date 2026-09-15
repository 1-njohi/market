<template>
    <Head title="Betslip created — Betslip Pirates" />

    <div class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <!-- Back -->
            <Link
                href="/dashboard"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to dashboard
            </Link>

            <!-- ═══ HERO ═══ -->
            <div class="mt-6 mb-10 border-b border-[#232d42] pb-8">
                <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-500/10 text-emerald-400">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>

                <p class="text-[10px] font-black tracking-widest text-emerald-400 uppercase">
                    Success
                </p>
                <h1 class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                    Your betslip is live
                </h1>
                <p class="mt-3 max-w-2xl text-sm leading-relaxed font-medium text-slate-400">
                    Your picks are saved. Share the code below with anyone you
                    want to sell to — they'll pay to unlock your selections.
                </p>
            </div>

            <!-- ═══ SUMMARY CARD ═══ -->
            <section class="mb-6 overflow-hidden rounded-lg border border-[#232d42] bg-[#161c2a]">
                <header class="flex items-center justify-between border-b border-[#232d42] px-5 py-3">
                    <span class="text-[10px] font-black tracking-widest text-slate-500 uppercase">
                        Bet summary
                    </span>
                    <span class="rounded border border-amber-500/20 bg-amber-500/10 px-2 py-0.5 text-[9px] font-black tracking-widest text-amber-400 uppercase">
                        {{ betslip.status }}
                    </span>
                </header>

                <div class="grid grid-cols-3 divide-x divide-[#232d42]/60">
                    <div class="p-4">
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Total odds
                        </span>
                        <span class="mt-1 block font-mono text-lg font-black text-emerald-400">
                            {{ Number(betslip.total_odds).toFixed(2) }}×
                        </span>
                    </div>
                    <div class="p-4">
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Price
                        </span>
                        <span class="mt-1 block font-mono text-lg font-black text-white">
                            KES {{ Number(betslip.price).toLocaleString('en-KE') }}
                        </span>
                    </div>
                    <div class="p-4">
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Selections
                        </span>
                        <span class="mt-1 block font-mono text-lg font-black text-sky-400">
                            {{ betslip.remaining }}
                        </span>
                    </div>
                </div>
            </section>

            <!-- ═══ SHARE CODE ═══ -->
            <section class="mb-6 rounded-lg border border-[#232d42] bg-[#161c2a] p-5">
                <label class="block text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Your share code
                </label>
                <p class="mt-1 mb-3 text-[11px] text-slate-500">
                    Buyers type this in to find your slip. Anyone with the code can view it.
                </p>
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-[#232d42] bg-[#070b14] px-4 py-3">
                    <span class="font-mono text-xl font-black tracking-widest text-[#ff8c00] uppercase">
                        {{ betslip.code }}
                    </span>
                    <button
                        type="button"
                        @click="copyToClipboard(shareUrl)"
                        class="rounded-lg border border-sky-500/40 bg-sky-500/10 px-4 py-2 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:bg-sky-500/20"
                    >
                        {{ copied ? 'Copied!' : 'Copy link' }}
                    </button>
                </div>
            </section>

            <!-- ═══ SHARE ═══ -->
            <section class="mb-6 rounded-lg border border-[#232d42] bg-[#161c2a] p-5">
                <label class="block text-[10px] font-black tracking-widest text-sky-400 uppercase">
                    Share with buyers
                </label>
                <p class="mt-1 mb-4 text-[11px] text-slate-500">
                    Send it straight to WhatsApp, Telegram, or X — or copy the link anywhere.
                </p>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <a
                        :href="socialLinks.whatsapp"
                        target="_blank"
                        class="flex flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#070b14] p-3 text-slate-300 transition-all hover:border-emerald-500 hover:text-emerald-400"
                    >
                        <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                            <path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.513 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.713-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.42 9.864-9.858.002-2.634-1.023-5.11-2.885-6.974C16.528 1.91 14.058.883 11.432.882c-5.443 0-9.863 4.42-9.867 9.861-.001 1.73.458 3.422 1.33 4.925l-.995 3.635 3.722-.975zm13.125-7.391c-.244-.122-1.441-.712-1.664-.792-.223-.081-.385-.122-.547.122-.162.244-.628.792-.77 0-.142-.162-.284-.203-.527-.325-.244-.122-.963-.355-1.835-1.134-.679-.605-1.138-1.353-1.272-1.596-.134-.244-.014-.375.107-.496.111-.109.244-.284.365-.426.122-.142.162-.244.243-.406.081-.162.041-.305-.02-.426-.061-.122-.547-1.319-.75-1.808-.198-.476-.399-.413-.547-.42-.14-.007-.302-.008-.464-.008s-.426.061-.649.305c-.223.244-.852.833-.852 2.031s.872 2.356.994 2.519c.122.163 1.717 2.622 4.16 3.679.581.252 1.034.402 1.388.515.584.185 1.116.159 1.536.096.468-.071 1.441-.589 1.643-1.158.203-.569.203-1.057.142-1.158-.06-.101-.223-.162-.466-.283z"/>
                        </svg>
                        <span class="text-[10px] font-bold tracking-wider uppercase">WhatsApp</span>
                    </a>

                    <a
                        :href="socialLinks.telegram"
                        target="_blank"
                        class="flex flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#070b14] p-3 text-slate-300 transition-all hover:border-sky-500 hover:text-sky-400"
                    >
                        <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                            <path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18.717-.962 4.084-1.362 5.483-.167.55-.542.717-.892.733-.75.033-1.316-.417-2.033-.85-.183-.117-.35-.233-.517-.35-.55-.417-.183-.633.117-.95.083-.083 1.483-1.35 1.517-1.483.003-.017.003-.083-.031-.117s-.1-.017-.133-.017c-.05 0-1.15.65-2.284 1.333l-2.016 1.183c-.45.267-.85.317-1.167.317-.35 0-1.034-.167-1.534-.317-.616-.183-1.1-.283-1.066-.6.017-.167.267-.333.733-.517 2.884-1.117 4.816-1.85 5.8-2.2 2.8-.983 3.383-1.15 3.766-1.15.083 0 .283.017.417.117.117.1.15.233.167.367z"/>
                        </svg>
                        <span class="text-[10px] font-bold tracking-wider uppercase">Telegram</span>
                    </a>

                    <a
                        :href="socialLinks.twitter"
                        target="_blank"
                        class="flex flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#070b14] p-3 text-slate-300 transition-all hover:border-slate-500 hover:text-white"
                    >
                        <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                        <span class="text-[10px] font-bold tracking-wider uppercase">X</span>
                    </a>

                    <button
                        type="button"
                        @click="triggerNativeShare"
                        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#070b14] p-3 text-[#ff8c00] transition-all hover:border-[#ff8c00] hover:bg-[#ff8c00]/5"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-5 w-5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"/>
                        </svg>
                        <span class="text-[10px] font-bold tracking-wider uppercase">More</span>
                    </button>
                </div>
            </section>

            <!-- ═══ ACTIONS ═══ -->
            <div class="flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-between">
                <Link
                    href="/dashboard"
                    class="rounded-lg border border-[#232d42] px-6 py-3 text-center text-xs font-black tracking-widest text-slate-400 uppercase transition-colors hover:border-sky-500/50 hover:text-slate-200"
                >
                    Back to dashboard
                </Link>
                <Link
                    :href="`/betslip/view/g/${betslip.code}`"
                    class="rounded-lg bg-[#ff8c00] px-8 py-3 text-center text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02] active:scale-95"
                >
                    View my betslip
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const betslip = page.props.betslip;
const copied = ref(false);

const shareUrl = computed(
    () => `${window.location.origin}/betslip/view/g/${betslip.code}`,
);

const shareText = computed(
    () =>
        `Check out my betslip — ${Number(betslip.total_odds).toFixed(2)} total odds. Code: ${betslip.code}`,
);

const socialLinks = computed(() => ({
    whatsapp: `https://api.whatsapp.com/send?text=${encodeURIComponent(shareText.value + ' ' + shareUrl.value)}`,
    telegram: `https://t.me/share/url?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(shareText.value)}`,
    twitter: `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(shareText.value)}`,
}));

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2500);
    } catch (err) {
        console.error('Failed to copy text:', err);
    }
};

const triggerNativeShare = async () => {
    if (navigator.share) {
        try {
            await navigator.share({
                title: 'My betslip',
                text: shareText.value,
                url: shareUrl.value,
            });
        } catch (err) {
            if (err.name !== 'AbortError') console.error('Share failed:', err);
        }
    } else {
        copyToClipboard(shareUrl.value);
    }
};

// Safety net: clear any lingering selections from localStorage now that
// the slip has been created on the server.
onMounted(() => {
    const cleared = [];
    localStorage.setItem('bet_selections', JSON.stringify(cleared));
    window.dispatchEvent(
        new CustomEvent('betSelectionUpdated', {
            detail: { selections: cleared },
        }),
    );
});
</script>