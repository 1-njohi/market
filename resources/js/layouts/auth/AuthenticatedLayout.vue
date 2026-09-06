<script setup>
import { ref } from 'vue';
// import ApplicationLogo from "@/Components/ApplicationLogo.vue";
import Dropdown from '@/components/Dropdown.vue';
import DropdownLink from '@/components/DropdownLink.vue';
import NavLink from '@/components/NavLink.vue';
import ResponsiveNavLink from '@/components/ResponsiveNavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingNavigationDropdown = ref(false);
</script>

<template>
    <div>
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            <!-- Page Heading -->
            
            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
