<template>
    <div class="row align-items-baseline">
        <div class="col-9">
            <h1 class="display-1">
                {{ $t("project.list").capitalize() }}
            </h1>
        </div>
        <div class="col-3 text-end">
            <PicButton :no-margin="true" :text="$t('project.new').capitalize()" color="primary"
                       icon="file-earmark-plus" @click="addProject()"></PicButton>
        </div>
    </div>
    <p v-if="basicLoading">
        <LoadingSpinner/>
        {{ $t("loading").capitalize() }}
    </p>
    <template v-else>
        <p v-if="projectList.length === 0">
            {{ $t("project.no").capitalize() }}
        </p>
        <div v-else class="table-responsive">
            <table class="table table-nowrap">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">{{ $t("name").capitalize() }}</th>
                    <th scope="col">{{ $t("educator.plur").capitalize() }}</th>
                    <th scope="col">{{ $t("language_short").capitalize() }}</th>
                    <th scope="col">{{ $t("status").capitalize() }}</th>
                    <th scope="col">{{ $t("action.plur").capitalize() }}</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="p in projectList" :key="p.id" class="align-middle">
                    <th scope="row">{{ p.id }}</th>
                    <td>{{ p.name }}</td>
                    <td>{{ p.data.educators }}</td>
                    <td>{{ p.data.language }}</td>
                    <td>
                        <project-badge :p="p"></project-badge>
                    </td>
                    <td>
                        <project-buttons :p="p" :inside="false" @update="updateProjects"></project-buttons>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </template>
</template>

<script setup>
import {inject, onMounted, ref} from 'vue'
import {useRouter} from 'vue-router'
import LoadingSpinner from "@/components/objects/LoadingSpinner";
import ProjectButtons from "@/components/objects/ProjectButtons";
import PicButton from "@/components/objects/PicButton";
import ProjectBadge from "@/components/objects/ProjectBadge";

const router = useRouter();

const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const showModalWindow = inject('showModalWindow');

const projectList = ref([]);
const basicLoading = ref(true);

function addProject() {
    router.push('/projects/new');
}

// function getPasswords(id) {
//     let params = {"action": "projectPasswords", "id": id, ...updateAxiosParams()};
//     let usp = new URLSearchParams(params).toString();
//     window.open(axios.defaults.baseURL + "?" + usp).focus();
// }
//
// function enterProject(id) {
//     router.push({path: "/project/" + id});
// }
//
// function confirmProject(id) {
//     if (confirm("Are you sure? This action cannot be undone")) {
//         projectAction(id, "projectConfirm");
//     }
// }
//
// function toggleAvailability(id) {
//     projectAction(id, "projectToggleAvailability");
// }
//
// function projectAction(id, action) {
//     projectLoading.value.add(id);
//     axios.post("?", {"action": action, id: id, ...updateAxiosParams()})
//         .then(() => {
//             updateProjects();
//         })
//         .catch((reason) => {
//             let debugText = reason.response.statusText + " - " + reason.response.data.error;
//             showModalWindow(debugText);
//         })
//         .then(() => {
//             projectLoading.value.delete(id);
//         });
//
// }

function updateProjects() {
    basicLoading.value = true;
    axios.get("?", {"params": {"action": "projectList", ...updateAxiosParams()}})
        .then((response) => {
            projectList.value = response.data.records;
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
        .finally(() => {
            basicLoading.value = false;
        });
}

onMounted(function () {
    updateProjects();
});

</script>
