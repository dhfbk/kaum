<template>
    <PicButton :always-text="inside" v-if="!inside && e.confirmed" @click="enterTask(e.id)"
               :text="$t('action.manage').capitalize()"
               color="success" icon="box-arrow-in-right"/>
    <PicButton :always-text="inside" @click="downloadData(e.id)" text="Export data" color="success" icon="filetype-xls"/>
    <PicButton :always-text="inside" v-if="!e.confirmed" @click="confirmTask(e.id)"
               :text="$t('action.confirm').capitalize()" color="info" icon="check-circle"
               :disabled="loading"/>

    <PicButton :always-text="inside" v-if="!e.confirmed" @click="editTask(e.id)" :text="$t('action.edit').capitalize()"
               color="warning"
               icon="pencil" :disabled="loading"/>

    <PicButton :always-text="inside" @click="cloneTask(e.id)" :text="$t('action.clone').capitalize()" color="yellow"
               icon="file-earmark-break"/>
    <template v-if="!e.closed && e.confirmed">
        <PicButton :always-text="inside" v-if="!e.disabled" @click="toggleTask(e.id)"
                   :text="$t('action.disable').capitalize()" color="warning"
                   icon="x-circle" :disabled="loading"/>
        <PicButton :always-text="inside" v-else @click="toggleTask(e.id)" :text="$t('action.enable').capitalize()"
                   color="warning"
                   icon="brightness-high"
                   :disabled="loading"/>

        <div class="btn-group dropdown position-static">
            <button type="button" class="btn btn-sm btn-info me-3 dropdown-toggle"
                    data-bs-toggle="dropdown" role="button"
                    :title="$t('action.user_passwords').capitalize()" :disabled="false">
                <i class="bi" :class="'bi-key'"></i>
                <span class="ms-2 d-none me-1" :class="[inside ? 'd-sm-inline' : 'd-lg-inline']">{{
                        $t('action.user_passwords').capitalize()
                    }}</span>
            </button>
            <div class="dropdown-menu position-absolute">
                <a class="dropdown-item" @click="getUsersPasswords(e.id, 'taskPasswords')">List</a>
                <a class="dropdown-item" @click="getUsersPasswords(e.id, 'taskPasswordsNotes')">Notes</a>
            </div>
        </div>

        <PicButton :always-text="inside" v-if="e.disabled" :disabled="loading" @click="closeTask(e.id)"
                   :text="$t('action.close').capitalize()" color="dark" icon="door-closed-fill"/>
    </template>
    <PicButton :always-text="inside" v-if="!inside && (e.disabled || e.closed || !e.confirmed)"
               :text="$t('action.delete').capitalize()"
               @click="deleteTask(e.id)" color="danger" icon="trash"/>

</template>

<script setup>
import {defineProps, ref, defineEmits, inject} from "vue";
import PicButton from "@/components/objects/PicButton";
import {useRoute, useRouter} from "vue-router";
import {useI18n} from "vue-i18n";

const loading = ref(false);
const showModalWindow = inject('showModalWindow');
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');

const router = useRouter();
const route = useRoute();
const {t} = useI18n();

const props = defineProps({
    id: String,
    e: Object,
    inside: Boolean
});

const emit = defineEmits(['cloneTask', 'update', 'edit']);

function downloadData(id) {
    let params = {
        "action": "task",
        "type": props.e.tool,
        "sub": "exportResults",
        "project_id": props.id,
        "id": id,
        ...updateAxiosParams()
    };
    let usp = new URLSearchParams(params).toString();
    window.open(axios.defaults.baseURL + "?" + usp).focus();
}

function cloneTask(cloneID) {
    emit('cloneTask', cloneID);
}

function editTask(cloneID) {
    emit('edit', cloneID);
}

function getUsersPasswords(taskID, action) {
    let params = {
        "action": action,
        "project_id": props.id,
        "id": taskID,
        ...updateAxiosParams()
    };
    let usp = new URLSearchParams(params).toString();
    window.open(axios.defaults.baseURL + "?" + usp).focus();
}

function enterTask(taskID) {
    router.push('/project/' + route.params.id + '/' + taskID)
}

function deleteTask(id) {
    if (confirm("Are you sure? This action cannot be undone")) {
        taskAction(id, "taskDelete");
    }
}

function closeTask(id) {
    taskAction(id, "closeTask");
}

function toggleTask(id) {
    taskAction(id, "taskToggleAvailability");
}

function confirmTask(id) {
    if (confirm(t("task.confirm_edit"))) {
        taskAction(id, "confirmTask");
    }
}

function taskAction(id, action) {
    loading.value = true;
    axios.post("?", {"action": action, id: id, ...updateAxiosParams()})
        .then(() => {
            emit('update');
            // updateProject();
        })
        .catch((reason) => {
            let debugText = reason.response.statusText + " - " + reason.response.data.error;
            showModalWindow(debugText);
        })
        .then(() => {
            loading.value = false;
        });
}

</script>

<style scoped>

</style>