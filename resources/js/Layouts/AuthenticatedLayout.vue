<template>
    <div class="h-screen bg-gray-50 flex w-full gap-4">
        <Navigation/>
        <main class="flex flex-col flex-1 px-4 overflow-hidden">
            <div class="flex items-center justify-between w-full">
                <SearchForm/>
                <UserSettingDropdown/>
            </div>
            <div class="flex-1 flex flex-col overflow-hidden">
                <slot></slot>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import Navigation from '@/Components/app/Navigation.vue';
import SearchForm from '@/Components/app/SearchForm.vue';
import UserSettingDropdown from '@/Components/app/UserSettingDropdown.vue';
</script>
