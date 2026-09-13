<template>
  <Transition
    enter-active-class="transform ease-out duration-300 transition"
    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-4"
    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
    leave-active-class="transition ease-in duration-200"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="visible"
      :class="[
        'fixed z-50 flex items-center w-full max-w-sm gap-3 rounded-lg border p-4 shadow-2xl select-none backdrop-blur-md',
        // Responsive Position: Bottom on Mobile, Top-Right on Desktop
        'bottom-4 left-4 right-4 mx-auto sm:top-6 sm:right-6 sm:bottom-auto sm:left-auto sm:mx-0',
        // Dynamic Type-Based Theming
        typeStyles[type].wrapper
      ]"
    >
      <!-- STATUS ICON ACCENT -->
      <div :class="['flex h-8 w-8 shrink-0 items-center justify-center rounded-md border text-base', typeStyles[type].iconContainer]">
        <component :is="typeStyles[type].icon" class="h-4 w-4" />
      </div>

      <!-- MESSAGE TEXT CONTENT -->
      <div class="flex-1 text-xs font-bold tracking-wide uppercase">
        <p :class="typeStyles[type].text">{{ message }}</p>
      </div>

      <!-- MANUAL DISMISS BUTTON -->
      <button
        type="button"
        @click="dismiss"
        class="rounded p-1 text-slate-500 transition-colors hover:bg-[#242f48] hover:text-slate-300 cursor-pointer"
      >
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="h-4 w-4">
          <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
        </svg>
        Lorem ipsum dolor sit amet.
      </button>
    </div>
  </Transition>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount, defineProps, defineEmits } from 'vue';

// Define Props with Fallbacks matching requirements
const props = defineProps({
  message: {
    type: String,
    required: true,
  },
  timeout: {
    type: Number,
    default: 4000, // standard default fallback in milliseconds
  },
  type: {
    type: String,
    default: 'success',
    validator: (value) => ['success', 'warn', 'error'].includes(value),
  },
});

const emit = defineEmits(['dismissed']);
const visible = ref(true);
let timer = null;

// Functional Theme Color Mapping tailored to match the platform accents
const typeStyles = {
  success: {
    wrapper: 'bg-[#161c2a]/95 border-emerald-500/30 text-emerald-400',
    iconContainer: 'bg-emerald-950/50 border-emerald-500/20 text-emerald-400',
    text: 'text-emerald-400',
    icon: {
      template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>`
    }
  },
  warn: {
    // Uses the premium '⚡ Boosted Odds' orange accent color space
    wrapper: 'bg-[#161c2a]/95 border-[#ff8c00]/30 text-[#ff8c00]',
    iconContainer: 'bg-amber-950/40 border-[#ff8c00]/20 text-[#ff8c00]',
    text: 'text-[#ff8c00]',
    icon: {
      template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>`
    }
  },
  error: {
    wrapper: 'bg-[#161c2a]/95 border-red-500/30 text-red-400',
    iconContainer: 'bg-red-950/40 border-red-500/20 text-red-400',
    text: 'text-red-400',
    icon: {
      template: `<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0-10.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.57-.598-3.75h-.152c-3.196 0-6.1-1.249-8.25-3.286Zm0 13.036h.008v.008H12v-.008Z" /></svg>`
    }
  }
};

const dismiss = () => {
  visible.value = false;
  emit('dismissed');
};

// Auto dismissal processing lifecycle hooks
onMounted(() => {
  if (props.timeout > 0) {
    timer = setTimeout(() => {
      dismiss();
    }, props.timeout);
  }
});

onBeforeUnmount(() => {
  if (timer) clearTimeout(timer);
});
</script>