<template>
  <div
    class="relative w-full overflow-hidden border-b border-gray-800/40 bg-[#0a1628] py-20 lg:py-32"
  >
    <!-- TEST MODE BANNER — remove before production -->
    <div
      class="absolute top-0 right-0 left-0 z-20 border-b border-amber-500/30 bg-amber-500/10 py-2 text-center backdrop-blur-sm"
    >
      <p class="text-[10px] font-black tracking-widest text-amber-400 uppercase">
        ⚠ Development Preview — No Real Transactions Processed
      </p>
    </div>

    <!-- Ambient Glows -->
    <div
      class="pointer-events-none absolute top-0 left-1/4 h-96 w-96 rounded-full bg-blue-600/10 blur-[120px]"
    ></div>
    <div
      class="pointer-events-none absolute right-1/4 bottom-0 h-96 w-96 rounded-full bg-amber-500/5 blur-[140px]"
    ></div>

    <!-- Main Content -->
    <div class="relative z-10 mx-auto max-w-6xl px-6 text-center">
      <!-- Brand Tagline -->
      <div
        class="mb-6 inline-flex items-center space-x-2 rounded-full border border-blue-500/20 bg-blue-500/10 px-3 py-1"
      >
        <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-blue-400"></span>
        <span
          class="text-[10px] font-black tracking-widest text-blue-400 uppercase"
        >
          A Marketplace for Verified Betslips
        </span>
      </div>

      <!-- Headline -->
      <h1
        class="mx-auto max-w-4xl text-4xl leading-[1.1] font-black tracking-tight text-white uppercase sm:text-5xl lg:text-6xl"
      >
        Stop Buying Fake Betslips.<br />
        <span
          class="bg-gradient-to-r from-blue-500 via-indigo-400 to-amber-400 bg-clip-text text-transparent"
        >
          Real Picks. Real Results. Real Money.
        </span>
      </h1>

      <!-- Subheadline -->
      <p
        class="mx-auto mt-6 max-w-2xl text-sm leading-relaxed text-gray-400 sm:text-base"
      >
        A marketplace where tipsters sell their betslips and buyers pay to
        unlock the picks. Every result is
        <span class="font-semibold text-gray-200">verified after the match</span>,
        so sellers can't edit or fake their history. If a slip loses, you get
        your money back.
      </p>

      <!-- CTAs -->
      <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
        <!-- Primary -->
        <Link
          href="/marketplace"
          class="w-full rounded-xl bg-blue-600 px-8 py-4 text-xs font-bold tracking-wider text-white uppercase shadow-xl shadow-blue-600/20 transition-all hover:bg-blue-500 active:scale-[0.98] sm:w-auto"
        >
          Browse Betslips
        </Link>

        <!-- Secondary -->
        <button
          @click="$emit('become-seller')"
          class="w-full rounded-xl border border-gray-800 bg-[#0d1527] px-8 py-4 text-xs font-bold tracking-wider text-gray-300 uppercase transition-all hover:bg-[#121b32] hover:text-white sm:w-auto"
        >
          Start Selling Your Betslips
        </button>
      </div>

      <!-- Metrics Bar -->
      <div
        class="mx-auto mt-16 grid max-w-4xl grid-cols-2 gap-6 border-t border-gray-800/40 pt-12 md:grid-cols-4"
      >
        <div class="text-center">
          <span class="font-mono text-2xl font-black tracking-wide text-white"
            >100%</span
          >
          <span
            class="mt-1 block text-[10px] font-bold tracking-widest text-gray-500 uppercase"
          >
            Results Verified
          </span>
        </div>
        <div class="text-center">
          <span
            class="font-mono text-2xl font-black tracking-wide text-green-400"
            >+24.8%</span
          >
          <span
            class="mt-1 block text-[10px] font-bold tracking-widest text-gray-500 uppercase"
          >
            Top Pirate Profit
          </span>
        </div>
        <div class="text-center">
          <span
            class="font-mono text-2xl font-black tracking-wide text-blue-400"
            >0</span
          >
          <span
            class="mt-1 block text-[10px] font-bold tracking-widest text-gray-500 uppercase"
          >
            Fake Results
          </span>
        </div>
        <div class="text-center">
          <span
            class="font-mono text-2xl font-black tracking-wide text-amber-500"
            >Instant</span
          >
          <span
            class="mt-1 block text-[10px] font-bold tracking-widest text-gray-500 uppercase"
          >
            Safe Payouts
          </span>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';

defineEmits(['become-seller']);
</script>