<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage();
const isLoggedIn = computed(() => !!page.props.auth?.user);

const reportTypes = [
    {
        value: 'bug',
        label: 'Bug or technical issue',
        description: 'Something is broken, slow, or behaving incorrectly.',
        icon: '🐛',
    },
    {
        value: 'seller',
        label: 'Seller misconduct',
        description: 'A seller is manipulating results, using fake accounts, or misleading buyers.',
        icon: '⚠️',
    },
    {
        value: 'buyer',
        label: 'Buyer misconduct',
        description: 'A buyer is abusing the refund system, harassing sellers, or violating rules.',
        icon: '🚫',
    },
    {
        value: 'payment',
        label: 'Payment problem',
        description: 'A deposit, withdrawal, or refund did not arrive or behaved incorrectly.',
        icon: '💳',
    },
    {
        value: 'security',
        label: 'Security or account',
        description: 'Suspicious login, unauthorized access, or account compromise.',
        icon: '🔒',
    },
    {
        value: 'other',
        label: 'Something else',
        description: 'Anything that doesn\'t fit the categories above.',
        icon: '📝',
    },
];

const severityOptions = [
    { value: 'low', label: 'Low — minor, no rush' },
    { value: 'medium', label: 'Medium — affecting my use' },
    { value: 'high', label: 'High — blocking or costly' },
    { value: 'critical', label: 'Critical — money or security at risk' },
];

const form = useForm({
    type: 'bug',
    severity: 'medium',
    subject: '',
    description: '',
    betslip_code: '',
    contact_email: isLoggedIn.value ? page.props.auth.user.email : '',
    screenshot: null,
});

const selectedType = computed(
    () => reportTypes.find((t) => t.value === form.type) || reportTypes[0],
);

const fileName = ref<string | null>(null);

const onFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    form.screenshot = file;
    fileName.value = file ? file.name : null;
};

const submit = () => {
    form.post('/report', {
        preserveScroll: true,
        forceFormData: true, // needed for file upload
        onSuccess: () => {
            form.reset('subject', 'description', 'betslip_code', 'screenshot');
            fileName.value = null;
        },
    });
};
</script>

<template>
    <Head title="Report a Problem" />

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
                    Report a Problem
                </h1>
                <p class="mt-4 max-w-2xl text-sm leading-relaxed text-slate-400">
                    Use this form to report bugs, misconduct, payment issues,
                    or anything else that needs our attention. Reports
                    involving money or security are prioritized.
                </p>
            </div>

            <!-- Success state -->
            <div
                v-if="form.recentlySuccessful"
                class="mb-8 rounded-lg border border-emerald-500/30 bg-emerald-500/5 p-5"
            >
                <div class="flex items-start gap-3">
                    <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full border border-emerald-500/40 bg-emerald-500/10">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="h-4 w-4 text-emerald-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-black tracking-widest text-emerald-400 uppercase">
                            Report received
                        </h3>
                        <p class="mt-1 text-sm leading-relaxed text-slate-400">
                            Thank you. We've logged your report and will
                            investigate. If you included an email address,
                            expect a reply within 24 hours. Reference IDs for
                            money or security issues are handled within 4 hours.
                        </p>
                    </div>
                </div>
            </div>

            <form
                v-else
                @submit.prevent="submit"
                class="space-y-6 rounded-lg border border-[#232d42] bg-[#161c2a] p-6"
            >
                <!-- Report type -->
                <div>
                    <label class="mb-3 block text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        What are you reporting?
                    </label>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <button
                            v-for="type in reportTypes"
                            :key="type.value"
                            type="button"
                            @click="form.type = type.value"
                            :class="[
                                'rounded-lg border p-3 text-left transition-colors',
                                form.type === type.value
                                    ? 'border-sky-500/60 bg-sky-500/10'
                                    : 'border-[#232d42] bg-[#0f1422] hover:border-[#2b3a54]',
                            ]"
                        >
                            <div class="flex items-center gap-2">
                                <span class="text-base">{{ type.icon }}</span>
                                <span
                                    :class="[
                                        'text-xs font-bold',
                                        form.type === type.value
                                            ? 'text-sky-400'
                                            : 'text-slate-200',
                                    ]"
                                >
                                    {{ type.label }}
                                </span>
                            </div>
                            <p class="mt-1.5 text-[10px] leading-relaxed text-slate-500">
                                {{ type.description }}
                            </p>
                        </button>
                    </div>
                </div>

                <!-- Severity -->
                <div>
                    <label class="mb-3 block text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        How urgent is this?
                    </label>
                    <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
                        <button
                            v-for="s in severityOptions"
                            :key="s.value"
                            type="button"
                            @click="form.severity = s.value"
                            :class="[
                                'rounded-lg border px-4 py-2.5 text-left text-xs font-bold transition-colors',
                                form.severity === s.value
                                    ? 'border-sky-500/60 bg-sky-500/10 text-sky-400'
                                    : 'border-[#232d42] bg-[#0f1422] text-slate-300 hover:border-[#2b3a54]',
                            ]"
                        >
                            {{ s.label }}
                        </button>
                    </div>
                </div>

                <!-- Subject -->
                <div>
                    <label class="mb-1.5 block text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        Subject
                    </label>
                    <input
                        v-model="form.subject"
                        type="text"
                        required
                        :placeholder="`Short summary of the ${selectedType.label.toLowerCase()}`"
                        class="w-full rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none"
                    />
                    <p v-if="form.errors.subject" class="mt-1 text-[10px] font-bold text-rose-400">
                        {{ form.errors.subject }}
                    </p>
                </div>

                <!-- Betslip code (optional) -->
                <div>
                    <label class="mb-1.5 block text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        Related betslip code <span class="font-normal text-slate-600 normal-case">(optional)</span>
                    </label>
                    <input
                        v-model="form.betslip_code"
                        type="text"
                        placeholder="e.g. EUZ-KGIG-BW4"
                        class="w-full rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2.5 font-mono text-sm tracking-wider text-white uppercase placeholder-slate-600 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none"
                    />
                    <p class="mt-1 text-[10px] text-slate-500">
                        If your report is about a specific betslip, include its code so we can look up the transaction instantly.
                    </p>
                </div>

                <!-- Description -->
                <div>
                    <label class="mb-1.5 block text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        What happened?
                    </label>
                    <textarea
                        v-model="form.description"
                        rows="7"
                        required
                        placeholder="Describe the issue. Include what you expected, what actually happened, and any steps that led up to it."
                        class="w-full resize-none rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none"
                    ></textarea>
                    <p v-if="form.errors.description" class="mt-1 text-[10px] font-bold text-rose-400">
                        {{ form.errors.description }}
                    </p>
                </div>

                <!-- Screenshot upload -->
                <div>
                    <label class="mb-1.5 block text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        Screenshot <span class="font-normal text-slate-600 normal-case">(optional, max 5MB)</span>
                    </label>
                    <label
                        class="flex cursor-pointer items-center justify-between rounded-lg border border-dashed border-[#232d42] bg-[#0f1422] px-4 py-3 transition-colors hover:border-sky-500/40"
                    >
                        <span class="text-xs text-slate-400">
                            {{ fileName || 'Choose a file (PNG, JPG, or PDF)' }}
                        </span>
                        <span class="rounded border border-[#232d42] bg-[#161c2a] px-3 py-1 text-[10px] font-black tracking-widest text-slate-400 uppercase">
                            Browse
                        </span>
                        <input
                            type="file"
                            accept="image/png,image/jpeg,application/pdf"
                            class="hidden"
                            @change="onFileChange"
                        />
                    </label>
                    <p v-if="form.errors.screenshot" class="mt-1 text-[10px] font-bold text-rose-400">
                        {{ form.errors.screenshot }}
                    </p>
                </div>

                <!-- Contact email -->
                <div>
                    <label class="mb-1.5 block text-[10px] font-black tracking-widest text-slate-400 uppercase">
                        Reply-to email
                    </label>
                    <input
                        v-model="form.contact_email"
                        type="email"
                        required
                        placeholder="you@example.com"
                        class="w-full rounded-lg border border-[#232d42] bg-[#0f1422] px-4 py-2.5 text-sm text-white placeholder-slate-600 focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 focus:outline-none"
                    />
                    <p v-if="form.errors.contact_email" class="mt-1 text-[10px] font-bold text-rose-400">
                        {{ form.errors.contact_email }}
                    </p>
                </div>

                <!-- Submit -->
                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full cursor-pointer rounded-lg bg-[#ff8c00] py-3 text-xs font-black tracking-widest text-black uppercase transition-transform hover:scale-[1.02] disabled:opacity-50"
                >
                    {{ form.processing ? 'Submitting report…' : 'Submit report' }}
                </button>

                <p class="text-center text-[10px] leading-relaxed text-slate-500">
                    Reports involving money, security, or fraud are treated as
                    priority and investigated within 4 hours.
                </p>
            </form>

            <!-- Emergency guidance -->
            <section class="mt-8 rounded-lg border border-rose-500/20 bg-rose-500/5 p-5">
                <h2 class="text-xs font-black tracking-widest text-rose-400 uppercase">
                    Urgent security concerns
                </h2>
                <p class="mt-2 text-xs leading-relaxed text-slate-400">
                    If you believe your account has been compromised and money
                    is at immediate risk, email
                    <a href="mailto:security@betslip-pirates.com" class="text-rose-300 underline decoration-dotted hover:text-rose-200">
                        security@betslip-pirates.com
                    </a>
                    directly, or use the form above with severity set to
                    "Critical". We monitor security reports 24/7.
                </p>
            </section>
        </div>
    </div>
</template>