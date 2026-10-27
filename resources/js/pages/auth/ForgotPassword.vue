<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { email } from '@/routes/password';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import AuthShell from '@/components/auth/AuthShell.vue';
import { buttonClass, errorClass, inputClass, labelClass, linkClass } from '@/components/auth/classes';

defineOptions({
    layout: {
        title: 'Forgot password',
        description: 'Enter your email to receive a password reset link',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <AppSidebarHeader />
    <Head title="Forgot password" />

    <AuthShell title="Forgot your password?" subtitle="Enter your email and we'll send you a link to reset it.">
        <p v-if="status" role="status" class="mb-5 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm font-semibold text-emerald-300">
            {{ status }}
        </p>

        <Form v-bind="email.form()" v-slot="{ errors, processing }" class="grid gap-5">
            <div class="grid gap-2">
                <Label for="email" :class="labelClass">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    inputmode="email"
                    autocomplete="email"
                    autofocus
                    placeholder="email@example.com"
                    :class="inputClass"
                />
                <InputError :message="errors.email" :class="errorClass" />
            </div>

            <Button type="submit" :class="buttonClass" :disabled="processing" :aria-busy="processing" data-test="email-password-reset-link-button">
                <Spinner v-if="processing" class="h-4 w-4 animate-spin text-[#070b14]" />
                <span>Email password reset link</span>
            </Button>
        </Form>

        <template #footer>
            Or, return to
            <TextLink :href="login()" :class="linkClass">log in</TextLink>
        </template>
    </AuthShell>
</template>