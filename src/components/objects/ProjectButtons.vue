<template>
    <PicButton :always-text="inside" v-if="!p.disabled && !inside" @click="enterProject(p.id)"
               :text="$t('action.manage').capitalize()" color="success" icon="box-arrow-in-right"/>
    <span v-if="!p.confirmed">
        <PicButton :always-text="inside" @click="confirmProject(p.id)"
                   :text="$t('action.confirm').capitalize()" color="info" icon="check-circle"
                   :disabled="loading"/>
        <PicButton :always-text="inside" @click="getPasswords(p.id)"
                   :text="$t('action.edu_passwords').capitalize()" color="success" icon="key"/>
    </span>
    <span v-else>
        <PicButton :always-text="inside" v-if="!p.disabled" @click="toggleAvailability(p.id)"
                   :text="$t('action.disable').capitalize()" color="warning" icon="x-circle"
                   :disabled="loading"/>
        <PicButton :always-text="inside" v-else @click="toggleAvailability(p.id)"
                   :text="$t('action.enable').capitalize()" color="warning" icon="brightness-high"
                   :disabled="loading"/>
    </span>
    <PicButton :always-text="inside" v-if="!inside && p.disabled" :text="$t('action.delete').capitalize()"
               @click="deleteProject(p.id)" color="danger" icon="trash"/>

</template>

<script setup>
import PicButton from "@/components/objects/PicButton";
import {defineProps, ref, inject, defineEmits} from "vue";
import {useRouter} from "vue-router";

const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const showModalWindow = inject('showModalWindow');

const router = useRouter();
const emit = defineEmits(['update']);
defineProps({
    p: Object,
    inside: Boolean
});

const loading = ref(false);

function getPasswords(id) {
    let params = {"action": "projectPasswords", "id": id, ...updateAxiosParams()};
    let usp = new URLSearchParams(params).toString();
    window.open(axios.defaults.baseURL + "?" + usp).focus();
}

function enterProject(id) {
    router.push({path: "/project/" + id});
}

function confirmProject(id) {
    if (confirm("Are you sure? This action cannot be undone")) {
        projectAction(id, "projectConfirm");
    }
}

function deleteProject(id) {
    if (confirm("Are you sure? This action cannot be undone")) {
        projectAction(id, "projectDelete");
    }
}

function toggleAvailability(id) {
    projectAction(id, "projectToggleAvailability");
}

function projectAction(id, action) {
    loading.value = true;
    axios.post("?", {"action": action, id: id, ...updateAxiosParams()})
        .then(() => {
            emit('update');
            // updateProjects();
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