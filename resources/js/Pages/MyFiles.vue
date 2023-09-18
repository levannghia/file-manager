<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        {{ $page.url }}
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
</script>
