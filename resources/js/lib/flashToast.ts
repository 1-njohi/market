import { reactive } from 'vue';
import { router } from '@inertiajs/vue3';

// Module-scope state — shared by every importer, survives remounts
export const toastState = reactive<{
    success: string | null;
    error: string | null;
}>({
    success: null,
    error: null,
});

let successTimer: ReturnType<typeof setTimeout> | null = null;
let errorTimer: ReturnType<typeof setTimeout> | null = null;

export function showSuccess(msg: string, duration = 4000) {
    if (successTimer) { clearTimeout(successTimer); successTimer = null; }
    toastState.success = msg;
    successTimer = setTimeout(() => {
        toastState.success = null;
        successTimer = null;
    }, duration);
}

export function showError(msg: string, duration = 5000) {
    if (errorTimer) { clearTimeout(errorTimer); errorTimer = null; }
    toastState.error = msg;
    errorTimer = setTimeout(() => {
        toastState.error = null;
        errorTimer = null;
    }, duration);
}

export function dismissSuccess() {
    if (successTimer) { clearTimeout(successTimer); successTimer = null; }
    toastState.success = null;
}

export function dismissError() {
    if (errorTimer) { clearTimeout(errorTimer); errorTimer = null; }
    toastState.error = null;
}

let initialized = false;

export function initializeFlashToast() {
    if (initialized) return;
    initialized = true;

    router.on('success', (event: any) => {
        const flash = event?.detail?.page?.props?.flash;
        if (flash?.success) showSuccess(flash.success);
        if (flash?.error) showError(flash.error);
    });
}