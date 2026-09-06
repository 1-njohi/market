<template>
  <div
    class="relative w-full overflow-hidden bg-[#070b14] border-b border-gray-800/40 py-20 lg:py-32"
  >
    <!-- Cyberpunk Ambient Glows (unchanged) -->
    <div
      class="pointer-events-none absolute top-0 left-1/4 h-96 w-96 rounded-full bg-blue-600/10 blur-[120px]"
    ></div>
    <div
      class="pointer-events-none absolute bottom-0 right-1/4 h-96 w-96 rounded-full bg-amber-500/5 blur-[140px]"
    ></div>

    <!-- Main Content -->
    <div class="relative z-10 mx-auto max-w-6xl px-6 text-center">
      <!-- Brand Tagline -->
      <div
        class="mb-6 inline-flex items-center space-x-2 rounded-full border border-blue-500/20 bg-blue-500/10 px-3 py-1"
      >
        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-blue-400"></span>
        <span
          class="text-[10px] font-black uppercase tracking-widest text-blue-400"
        >
          The Decentralized Tipster Syndicate
        </span>
      </div>

      <!-- Headline -->
      <h1
        class="mx-auto max-w-4xl text-4xl font-black uppercase leading-[1.1] tracking-tight text-white sm:text-5xl lg:text-6xl"
      >
        Stop Buying Faked Slips.<br />
        <span
          class="bg-gradient-to-r from-blue-500 via-indigo-400 to-amber-400 bg-clip-text text-transparent"
        >
          Track. Verify. Monetize.
        </span>
      </h1>

      <!-- Subheadline -->
      <p
        class="mx-auto mt-6 max-w-2xl text-sm leading-relaxed text-gray-400 sm:text-base"
      >
        The ultimate zero-trust marketplace for football insights. Powered by
        direct
        <span class="font-semibold text-gray-200">api-football.com</span>
        auditing. Pirates sell pure, un‑deletable data history. Buyers get
        transparent, mathematical edges.
      </p>

      <!-- CTAs -->
      <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
        <!-- Primary: Explore Slips → use Inertia Link -->
        <Link
          href="/marketplace"
          class="w-full sm:w-auto px-8 py-4 rounded-xl bg-blue-600 hover:bg-blue-500 active:scale-[0.98] transition-all text-white text-xs font-bold uppercase tracking-wider shadow-xl shadow-blue-600/20"
        >
          Explore Verified Slips
        </Link>

        <!-- Secondary: Become Seller -->
        <button
          @click="$emit('become-seller')"
          class="w-full sm:w-auto px-8 py-4 rounded-xl bg-[#0d1527] hover:bg-[#121b32] border border-gray-800 text-gray-300 hover:text-white text-xs font-bold uppercase tracking-wider transition-all"
        >
          Start Selling Your Tips
        </button>
      </div>

      <!-- Metrics Bar -->
      <div
        class="mx-auto mt-16 max-w-4xl grid grid-cols-2 gap-6 border-t border-gray-800/40 pt-12 md:grid-cols-4"
      >
        <div class="text-center">
          <span class="font-mono text-2xl font-black tracking-wide text-white"
            >100%</span
          >
          <span
            class="mt-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500"
          >
            Audited Results
          </span>
        </div>
        <div class="text-center">
          <span
            class="font-mono text-2xl font-black tracking-wide text-green-400"
            >+24.8%</span
          >
          <span
            class="mt-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500"
          >
            Top Pirate ROI
          </span>
        </div>
        <div class="text-center">
          <span class="font-mono text-2xl font-black tracking-wide text-blue-400"
            >0</span
          >
          <span
            class="mt-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500"
          >
            Fakeable Data
          </span>
        </div>
        <div class="text-center">
          <span
            class="font-mono text-2xl font-black tracking-wide text-amber-500"
            >Instant</span
          >
          <span
            class="mt-1 block text-[10px] font-bold uppercase tracking-widest text-gray-500"
          >
            Escrow Settlement
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineEmits(['become-seller']); // explore-slips is no longer needed, it's handled by Link
</script>