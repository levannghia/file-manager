<template>
    <nav class="min-w-[200px]">
        <div class="h-[80px] px-3 flex items-center gap-3">
            <Link href="/">
                <ApplicationLogo class="block h-9 w-auto fill-current text-gray-800"></ApplicationLogo>
            </Link>
            LaraStore
        </div>
        <div class="px-3">
            <CreateNewDropdown/>
            <div class="py-4">
                <NavLink href="/" :active="true">My File</NavLink>
                <NavLink href="/">Shared with me</NavLink>
                <NavLink href="/">Share by me</NavLink>
                <NavLink href="/">Trash</NavLink>
            </div>
        </div>
    </nav>
</template>

<script setup>
import { Link } from '@inertiajs/vue3';
import ApplicationLogo from '@/components/ApplicationLogo.vue';
import CreateNewDropdown from '@/components/app/CreateNewDropdown.vue';
import NavLink from '@/components/NavLink.vue';
</script>