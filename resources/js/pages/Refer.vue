<script setup>
// Fixes vs. original:
//  1. Reordered declarations — page/dashboard/shareUrl defined before use.
//  2. All money values wrapped in Number(...).toFixed(2) so decimal-string
//     casts from Laravel don't throw a TypeError during render.
//  3. Copy-feedback flag has a single managed timer, cleared on unmount;
//     failure path is surfaced in the UI, not swallowed.
//  4. Clipboard helper has a legacy textarea fallback for non-secure contexts.
//  5. Dead `shareOnWhatsApp` removed; single source of truth for share links.
//  6. X share URL switched to x.com/intent/post.
//  7. `rel="noopener noreferrer"` on every target="_blank" link.
//  8. aria-labels added to icon-only controls.
//  9. Hardcoded 10% / 20 wins / 12 months / 20% off replaced with the
//     same dynamic `dashboard.terms.*` values the terms card uses.
// 10. `dashboard.referees` guarded against undefined.
// 11. Native-share fallback copies text+URL instead of the bare code.

import { Head, Link, usePage } from '@inertiajs/vue3';
import Footer from '@/components/Footer.vue';
import InfoPopover from '@/components/InfoPopover.vue';
import { computed, onUnmounted, ref } from 'vue';

const page = usePage();
const dashboard = computed(() => page.props.dashboard);

// ---- Dynamic referral terms ------------------------------------------------
const rewardPct = computed(() =>
    Math.round((dashboard.value.terms.reward_percentage ?? 0.1) * 100),
);
const maxWins = computed(() => dashboard.value.terms.max_transactions ?? 20);
const windowMonths = computed(() => dashboard.value.terms.window_months ?? 12);
const refereePct = computed(() =>
    Math.round((dashboard.value.terms.referee_discount_pct ?? 0.2) * 100),
);
const refereeCap = computed(() =>
    Number(dashboard.value.terms.referee_discount_cap ?? 20).toFixed(0),
);

// ---- Share links -----------------------------------------------------------
const shareUrl = computed(() => dashboard.value.share_url);

const shareText = computed(
    () =>
        `Join me on Betslip Pirates — buy and sell verified betslips with a refund guarantee. My code is ${dashboard.value.code}`,
);

const socialLinks = computed(() => ({
    whatsapp: `https://api.whatsapp.com/send?text=${encodeURIComponent(
        shareText.value + ' ' + shareUrl.value,
    )}`,
    telegram: `https://t.me/share/url?url=${encodeURIComponent(
        shareUrl.value,
    )}&text=${encodeURIComponent(shareText.value)}`,
    twitter: `https://x.com/intent/post?url=${encodeURIComponent(
        shareUrl.value,
    )}&text=${encodeURIComponent(shareText.value)}`,
}));

// ---- Copy feedback ---------------------------------------------------------
const copied = ref(false);
const copyFailed = ref(false);
let copyTimer = null;

const flashCopied = () => {
    copied.value = true;
    copyFailed.value = false;
    clearTimeout(copyTimer);
    copyTimer = setTimeout(() => (copied.value = false), 2000);
};

const flashFailed = () => {
    copyFailed.value = true;
    copied.value = false;
    clearTimeout(copyTimer);
    copyTimer = setTimeout(() => (copyFailed.value = false), 2000);
};

onUnmounted(() => clearTimeout(copyTimer));

const copyToClipboard = async (text) => {
    try {
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(text);
        } else {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
        }
        flashCopied();
    } catch (err) {
        console.error('Copy failed:', err);
        flashFailed();
    }
};

const copyCode = () => copyToClipboard(dashboard.value.code);

const triggerNativeShare = async () => {
    if (navigator.share) {
        try {
            await navigator.share({
                title: 'Join Betslip Pirates',
                text: shareText.value,
                url: shareUrl.value,
            });
            return;
        } catch (err) {
            if (err.name === 'AbortError') return;
            console.error('Share failed:', err);
        }
    }
    // Fallback: copy a full invite (text + URL), not just the bare code.
    copyToClipboard(`${shareText.value} ${shareUrl.value}`);
};
</script>

<template>
    <Head title="Refer — Betslip Pirates" />

    <div class="min-h-screen font-sans text-slate-300">
        <div class="mx-auto w-full max-w-[1200px] px-4 py-12 md:px-6 lg:px-8">
            <!-- Header -->
            <div class="mb-10 border-b border-[#232d42] pb-8">
                <div class="flex items-center gap-2">
                    <p
                        class="text-[10px] font-black tracking-widest text-amber-400 uppercase"
                    >
                        Refer &amp; Earn
                    </p>
                    <InfoPopover title="How referrals work">
                        <p>
                            Share your code. When someone signs up and makes a
                            winning purchase, you earn {{ rewardPct }}% of the
                            listed price — paid straight to your wallet.
                        </p>
                        <p>
                            Each referee can earn you rewards on up to
                            {{ maxWins }} winning transactions, or
                            {{ windowMonths }} months from their signup,
                            whichever comes first.
                        </p>
                        <p class="text-slate-400">
                            Your referee gets {{ refereePct }}% off their first
                            purchase (capped at KES {{ refereeCap }}).
                        </p>
                    </InfoPopover>
                </div>
                <h1
                    class="mt-2 text-3xl font-black tracking-wider text-white uppercase md:text-4xl"
                >
                    Refer &amp; Earn
                </h1>
                <p
                    class="mt-3 max-w-2xl text-sm leading-relaxed text-slate-400"
                >
                    Share your code with friends. You earn a share of every
                    winning transaction they make — withdrawable directly to
                    your wallet.
                </p>
            </div>

            <!-- Code + share + stats -->
            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                <!-- Left column: code + share -->
                <div class="space-y-4 lg:col-span-2">
                    <!-- Your code -->
                    <div
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
                    >
                        <label
                            class="block text-[10px] font-black tracking-widest text-sky-400 uppercase"
                        >
                            Your referral code
                        </label>
                        <p class="mt-1 mb-3 text-[11px] text-slate-500">
                            Friends type this in at signup. Anyone with the code
                            can claim it once.
                        </p>
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border border-[#232d42] bg-[#070b14] px-4 py-3"
                        >
                            <span
                                class="font-mono text-xl font-black tracking-widest text-[#ff8c00] uppercase"
                            >
                                {{ dashboard.code }}
                            </span>
                            <button
                                type="button"
                                @click="copyCode"
                                class="cursor-pointer rounded-lg border border-sky-500/40 bg-sky-500/10 px-4 py-2 text-[10px] font-black tracking-widest text-sky-400 uppercase transition-colors hover:bg-sky-500/20"
                            >
                                <span v-if="copyFailed">Copy failed</span>
                                <span v-else-if="copied">Copied!</span>
                                <span v-else>Copy code</span>
                            </button>
                        </div>
                    </div>

                    <!-- Share -->
                    <div
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-5"
                    >
                        <label
                            class="block text-[10px] font-black tracking-widest text-sky-400 uppercase"
                        >
                            Share with friends
                        </label>
                        <p class="mt-1 mb-4 text-[11px] text-slate-500">
                            Send your code straight to WhatsApp, Telegram, or X
                            — or copy the link anywhere.
                        </p>

                        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                            <a
                                :href="socialLinks.whatsapp"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Share on WhatsApp"
                                class="flex flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#070b14] p-3 text-slate-300 transition-all hover:border-emerald-500 hover:text-emerald-400"
                            >
                                <svg
                                    class="h-5 w-5 fill-current"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946C.06 5.348 5.397.01 12.008.01c3.202.001 6.212 1.246 8.477 3.513 2.266 2.268 3.507 5.28 3.505 8.484-.004 6.657-5.34 11.997-11.953 11.997-2.005-.001-3.973-.502-5.713-1.457L0 24zm6.59-4.846c1.6.95 3.188 1.449 4.825 1.451 5.436 0 9.86-4.42 9.864-9.858.002-2.634-1.023-5.11-2.885-6.974C16.528 1.91 14.058.883 11.432.882c-5.443 0-9.863 4.42-9.867 9.861-.001 1.73.458 3.422 1.33 4.925l-.995 3.635 3.722-.975zm13.125-7.391c-.244-.122-1.441-.712-1.664-.792-.223-.081-.385-.122-.547.122-.162.244-.628.792-.77 0-.142-.162-.284-.203-.527-.325-.244-.122-.963-.355-1.835-1.134-.679-.605-1.138-1.353-1.272-1.596-.134-.244-.014-.375.107-.496.111-.109.244-.284.365-.426.122-.142.162-.244.243-.406.081-.162.041-.305-.02-.426-.061-.122-.547-1.319-.75-1.808-.198-.476-.399-.413-.547-.42-.14-.007-.302-.008-.464-.008s-.426.061-.649.305c-.223.244-.852.833-.852 2.031s.872 2.356.994 2.519c.122.163 1.717 2.622 4.16 3.679.581.252 1.034.402 1.388.515.584.185 1.116.159 1.536.096.468-.071 1.441-.589 1.643-1.158.203-.569.203-1.057.142-1.158-.06-.101-.223-.162-.466-.283z"
                                    />
                                </svg>
                                <span
                                    class="text-[10px] font-bold tracking-wider uppercase"
                                    >WhatsApp</span
                                >
                            </a>

                            <a
                                :href="socialLinks.telegram"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Share on Telegram"
                                class="flex flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#070b14] p-3 text-slate-300 transition-all hover:border-sky-500 hover:text-sky-400"
                            >
                                <svg
                                    class="h-5 w-5 fill-current"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
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
                                :href="socialLinks.twitter"
                                target="_blank"
                                rel="noopener noreferrer"
                                aria-label="Share on X"
                                class="flex flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#070b14] p-3 text-slate-300 transition-all hover:border-slate-500 hover:text-white"
                            >
                                <svg
                                    class="h-5 w-5 fill-current"
                                    viewBox="0 0 24 24"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"
                                    />
                                </svg>
                                <span
                                    class="text-[10px] font-bold tracking-wider uppercase"
                                    >X</span
                                >
                            </a>

                            <button
                                type="button"
                                @click="triggerNativeShare"
                                aria-label="Share via device"
                                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border border-[#232d42] bg-[#070b14] p-3 text-[#ff8c00] transition-all hover:border-[#ff8c00] hover:bg-[#ff8c00]/5"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="2.5"
                                    stroke="currentColor"
                                    class="h-5 w-5"
                                    aria-hidden="true"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M7.217 10.907a2.25 2.25 0 1 0 0 2.186m0-2.186c.18.324.283.696.283 1.093s-.103.77-.283 1.093m0-2.186 9.566-5.314m-9.566 7.5 9.566 5.314m0 0a2.25 2.25 0 1 0 3.935 2.186 2.25 2.25 0 0 0-3.935-2.186Zm0-12.814a2.25 2.25 0 1 0 3.933-2.185 2.25 2.25 0 0 0-3.933 2.185Z"
                                    />
                                </svg>
                                <span
                                    class="text-[10px] font-bold tracking-wider uppercase"
                                    >More</span
                                >
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Right column: stats + terms -->
                <div class="space-y-4">
                    <!-- Stats -->
                    <div
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    >
                        <span
                            class="text-[9px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Referees
                        </span>
                        <p class="mt-1 font-mono text-xl font-black text-white">
                            {{ dashboard.referee_count }}
                        </p>
                    </div>

                    <div
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    >
                        <span
                            class="text-[9px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Total earned
                        </span>
                        <p
                            class="mt-1 font-mono text-xl font-black text-emerald-400"
                        >
                            KES {{ Number(dashboard.total_earned).toFixed(2) }}
                        </p>
                    </div>

                    <div
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    >
                        <span
                            class="text-[9px] font-black tracking-widest text-slate-500 uppercase"
                        >
                            Winning transactions
                        </span>
                        <p class="mt-1 font-mono text-xl font-black text-white">
                            {{ dashboard.total_wins }}
                        </p>
                    </div>

                    <!-- Terms -->
                    <div
                        class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    >
                        <div class="flex items-center justify-between">
                            <span
                                class="text-[9px] font-black tracking-widest text-slate-500 uppercase"
                            >
                                Your terms
                            </span>
                            <span
                                v-if="dashboard.terms.is_override"
                                class="rounded border border-amber-500/30 bg-amber-500/10 px-1.5 py-0.5 font-mono text-[9px] font-black tracking-widest text-amber-400 uppercase"
                            >
                                Custom
                            </span>
                        </div>

                        <dl class="mt-3 space-y-2.5 text-[11px]">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <dt class="text-slate-500">You earn</dt>
                                <dd
                                    class="font-mono font-black text-emerald-400"
                                >
                                    {{ rewardPct }}%
                                    <span class="text-slate-500">
                                        of price</span
                                    >
                                </dd>
                            </div>

                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <dt class="text-slate-500">
                                    Per referee, up to
                                </dt>
                                <dd class="font-mono font-black text-white">
                                    {{ maxWins }} wins
                                </dd>
                            </div>

                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <dt class="text-slate-500">Window</dt>
                                <dd class="font-mono font-black text-white">
                                    {{ windowMonths }} months
                                </dd>
                            </div>

                            <div class="border-t border-[#232d42]/60 pt-2.5">
                                <dt class="mb-1.5 text-slate-500">
                                    Your referees get
                                </dt>
                                <dd class="font-mono text-xs text-sky-400">
                                    {{ refereePct }}% off first purchase
                                </dd>
                                <dd
                                    class="mt-0.5 font-mono text-[10px] text-slate-500"
                                >
                                    capped at KES {{ refereeCap }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>

            <!-- Referees table -->
            <section class="mt-10">
                <div
                    class="mb-4 flex items-center justify-between border-b border-[#232d42] pb-3"
                >
                    <h2
                        class="text-[10px] font-black tracking-widest text-sky-400 uppercase"
                    >
                        Your Referees
                    </h2>
                    <span
                        class="font-mono text-[10px] text-slate-500 uppercase"
                    >
                        {{ (dashboard.referees || []).length }} total
                    </span>
                </div>

                <div
                    v-if="!(dashboard.referees || []).length"
                    class="rounded-lg border border-[#232d42] bg-[#161c2a] p-12 text-center"
                >
                    <p
                        class="text-[10px] font-black tracking-widest text-slate-500 uppercase"
                    >
                        No referees yet
                    </p>
                    <p class="mt-3 text-sm text-slate-400">
                        Share your code above to start earning. Every winning
                        transaction your referees make pays you
                        {{ rewardPct }}% of the listed price.
                    </p>
                    <Link
                        href="/marketplace"
                        class="mt-6 inline-block rounded-lg bg-amber-500 px-6 py-3 text-[10px] font-black tracking-widest text-black uppercase transition-colors hover:bg-amber-400"
                    >
                        Browse marketplace
                    </Link>
                </div>

                <div v-else class="space-y-3">
                    <div
                        v-for="r in dashboard.referees"
                        :key="r.referee_id"
                        class="flex flex-wrap items-center justify-between gap-4 rounded-lg border border-[#232d42] bg-[#161c2a] p-4"
                    >
                        <div class="min-w-0">
                            <p
                                class="truncate font-mono text-sm font-black tracking-wide text-slate-200 uppercase"
                            >
                                {{ r.referee_name }}
                            </p>
                            <p class="mt-0.5 text-[10px] text-slate-500">
                                {{ r.wins_counted }} of {{ maxWins }} wins ·
                                <span
                                    :class="
                                        r.is_expired
                                            ? 'text-rose-400'
                                            : 'text-slate-500'
                                    "
                                >
                                    {{ r.is_expired ? 'Expired' : 'Active' }}
                                </span>
                            </p>
                        </div>
                        <div class="text-right">
                            <p
                                class="font-mono text-lg font-black text-emerald-400"
                            >
                                KES {{ Number(r.total_earned).toFixed(2) }}
                            </p>
                            <p class="text-[10px] text-slate-500 uppercase">
                                earned
                            </p>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <Footer />
    </div>
</template>
