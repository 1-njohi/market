<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

const logoutForm = useForm({});

const logout = () => {
    if (!confirm('Log out of Betslip Pirates?')) {
        return;
    }
    logoutForm.post('/logout');
};
</script>
<template>
    <!-- Logout icon button -->
    <button
        type="button"
        @click="logout"
        class="flex h-9 w-9 items-center justify-center rounded border border-transparent text-slate-400 transition-colors hover:bg-[#242f48] hover:text-rose-400"
        title="Log out"
        aria-label="Log out"
    >
        <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="2"
            stroke="currentColor"
            class="h-4 w-4"
        >
            <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"
            />
        </svg>
    </button>
</template>
