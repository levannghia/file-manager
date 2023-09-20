<template>
    <div class="h-screen bg-gray-50 flex w-full gap-4">
        <Navigation />
        <main @drop.prevent="handleDrop" @dragover.prevent="onDragOver" @dragleave.prevent="onDragLeave"
            class="flex flex-col flex-1 px-4 overflow-hidden" :class="dragOver ? 'dropzone' : ''">
            <template v-if="dragOver">
                <div class="text-gray-500 text-center py-8 text-sm">Drop files here to upload</div>
            </template>
            <template v-else>
                <div class="flex items-center justify-between w-full">
                    <SearchForm />
                    <UserSettingsDropdown />
                </div>
                <div class="flex-1 flex flex-col overflow-hidden">
                    <slot />
                </div>
            </template>
        </main>
    </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { Link, useForm, usePage } from '@inertiajs/vue3';
import Navigation from '@/Components/app/Navigation.vue';
import SearchForm from '@/Components/app/SearchForm.vue';
import UserSettingDropdown from '@/Components/app/UserSettingDropdown.vue';
import { emitter, FILE_UPLOAD_STARTED } from '@/event-bus.js';

const page = usePage()
const fileUploadForm = useForm({
    files: [],
    relative_paths: [],
    parent_id: null
})
const dragOver = ref(false)

function onDragOver() {
    dragOver.value = true
}

function onDragLeave() {
    dragOver.value = false
}

function handleDrop(ev) {
    dragOver.value = false;
    const files = ev.dataTransfer.files
    console.log(files);
    if (!files.length) {
        return
    }

    uploadFiles(files)
}

function uploadFiles(files) {
    console.log("files", files);
    fileUploadForm.parent_id = page.props.folder.id
    fileUploadForm.relative_paths = [...files].map(f => f.webkitRelativePath)
    fileUploadForm.files = files

    fileUploadForm.post(route('file.store'));
}

onMounted(() => {
    emitter.on(FILE_UPLOAD_STARTED, uploadFiles)
})
</script>
<style scoped>
    .dropzone {
        width: 100%;
        height: 100%;
        color: #8d8d8d;
        border: 2px dashed gray;
        display: flex;
        justify-content: center;
        align-items: center;
    }
</style>
