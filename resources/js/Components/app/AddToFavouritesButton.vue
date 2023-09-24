<template>
    <button @click="onClick"
            class="inline-flex items-center px-4 py-2 mr-3 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 hover:text-blue-700 focus:z-10 focus:ring-2 focus:ring-blue-700 focus:text-blue-700 dark:bg-gray-700 dark:border-gray-600 dark:text-white dark:hover:text-white dark:hover:bg-gray-600 dark:focus:ring-blue-500 dark:focus:text-white">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"
             class="w-4 h-4 mr-2">
            <path stroke-linecap="round" stroke-linejoin="round"
                  d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/>
        </svg>

        Add to favourites
    </button>
</template>

<script setup>
// Imports
import {ref} from "vue";
import {useForm, usePage} from "@inertiajs/vue3";
import {showErrorDialog, showSuccessNotification} from "@/event-bus.js";

// Uses
const page = usePage();
const form = useForm({
    all: null,
    ids: [],
    parent_id: null
})


// Refs

// Props & Emit

const props = defineProps({
    allSelected: {
        type: Boolean,
        required: false,
        default: false
    },
    selectedIds: {
        type: Array,
        required: false
    }
})

// Computed

// Methods

function onClick() {
    if (!props.allSelected && !props.selectedIds.length) {
        showErrorDialog('Please select at least one file to restore')
        return
    }

    form.parent_id = page.props.folder.id;

    if(props.allSelected){
        form.all = true;
    }else{
        form.ids = props.selectedIds
    }

    form.post(route('file.addToFavourites'), {
        onSuccess: () => {
            form.ids = [];
            showSuccessNotification('Selected file have been added to favourites')
        }
    })
}

// Hooks

</script>

<style scoped>

</style>
