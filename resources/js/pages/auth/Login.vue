<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
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

    <div class="flex min-h-[90vh] items-center bg-[#070b14] px-2">
        <Form
            v-bind="store.form()"
            :reset-on-success="['password']"
            v-slot="{ errors, processing }"
            class="mx-auto flex w-full max-w-[420px] flex-col gap-6 rounded-lg border border-[#232d42] bg-[#161c2a] p-6 shadow-2xl select-none"
        >
            <div class="grid gap-5">
                <div class="mb-1 border-b border-[#232d42] pb-3">
                    <h2
                        class="flex items-center gap-2 text-sm font-black tracking-widest text-slate-200 uppercase"
                    >
                        Account Authentication
                    </h2>
                </div>
                <!-- <PasskeyVerify /> -->

                <div class="grid gap-2">
                    <Label
                        for="email"
                        class="text-[11px] font-bold tracking-wider text-[#64748b] uppercase"
                        >Email address</Label
                    >
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        placeholder="email@example.com"
                        class="w-full rounded border border-[#232d42] bg-[#111622] px-3 py-2.5 text-xs font-semibold text-white placeholder-slate-600 transition-colors focus:border-sky-500 focus:outline-none"
                    />
                    <InputError
                        :message="errors.email"
                        class="mt-1 text-xs font-bold text-red-400"
                    />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-center justify-between">
                        <Label
                            for="password"
                            class="text-[11px] font-bold tracking-wider text-[#64748b] uppercase"
                            >Password</Label
                        >
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-[11px] font-bold tracking-wide text-sky-400 uppercase hover:underline"
                            :tabindex="5"
                        >
                            Forgot your password?
                        </TextLink>
                    </div>
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Password"
                        class="w-full rounded border border-[#232d42] bg-[#111622] px-3 py-2.5 text-xs font-semibold text-white placeholder-slate-600 transition-colors focus:border-sky-500 focus:outline-none"
                    />
                    <InputError
                        :message="errors.password"
                        class="mt-1 text-xs font-bold text-red-400"
                    />
                </div>

                <div class="flex items-center justify-between pt-1">
                    <Label
                        for="remember"
                        class="flex cursor-pointer items-center space-x-3 text-xs font-bold text-slate-300"
                    >
                        <Checkbox
                            id="remember"
                            name="remember"
                            :tabindex="3"
                            class="h-4 w-4 rounded border-[#232d42] bg-[#111622] accent-[#ff8c00]"
                        />
                        <span class="tracking-wide">Remember me</span>
                    </Label>
                </div>

                <Button
                    type="submit"
                    class="mt-3 flex w-full cursor-pointer items-center justify-center space-x-2 rounded border-b-2 border-amber-700 bg-[#ff8c00] py-3 text-center text-xs font-black tracking-wider text-black uppercase shadow-md transition-all hover:bg-[#e07b00] disabled:cursor-not-allowed disabled:opacity-50"
                    :tabindex="4"
                    :disabled="processing"
                    data-test="login-button"
                >
                    <Spinner
                        v-if="processing"
                        class="h-4 w-4 animate-spin text-black"
                    />
                    <span>Log in</span>
                </Button>
            </div>

            <div
                class="border-t border-[#232d42]/60 pt-3 text-center text-xs font-bold tracking-wide text-slate-400"
            >
                Don't have an account?
                <TextLink
                    :href="register()"
                    :tabindex="5"
                    class="pl-1 text-sky-400 uppercase hover:underline"
                    >Sign up</TextLink
                >
            </div>
        </Form>
    </div>
</template>
