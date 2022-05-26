<template>
    <h1 class="display-1">
        {{ title }}
    </h1>
    <ProjectForm :valuesProp="values" @submit="submit" @back="back" :buttonDisabled="buttonDisabled"/>
</template>

<script setup>
import {inject, onMounted, ref} from 'vue'
import ProjectForm from "@/components/ProjectForm.vue"
import {useStore} from 'vuex'
import {useRoute, useRouter} from 'vue-router';

const store = useStore();
const router = useRouter();
const route = useRoute();

const axios = inject('axios')
const updateAxiosParams = inject('updateAxiosParams');

const values = ref({
    educators: store.state.options.project_default_educators,
    language: store.state.options.project_default_language
    // students: store.state.options.project_default_students,
    // passwords: store.state.options.project_default_complexity
});
const title = ref("");

const showModalWindow = inject('showModalWindow');
const buttonDisabled = ref(false);

function back() {
    router.push("/projects");
}

function submit(v) {
    buttonDisabled.value = true;
    axios.post("?", {"action": "projectAdd", ...updateAxiosParams(), info: v})
        .then(() => {
            showModalWindow("Project created successfully");
            router.push("/projects");
        })
        .catch((reason) => {
            let debugText = reason.response.statusText + " - " + reason.response.data.error;
            showModalWindow(debugText);
            // store.commit('logout');
            // if (reason.response.status !== 401) {
            //   showModal.value = true;
            //   modalMessage.value = reason.response.statusText;
            // }
        })
        .then(() => {
            buttonDisabled.value = false;
            // mainLoaded.value = true;
        });
}

onMounted(function () {
    if (route.meta.action === "add") {
        title.value = "Insert new project";
    }
});
</script>
