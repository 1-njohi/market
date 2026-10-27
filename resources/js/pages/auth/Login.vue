<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import AuthShell from '@/components/auth/AuthShell.vue';
import { buttonClass, errorClass, inputClass, labelClass, linkClass } from '@/components/auth/classes';

defineOptions({
    layout: {
        title: 'Log in to your account',
        description: 'Enter your email and password below to log in',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <AppSidebarHeader />
    <Head title="Log in" />

    <AuthShell title="Welcome back" subtitle="Enter your email and password below to log in.">
        <!-- Was never shown before: e.g. "Your password has been reset." -->
        <p v-if="status" role="status" class="mb-5 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm font-semibold text-emerald-300">
            {{ status }}
        </p>

        <Form v-bind="store.form()" :reset-on-success="['password']" v-slot="{ errors, processing }" class="grid gap-5">
            <div class="grid gap-2">
                <Label for="email" :class="labelClass">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    inputmode="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="email@example.com"
                    :class="inputClass"
                />
                <InputError :message="errors.email" :class="errorClass" />
            </div>

            <div class="grid gap-2">
                <div class="flex items-center justify-between gap-3">
                    <Label for="password" :class="labelClass">Password</Label>
                    <TextLink v-if="canResetPassword" :href="request()" :class="['text-sm', linkClass]">Forgot password?</TextLink>
                </div>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Password"
                    :class="inputClass"
                />
                <InputError :message="errors.password" :class="errorClass" />
            </div>

            <Label for="remember" class="flex min-h-11 w-fit cursor-pointer items-center gap-3 text-sm font-semibold text-slate-300">
                <Checkbox id="remember" name="remember" class="h-5 w-5 rounded-md border-white/20 bg-white/[0.04] accent-amber-400" />
                <span>Remember me</span>
            </Label>

            <Button type="submit" :class="buttonClass" :disabled="processing" :aria-busy="processing" data-test="login-button">
                <Spinner v-if="processing" class="h-4 w-4 animate-spin text-[#070b14]" />
                <span>Log in</span>
            </Button>
        </Form>

        <template #footer>
            Don't have an account?
            <TextLink :href="register()" :class="linkClass">Sign up</TextLink>
        </template>
    </AuthShell>
</template>