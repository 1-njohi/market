<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { ref } from 'vue';
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
import AuthShell from '@/components/auth/AuthShell.vue';
import { buttonClass, errorClass, inputClass, labelClass, linkClass } from '@/components/auth/classes';

const props = defineProps<{
    passwordRules: string;
    referral_code?: string;
}>();

defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});

// The referral field is optional, so keep the form short unless someone arrived with a code.
const showReferral = ref(Boolean(props.referral_code));
</script>

<template>
    <AppSidebarHeader />
    <Head title="Register" />

    <AuthShell title="Create your account" subtitle="Free to join. Enter your details below to get started.">
        <Form v-bind="store.form()" :reset-on-success="['password', 'password_confirmation']" v-slot="{ errors, processing }" class="grid gap-5">
            <div class="grid gap-2">
                <Label for="name" :class="labelClass">Name</Label>
                <Input id="name" type="text" name="name" required autofocus autocomplete="name" placeholder="Full name" :class="inputClass" />
                <InputError :message="errors.name" :class="errorClass" />
            </div>

            <div class="grid gap-2">
                <Label for="email" :class="labelClass">Email address</Label>
                <Input id="email" type="email" name="email" inputmode="email" required autocomplete="email" placeholder="email@example.com" :class="inputClass" />
                <InputError :message="errors.email" :class="errorClass" />
            </div>

            <div class="grid gap-2">
                <Label for="phone" :class="labelClass">Phone number</Label>
                <div class="flex h-12 w-full items-center overflow-hidden rounded-xl border border-white/10 bg-white/[0.04] transition-all focus-within:border-amber-300/60 focus-within:bg-white/[0.06] focus-within:ring-2 focus-within:ring-amber-300/20">
                    <div class="relative h-full shrink-0 border-r border-white/10 bg-white/[0.03]">
                        <select
                            id="country_code"
                            name="country_code"
                            aria-label="Country code"
                            class="h-full cursor-pointer appearance-none bg-transparent pr-8 pl-4 text-base font-semibold text-slate-200 focus:outline-none"
                        >
                            <option value="+254">🇰🇪 +254</option>
                            <option value="+1">🇺🇸 +1</option>
                            <option value="+44">🇬🇧 +44</option>
                            <option value="+234">🇳🇬 +234</option>
                            <option value="+27">🇿🇦 +27</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-3 flex items-center text-slate-500">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-3 w-3" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                            </svg>
                        </div>
                    </div>

                    <input
                        id="phone"
                        type="tel"
                        name="phone"
                        inputmode="tel"
                        required
                        autocomplete="tel-national"
                        placeholder="712345678"
                        class="h-full w-full bg-transparent px-4 text-base font-medium text-white placeholder-slate-500 focus:outline-none"
                    />
                </div>
                <InputError :message="errors.phone" :class="errorClass" />
            </div>

            <!-- Referral code: tucked away unless there's a code to show -->
            <div>
                <button
                    v-if="!showReferral"
                    type="button"
                    @click="showReferral = true"
                    class="inline-flex min-h-11 cursor-pointer items-center gap-1.5 rounded-lg text-sm font-bold text-sky-300 transition-colors hover:text-sky-200 focus-visible:ring-2 focus-visible:ring-sky-400 focus-visible:outline-none"
                >
                    <span aria-hidden="true">+</span> Have a referral code?
                </button>

                <div v-else class="grid gap-2">
                    <Label for="referral_code" :class="labelClass">
                        Referral code
                        <span class="ml-1 font-normal text-slate-500">(optional)</span>
                    </Label>
                    <Input
                        id="referral_code"
                        type="text"
                        name="referral_code"
                        autocomplete="off"
                        :value="referral_code"
                        placeholder="e.g. DENIS-XK4"
                        :class="[inputClass, 'font-mono font-black tracking-widest text-amber-300 uppercase placeholder:font-sans placeholder:font-normal placeholder:tracking-normal']"
                    />
                    <p class="text-sm leading-relaxed text-slate-500">Got a code from a friend? You'll get 20% off your first purchase.</p>
                    <InputError :message="errors.referral_code" :class="errorClass" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label for="password" :class="labelClass">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="new-password"
                    placeholder="Password"
                    :passwordrules="passwordRules"
                    :class="inputClass"
                />
                <InputError :message="errors.password" :class="errorClass" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation" :class="labelClass">Confirm password</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    autocomplete="new-password"
                    placeholder="Confirm password"
                    :passwordrules="passwordRules"
                    :class="inputClass"
                />
                <InputError :message="errors.password_confirmation" :class="errorClass" />
            </div>

            <Button type="submit" :class="buttonClass" :disabled="processing" :aria-busy="processing" data-test="register-user-button">
                <Spinner v-if="processing" class="h-4 w-4 animate-spin text-[#070b14]" />
                <span>Create account</span>
            </Button>
        </Form>

        <template #footer>
            Already have an account?
            <TextLink :href="login()" :class="linkClass">Log in</TextLink>
        </template>
    </AuthShell>
</template>

<style scoped>
select option {
    background-color: #111622;
    color: #cbd5e1;
}
</style>