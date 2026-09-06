<script setup lang="ts">
import { computed } from 'vue';
import { SidebarInset } from '@/components/ui/sidebar';
import type { AppVariant } from '@/types';

type Props = {
    variant?: AppVariant;
    class?: string;
};

const props = withDefaults(defineProps<Props>(), {
    variant: 'sidebar',
});
const className = computed(() => props.class);
</script>

<template>
    <SidebarInset v-if="props.variant === 'sidebar'" :class="className">
        <slot />
    </SidebarInset>
    <main
        v-else
        class="mx-auto bg-red-500 flex h-full w-full max-w-7xl flex-1 flex-col gap-4 rounded-xl"
        :class="className"
    >
    Lorem ipsum dolor sit amet consectetur adipisicing elit. Maiores optio ipsum et eaque magni possimus impedit nisi repellat quaerat itaque, earum atque fugit eum illo excepturi vel voluptates nobis! Adipisci pariatur distinctio ipsum doloribus voluptate. Velit, ipsam. Iure voluptatem dolore illo qui nostrum porro, maiores, libero repellat minima officia pariatur?
        <slot />
    </main>
</template>
