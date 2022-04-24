<template>
    <div class="row align-items-end">
        <div class="col-md-9">
            <h1>
                Project list
            </h1>
        </div>
        <div class="col-md-3 text-end">
            <button class="btn btn-primary btn-sm" @click="this.$router.push('/projects/new')">
                <i class="bi bi-file-earmark-plus"></i> Add project
            </button>
        </div>
    </div>
    <p v-if="projectList.length == 0">
        No projects yet
    </p>
    <table v-else class="table">
        <thead>
        <tr>
            <th scope="col">#</th>
            <th scope="col">Name</th>
            <th scope="col">Educators</th>
            <th scope="col">Status</th>
            <th scope="col">Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="p in projectList" :key="p.id" class="align-middle">
            <th scope="row">{{ p.id }}</th>
            <td>{{ p.name }}</td>
            <td>{{ p.data.educators }}</td>
            <td>
                <span class="badge bg-warning" v-if="!p.confirmed">Unconfirmed</span>
                <span class="badge bg-success" v-else-if="!p.disabled">Active</span>
                <span class="badge bg-danger" v-else>Disabled</span>
            </td>
            <td>
                <span v-if="!p.confirmed">
                    <PicButton @click="confirmProject(p.id)"
                               text="Confirm" color="info" icon="check-circle" :disabled="projectLoading.has(p.id)"/>
                    <PicButton @click="getPasswords(p.id)"
                               text="Download passwords" color="success" icon="key"/>
                </span>
                <span v-else>
                    <PicButton v-if="!p.disabled" @click="enterProject(p.id)"
                               text="Manage" color="success" icon="box-arrow-in-right"/>
                    <PicButton v-if="!p.disabled" @click="toggleAvailability(p.id)"
                               text="Disable" color="warning" icon="x-circle" :disabled="projectLoading.has(p.id)"/>
                    <PicButton v-else @click="toggleAvailability(p.id)"
                               text="Enable" color="warning" icon="brightness-high"
                               :disabled="projectLoading.has(p.id)"/>
                </span>
                <PicButton text="Delete" color="danger" icon="trash"/>
            </td>
        </tr>
        </tbody>
    </table>
</template>

<script setup>
import {inject, onMounted, ref} from 'vue'
import {useRouter} from 'vue-router'
import PicButton from "@/components/PicButton";

const router = useRouter();

// @ is an alias to /src
// import HelloWorld from '@/components/HelloWorld.vue'

const axios = inject('axios')
const updateAxiosParams = inject('updateAxiosParams');
const showModalWindow = inject('showModalWindow');

const projectList = ref([]);
const projectLoading = ref(new Set());

function getPasswords(id) {
    let params = {"action": "projectPasswords", "id": id, ...updateAxiosParams()};
    let usp = new URLSearchParams(params).toString();
    window.open(axios.defaults.baseURL + "?" + usp).focus();
}

function enterProject(id) {
    router.replace({path: "/project/" + id});
}

function confirmProject(id) {
    if (confirm("Are you sure? This action cannot be undone")) {
        projectAction(id, "projectConfirm");
    }
}

function toggleAvailability(id) {
    projectAction(id, "projectToggleAvailability");
}

function projectAction(id, action) {
    projectLoading.value.add(id);
    axios.post("?", {"action": action, id: id, ...updateAxiosParams()})
        .then(() => {
            updateProjects();
        })
        .catch((reason) => {
            let debugText = reason.response.statusText + " - " + reason.response.data.error;
            showModalWindow(debugText);
        })
        .then(() => {
            projectLoading.value.delete(id);
        });

}

function updateProjects() {
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
        .then(() => {
            // mainLoaded.value = true;
        });
}

onMounted(function () {
    updateProjects();
});

</script>
