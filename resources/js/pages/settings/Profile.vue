<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Profile settings', href: edit() }],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

// Local UI state
const previewUrl = ref<string | null>(null);
const removePhoto = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const currentAvatar = computed(() => {
    if (previewUrl.value) return previewUrl.value;
    if (removePhoto.value) return null;
    return user.value.profile_picture_url ?? null;
});

const initials = computed(() =>
    (user.value.name || '?')
        .split(' ')
        .map((w: string) => w[0])
        .join('')
        .slice(0, 2)
        .toUpperCase(),
);

const onFileChange = (event: Event) => {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) {
        previewUrl.value = null;
        return;
    }
    // Clear "remove" if user is uploading a new one
    removePhoto.value = false;
    previewUrl.value = URL.createObjectURL(file);
};

const triggerFilePicker = () => fileInput.value?.click();

const clearPhoto = () => {
    removePhoto.value = true;
    previewUrl.value = null;
    if (fileInput.value) fileInput.value.value = '';
};
</script>

<template>
    <Head title="Profile settings" />

    <h1 class="sr-only">Profile settings</h1>

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Profile"
            description="Update your photo, name, and email address"
        />

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <!-- ═══ AVATAR ═══ -->
            <div class="grid gap-3">
                <Label>Profile picture</Label>

                <div class="flex items-center gap-4">
                    <!-- Preview -->
                    <div
                        class="bg-muted relative h-20 w-20 flex-shrink-0 overflow-hidden rounded-full border"
                    >
                        <img
                            v-if="currentAvatar"
                            :src="currentAvatar"
                            alt="Avatar preview"
                            class="h-full w-full object-cover"
                        />
                        <div
                            v-else
                            class="text-muted-foreground flex h-full w-full items-center justify-center text-lg font-semibold"
                        >
                            {{ initials }}
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="flex flex-wrap items-center gap-2">
                        <input
                            ref="fileInput"
                            type="file"
                            name="photo"
                            accept="image/jpeg,image/png,image/webp"
                            class="hidden"
                            @change="onFileChange"
                        />

                        <Button
                            type="button"
                            variant="outline"
                            size="sm"
                            @click="triggerFilePicker"
                        >
                            {{
                                currentAvatar ? 'Change photo' : 'Upload photo'
                            }}
                        </Button>

                        <Button
                            v-if="currentAvatar"
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="clearPhoto"
                        >
                            Remove
                        </Button>
                    </div>
                </div>

                <p class="text-muted-foreground text-xs">
                    JPG, PNG, or WebP — max 2&nbsp;MB.
                </p>

                <!-- Hidden field submitted when the user clicks Remove -->
                <input
                    type="hidden"
                    name="remove_photo"
                    :value="removePhoto ? '1' : '0'"
                />

                <InputError :message="errors.photo" />
                <InputError :message="errors.remove_photo" />
            </div>

            <!-- ═══ NAME ═══ -->
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Full name"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <!-- ═══ EMAIL ═══ -->
            <div class="grid gap-2">
                <Label for="email">Email address</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Email address"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div v-if="page.props.mustVerifyEmail && !user.email_verified_at">
                <p class="text-muted-foreground -mt-4 text-sm">
                    Your email address is unverified.
                    <Link
                        :href="send()"
                        as="button"
                        class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                    >
                        Click here to re-send the verification email.
                    </Link>
                </p>

                <div
                    v-if="page.props.status === 'verification-link-sent'"
                    class="mt-2 text-sm font-medium text-green-600"
                >
                    A new verification link has been sent to your email address.
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button
                    :disabled="processing"
                    data-test="update-profile-button"
                >
                    Save
                </Button>
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
