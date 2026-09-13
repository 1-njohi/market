<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const isLoggedIn = computed(() => !!page.props.auth?.user);

const form = useForm({
    name: isLoggedIn.value ? page.props.auth.user.name : '',
    email: isLoggedIn.value ? page.props.auth.user.email : '',
    subject: '',
    message: '',
});

const submit = () => {
    form.post('/contact', {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
};
</script>

<template>
    <Head title="Contact Us" />

    <div class="min-h-screen bg-[#070b14] px-4 py-12 font-sans text-slate-300 md:px-6 lg:px-8">
        <div class="mx-auto w-full max-w-3xl">
            <Link
                href="/"
                class="text-[10px] font-black tracking-widest text-sky-400 uppercase hover:underline"
            >
                ← Back to home
            </Link>

            <div class="mt-6 mb-10 border-b border-[#232d42] pb-8">
                <h1 class="text-3xl font-black tracking-wider text-white uppercase md:text-4xl">
                    Contact Us
                </h1>
                <p class="mt-4 text-sm leading-relaxed text-slate-400">
                    Questions, feedback, partnership enquiries — we read
                    everything and reply within 24 hours.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
                <!-- Form -->
                <div class="lg:col-span-3">
                    <form
                        @submit.prevent="submit"
                        class="space-y-4 rounded-lg border border-[#232d42] bg-[#161c2a] p-6"
                    >
                        <div>
                            <label class="mb-1.5 block text-[10px] font-black tracking-widest text-slate-400 uppercase">
                                Your name
                            </label>
                            <input
                                v-model="form.name"
                                type="text"
                                required
                                class="w-full rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none"
                            />
                            <p v-if="form.errors.name" class="mt-1 text-[10px] font-bold text-rose-400">
                                {{ form.errors.name }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[10px] font-black tracking-widest text-slate-400 uppercase">
                                Email address
                            </label>
                            <input
                                v-model="form.email"
                                type="email"
                                required
                                class="w-full rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none"
                            />
                            <p v-if="form.errors.email" class="mt-1 text-[10px] font-bold text-rose-400">
                                {{ form.errors.email }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[10px] font-black tracking-widest text-slate-400 uppercase">
                                Subject
                            </label>
                            <input
                                v-model="form.subject"
                                type="text"
                                required
                                placeholder="e.g. Account issue, partnership, feedback"
                                class="w-full rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none"
                            />
                            <p v-if="form.errors.subject" class="mt-1 text-[10px] font-bold text-rose-400">
                                {{ form.errors.subject }}
                            </p>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[10px] font-black tracking-widest text-slate-400 uppercase">
                                Message
                            </label>
                            <textarea
                                v-model="form.message"
                                rows="6"
                                required
                                class="w-full resize-none rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none"
                                placeholder="Tell us what's on your mind..."
                            ></textarea>
                            <p v-if="form.errors.message" class="mt-1 text-[10px] font-bold text-rose-400">
                                {{ form.errors.message }}
                            </p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full cursor-pointer rounded-lg bg-[#ff8c00] py-3 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02] disabled:opacity-50"
                        >
                            {{ form.processing ? 'Sending...' : 'Send message' }}
                        </button>
                    </form>
                </div>

                <!-- Direct contacts -->
                <div class="space-y-3 lg:col-span-2">
                    <a
                        href="mailto:hello@betslip-pirates.com"
                        class="block rounded-lg border border-[#232d42] bg-[#161c2a] p-4 transition-colors hover:border-sky-500/40"
                    >
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            General
                        </span>
                        <span class="mt-1 block text-xs font-semibold text-slate-300 break-all">
                            hello@betslip-pirates.com
                        </span>
                    </a>
                    <a
                        href="mailto:support@betslip-pirates.com"
                        class="block rounded-lg border border-[#232d42] bg-[#161c2a] p-4 transition-colors hover:border-sky-500/40"
                    >
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Support
                        </span>
                        <span class="mt-1 block text-xs font-semibold text-slate-300 break-all">
                            support@betslip-pirates.com
                        </span>
                    </a>
                    <a
                        href="mailto:legal@betslip-pirates.com"
                        class="block rounded-lg border border-[#232d42] bg-[#161c2a] p-4 transition-colors hover:border-sky-500/40"
                    >
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Legal
                        </span>
                        <span class="mt-1 block text-xs font-semibold text-slate-300 break-all">
                            legal@betslip-pirates.com
                        </span>
                    </a>
                    <a
                        href="mailto:press@betslip-pirates.com"
                        class="block rounded-lg border border-[#232d42] bg-[#161c2a] p-4 transition-colors hover:border-sky-500/40"
                    >
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Press
                        </span>
                        <span class="mt-1 block text-xs font-semibold text-slate-300 break-all">
                            press@betslip-pirates.com
                        </span>
                    </a>

                    <div class="rounded-lg border border-[#232d42] bg-[#161c2a] p-4">
                        <span class="block text-[10px] font-black tracking-widest text-slate-500 uppercase">
                            Office
                        </span>
                        <span class="mt-1 block text-xs font-semibold text-slate-300">
                            Nairobi, Kenya
                        </span>
                        <span class="mt-1 block text-[10px] text-slate-500">
                            Monday – Friday, 9am – 6pm EAT
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>