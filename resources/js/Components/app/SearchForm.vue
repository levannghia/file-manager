<template>
    <div class="w-[600px] h-[80px] flex items-center">
        <TextInput type="text" class="block w-full mr-2" v-model="search" @keyup.enter.prevent="onSearch()" autocomplete placeholder="search for file and folder"></TextInput>
    </div>
</template>

<script setup>
import TextInput from "@/Components/TextInput.vue";
import { router, useForm } from "@inertiajs/vue3";
import { onMounted, ref } from "vue";

const search = ref('');
let params = null;

function onSearch(){
    params.set('search', search.value)
    router.get(window.location.pathname + '?' + params.toString())
}

onMounted(() => {
    params = new URLSearchParams(window.location.search)
    search.value = params.get('search')
})
</script>