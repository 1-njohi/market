<template>
    <div class="mx-auto w-full max-w-2xl px-4 py-8 select-none md:py-16">
        <!-- <iframe
            width="200"
            height="40"
            src="https://store.pesapal.com/embed-code?pageUrl=https://store.pesapal.com/piratesdeposittest"
            frameborder="0"
        ></iframe> -->

        <div
            class="rounded-xl border border-[#232d42] bg-[#161c2a] p-6 text-center shadow-2xl md:p-8"
        >
            <div
                class="mx-auto mb-6 flex h-16 w-16 items-center justify-center rounded-full border border-emerald-500/30 bg-emerald-950/40 text-emerald-400"
            >
                <svg
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="2.5"
                    stroke="currentColor"
                    class="h-8 w-8"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"
                    />
                </svg>
            </div>

            <h1
                class="text-xl font-black tracking-wider text-slate-100 uppercase md:text-2xl"
            >
                Betslip Created Successfully!
            </h1>
            <p
                class="mx-auto mt-3 max-w-md text-xs leading-relaxed font-bold tracking-wide text-slate-400 uppercase"
            >
                Your dynamic slip ticket is locked in. Share it directly with
                your followers, group chats, or potential buyers to trail your
                locks!
            </p>

            <div class="mt-8 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div
                    class="rounded-lg border border-[#232d42] bg-[#111622]/60 p-3 text-left"
                >
                    <span
                        class="block text-[9px] font-black tracking-widest text-[#64748b] uppercase"
                        >Total Odds</span
                    >
                    <span
                        class="mt-1 block text-base font-black text-emerald-400"
                        >{{ Number(betslip.total_odds).toFixed(2) }}</span
                    >
                </div>

                <div
                    class="rounded-lg border border-[#232d42] bg-[#111622]/60 p-3 text-left"
                >
                    <span
                        class="block text-[9px] font-black tracking-widest text-[#64748b] uppercase"
                        >Entry Price</span
                    >
                    <span class="mt-1 block text-base font-black text-slate-200"
                        >KSH {{ betslip.price }}</span
                    >
                </div>

                <div
                    class="rounded-lg border border-[#232d42] bg-[#111622]/60 p-3 text-left"
                >
                    <span
                        class="block text-[9px] font-black tracking-widest text-[#64748b] uppercase"
                        >Remaining Slots</span
                    >
                    <span class="mt-1 block text-base font-black text-sky-400"
                        >{{ betslip.remaining }} Left</span
                    >
                </div>

                <div
                    class="rounded-lg border border-[#232d42] bg-[#111622]/60 p-3 text-left"
                >
                    <span
                        class="block text-[9px] font-black tracking-widest text-[#64748b] uppercase"
                        >Ticket Status</span
                    >
                    <span
                        class="mt-1.5 inline-flex items-center rounded border border-amber-500/20 bg-amber-500/10 px-2 py-0.5 text-[10px] font-black tracking-wider text-amber-500 uppercase"
                    >
                        {{ betslip.status }}
                    </span>
                </div>
            </div>

            <div
                class="mt-4 rounded-lg border border-[#232d42] bg-[#111622] p-4 text-left"
            >
                <span
                    class="text-[10px] font-black tracking-widest text-[#64748b] uppercase"
                    >Unique Booking Code</span
                >
                <div class="mt-1 flex items-center justify-between gap-4">
                    <span
                        class="text-lg font-black tracking-widest text-[#ff8c00] uppercase"
                        >{{ betslip.code }}</span
                    >
                    <button
                        @click="copyToClipboard(shareUrl)"
                        class="flex cursor-pointer items-center gap-1.5 rounded bg-[#242f48] px-3 py-1.5 text-xs font-black text-sky-400 uppercase transition-colors hover:bg-[#2e3c5c]"
                    >
                        <span>{{ copied ? 'Copied!' : 'Copy Link' }}</span>
                    </button>
                </div>
            </div>

            <div class="mt-8 border-t border-[#232d42]/60 pt-6">
                <span
                    class="mb-4 block text-[11px] font-black tracking-widest text-[#64748b] uppercase"
                >
                    Instant Distribution Engine
                </span>

                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <a
                        :href="socialLinks.twitter"
                        target="_blank"
                        class="flex flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#111622] p-3 text-slate-300 transition-all hover:border-slate-500 hover:text-white"
                    >
                        <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                            <path
                                d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"
                            />
                        </svg>
                        <span
                            class="text-[10px] font-bold tracking-wider uppercase"
                            >X / Twitter</span
                        >
                    </a>

                    <a
                        :href="socialLinks.telegram"
                        target="_blank"
                        class="flex flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#111622] p-3 text-slate-300 transition-all hover:border-sky-500 hover:text-sky-400"
                    >
                        <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                            <path
                                d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.562 8.161c-.18.717-.962 4.084-1.362 5.483-.167.55-.542.717-.892.733-.75.033-1.316-.417-2.033-.85-.183-.117-.35-.233-.517-.35-.55-.417-.183-.633.117-.95.083-.083 1.483-1.35 1.517-1.483.003-.017.003-.083-.031-.117s-.1-.017-.133-.017c-.05 0-1.15.65-2.284 1.333l-2.016 1.183c-.45.267-.85.317-1.167.317-.35 0-1.034-.167-1.534-.317-.616-.183-1.1-.283-1.066-.6.017-.167.267-.333.733-.517 2.884-1.117 4.816-1.85 5.8-2.2 2.8-.983 3.383-1.15 3.766-1.15.083 0 .283.017.417.117.117.1.15.233.167.367z"
                            />
                        </svg>
                        <span
                            class="text-[10px] font-bold tracking-wider uppercase"
                            >Telegram</span
                        >
                    </a>

                    <a
                        :href="socialLinks.whatsapp"
                        target="_blank"
                        class="flex flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#111622] p-3 text-slate-300 transition-all hover:border-emerald-500 hover:text-emerald-400"
                    >
                        <svg class="h-5 w-5 fill-current" viewBox="0 0 24 24">
                            <path
                                d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.513 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.713-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.42 9.864-9.858.002-2.634-1.023-5.11-2.885-6.974C16.528 1.91 14.058.883 11.432.882c-5.443 0-9.863 4.42-9.867 9.861-.001 1.73.458 3.422 1.33 4.925l-.995 3.635 3.722-.975zm13.125-7.391c-.244-.122-1.441-.712-1.664-.792-.223-.081-.385-.122-.547.122-.162.244-.628.792-.77 0-.142-.162-.284-.203-.527-.325-.244-.122-.963-.355-1.835-1.134-.679-.605-1.138-1.353-1.272-1.596-.134-.244-.014-.375.107-.496.111-.109.244-.284.365-.426.122-.142.162-.244.243-.406.081-.162.041-.305-.02-.426-.061-.122-.547-1.319-.75-1.808-.198-.476-.399-.413-.547-.42-.14-.007-.302-.008-.464-.008s-.426.061-.649.305c-.223.244-.852.833-.852 2.031s.872 2.356.994 2.519c.122.163 1.717 2.622 4.16 3.679.581.252 1.034.402 1.388.515.584.185 1.116.159 1.536.096.468-.071 1.441-.589 1.643-1.158.203-.569.203-1.057.142-1.158-.06-.101-.223-.162-.466-.283z"
                            />
                        </svg>
                        <span
                            class="text-[10px] font-bold tracking-wider uppercase"
                            >WhatsApp</span
                        >
                    </a>

                    <button
                        @click="triggerNativeShare"
                        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#111622] p-3 text-[#ff8c00] transition-all hover:border-[#ff8c00] hover:bg-[#ff8c00]/5"
                    >
                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="2.5"
                            stroke="currentColor"
                            class="h-5 w-5"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"
                            />
                        </svg>
                        <span
                            class="text-[10px] font-bold tracking-wider uppercase"
                            >More Options</span
                        >
                    </button>
                </div>
            </div>

            <div class="mt-8 border-t border-[#232d42]/40 pt-4">
                <Link
                    :href="`/dashboard`"
                    class="inline-block text-xs font-black tracking-widest text-sky-400 uppercase hover:underline"
                >
                    ← Return to Dashboard
                </Link>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';

const page = usePage();
const betslip = page.props.betslip;
const copied = ref(false);

const shareUrl = computed(
    () => `${window.location.origin}/betslip/view/g/${betslip.code}`,
);

// Enhanced share message including total odds for better click-through context
const shareText = computed(
    () =>
        `Tail my latest dynamic betslip! Odds: ${Number(betslip.total_odds).toFixed(2)} ⚡ Code: ${betslip.code} locked on the platform.`,
);

const socialLinks = computed(() => ({
    twitter: `https://twitter.com/intent/tweet?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(shareText.value)}`,
    telegram: `https://t.me/share/url?url=${encodeURIComponent(shareUrl.value)}&text=${encodeURIComponent(shareText.value)}`,
    whatsapp: `https://api.whatsapp.com/send?text=${encodeURIComponent(shareText.value + ' ' + shareUrl.value)}`,
}));

const copyToClipboard = async (text) => {
    try {
        await navigator.clipboard.writeText(text);
        copied.value = true;
        setTimeout(() => {
            copied.value = false;
        }, 2500);
    } catch (err) {
        console.error('Failed to copy text: ', err);
    }
};

const triggerNativeShare = async () => {
    if (navigator.share) {
        try {
            await navigator.share({
                title: 'Sportsbook Betslip',
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

const clearAll = () => {
    let new_selections = [];

    localStorage.setItem('bet_selections', JSON.stringify(new_selections));

    // A custom event so other components can react
    window.dispatchEvent(
        new CustomEvent('betSelectionUpdated', {
            detail: { selections, new_selections },
        }),
    );
};

onMounted(() => {
    clearAll();
});
</script>
