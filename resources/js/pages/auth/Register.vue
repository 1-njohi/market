<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';

defineProps<{
    passwordRules: string;
    referral_code?: string;
}>();

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});
</script>

<template>
    <Head title="Register" />

    <div
        class="flex min-h-[90vh] items-center overflow-y-hidden bg-[#070b14] px-2 pt-[10vh]"
    >
        <AppSidebarHeader />
        <Form
            v-bind="store.form()"
            :reset-on-success="['password', 'password_confirmation']"
            v-slot="{ errors, processing }"
            class="mx-auto w-full max-w-[420px] flex-col gap-6 rounded-lg border border-[#232d42] bg-[#161c2a] p-6 shadow-2xl select-none"
        >
            <div class="grid gap-5">
                <div class="mb-1 border-b border-[#232d42] pb-3">
                    <h2
                        class="flex items-center gap-2 text-sm font-black tracking-widest text-slate-200 uppercase"
                    >
                        Create Account
                    </h2>
                </div>

                <div class="grid gap-2">
                    <Label
                        for="name"
                        class="text-[11px] font-bold tracking-wider text-[#64748b] uppercase"
                        >Name</Label
                    >
                    <Input
                        id="name"
                        type="text"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="name"
                        name="name"
                        placeholder="Full name"
                        class="w-full rounded border border-[#232d42] bg-[#111622] px-3 py-2.5 text-xs font-semibold text-white placeholder-slate-600 transition-colors focus:border-sky-500 focus:outline-none"
                    />
                    <InputError
                        :message="errors.name"
                        class="mt-1 text-xs font-bold text-red-400"
                    />
                </div>

                <div class="grid gap-2">
                    <Label
                        for="email"
                        class="text-[11px] font-bold tracking-wider text-[#64748b] uppercase"
                        >Email address</Label
                    >
                    <Input
                        id="email"
                        type="email"
                        required
                        :tabindex="2"
                        autocomplete="email"
                        name="email"
                        placeholder="email@example.com"
                        class="w-full rounded border border-[#232d42] bg-[#111622] px-3 py-2.5 text-xs font-semibold text-white placeholder-slate-600 transition-colors focus:border-sky-500 focus:outline-none"
                    />
                    <InputError
                        :message="errors.email"
                        class="mt-1 text-xs font-bold text-red-400"
                    />
                </div>

                <div class="grid gap-2">
                    <Label
                        for="phone"
                        class="text-[11px] font-bold tracking-wider text-[#64748b] uppercase"
                        >Phone Number</Label
                    >
                    <div
                        class="flex w-full items-center overflow-hidden rounded border border-[#232d42] bg-[#111622] transition-all focus-within:border-sky-500"
                    >
                        <div
                            class="relative shrink-0 border-r border-[#232d42] bg-[#161c2a]/40"
                        >
                            <select
                                id="country_code"
                                name="country_code"
                                :tabindex="3"
                                class="cursor-pointer appearance-none bg-transparent py-2.5 pr-7 pl-3 text-xs font-bold tracking-wide text-slate-300 focus:outline-none"
                            >
                                <option value="+254">🇰🇪 +254</option>
                                <option value="+1">🇺🇸 +1</option>
                                <option value="+44">🇬🇧 +44</option>
                                <option value="+234">🇳🇬 +234</option>
                                <option value="+27">🇿🇦 +27</option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-2 flex items-center text-slate-500"
                            >
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="3"
                                    stroke="currentColor"
                                    class="h-2.5 w-2.5"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="m19.5 8.25-7.5 7.5-7.5-7.5"
                                    />
                                </svg>
                            </div>
                        </div>

                        <input
                            id="phone"
                            type="tel"
                            name="phone"
                            required
                            :tabindex="4"
                            autocomplete="tel-national"
                            placeholder="712345678"
                            class="w-full bg-transparent px-3 py-2.5 text-xs font-semibold tracking-wider text-white placeholder-slate-600 focus:outline-none"
                        />
                    </div>
                    <InputError
                        :message="errors.phone"
                        class="mt-1 text-xs font-bold text-red-400"
                    />
                </div>

                <div class="grid gap-2">
                    <Label
                        for="referral_code"
                        class="text-[11px] font-bold tracking-wider text-[#64748b] uppercase"
                    >
                        Referral code
                        <span
                            class="ml-1 font-normal tracking-normal text-slate-500 normal-case"
                        >
                            (optional)
                        </span>
                    </Label>
                    <Input
                        id="referral_code"
                        type="text"
                        :tabindex="5"
                        autocomplete="off"
                        name="referral_code"
                        :value="referral_code"
                        placeholder="e.g. DENIS-XK4"
                        class="w-full rounded border border-[#232d42] bg-[#111622] px-3 py-2.5 font-mono text-xs font-black tracking-widest text-[#ff8c00] uppercase transition-colors placeholder:font-normal placeholder:tracking-normal placeholder:text-slate-600 focus:border-sky-500 focus:outline-none"
                    />
                    <p class="text-[10px] leading-relaxed text-slate-500">
                        Got a code from a friend? You'll get 20% off your first
                        purchase.
                    </p>
                    <InputError
                        :message="errors.referral_code"
                        class="mt-1 text-xs font-bold text-red-400"
                    />
                </div>

                <div class="grid gap-2">
                    <Label
                        for="password"
                        class="text-[11px] font-bold tracking-wider text-[#64748b] uppercase"
                        >Password</Label
                    >
                    <PasswordInput
                        id="password"
                        required
                        :tabindex="5"
                        autocomplete="new-password"
                        name="password"
                        placeholder="Password"
                        :passwordrules="passwordRules"
                        class="w-full rounded border border-[#232d42] bg-[#111622] px-3 py-2.5 text-xs font-semibold text-white placeholder-slate-600 transition-colors focus:border-sky-500 focus:outline-none"
                    />
                    <InputError
                        :message="errors.password"
                        class="mt-1 text-xs font-bold text-red-400"
                    />
                </div>

                <div class="grid gap-2">
                    <Label
                        for="password_confirmation"
                        class="text-[11px] font-bold tracking-wider text-[#64748b] uppercase"
                        >Confirm password</Label
                    >
                    <PasswordInput
                        id="password_confirmation"
                        required
                        :tabindex="6"
                        autocomplete="new-password"
                        name="password_confirmation"
                        placeholder="Confirm password"
                        :passwordrules="passwordRules"
                        class="w-full rounded border border-[#232d42] bg-[#111622] px-3 py-2.5 text-xs font-semibold text-white placeholder-slate-600 transition-colors focus:border-sky-500 focus:outline-none"
                    />
                    <InputError
                        :message="errors.password_confirmation"
                        class="mt-1 text-xs font-bold text-red-400"
                    />
                </div>

                <Button
                    type="submit"
                    class="mt-3 flex w-full cursor-pointer items-center justify-center space-x-2 rounded border-b-2 border-amber-700 bg-[#ff8c00] py-3 text-center text-xs font-black tracking-wider text-black uppercase shadow-md transition-all hover:bg-[#e07b00] disabled:cursor-not-allowed disabled:opacity-50"
                    :tabindex="7"
                    :disabled="processing"
                    data-test="register-user-button"
                >
                    <Spinner
                        v-if="processing"
                        class="h-4 w-4 animate-spin text-black"
                    />
                    <span>Create account</span>
                </Button>
            </div>

            <div
                class="border-t border-[#232d42]/60 pt-3 text-center text-xs font-bold tracking-wide text-slate-400"
            >
                Already have an account?
                <TextLink
                    :href="login()"
                    class="pl-1 text-sky-400 uppercase hover:underline"
                    :tabindex="8"
                >
                    Log in
                </TextLink>
            </div>
        </Form>
    </div>
</template>

<style scoped>
select option {
    background-color: #111622;
    color: #cbd5e1;
}
</style>
