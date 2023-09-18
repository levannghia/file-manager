<template>
    <Head title="My Files" />

    <AuthenticatedLayout>
        {{ $page.url }}
    </AuthenticatedLayout>
</template>

<script setup>
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
</script>
