<template>
    <p v-if="basicLoading">Loading</p>
    <template v-else>
        <h1>{{ projectInfo.name }}</h1>
        <template v-if="store.state.loggedAdmin">
            <div class="row mt-5">
                <div class="col-md-9">
                    <h2>
                        Educators
                    </h2>
                </div>
                <div class="col-md-3 text-end">
                    <!--       <button class="btn btn-primary btn-sm" @click="this.$router.push('/projects/new')">
                            <i class="bi bi-file-earmark-plus"></i> Add educator
                          </button> -->
                </div>
            </div>
            <p v-if="projectInfo.educators.length == 0">
                No educators
            </p>
            <table v-else class="table">
                <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Username</th>
                    <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="e in projectInfo.educators" :key="e.id" class="align-middle">
                    <th scope="row">{{ e.id }}</th>
                    <td>{{ e.username }}</td>
                    <td>Actions</td>
                </tr>
                </tbody>
            </table>
        </template>

        <div class="row mt-5">
            <div class="col-md-9">
                <h2>
                    Tasks
                </h2>
            </div>
            <div class="col-md-3 text-end">
                <button class="btn btn-primary btn-sm" @click="addTask()">
                    <i class="bi bi-file-earmark-plus"></i> Add task
                </button>
            </div>
        </div>
        <p v-if="projectInfo.tasks.length === 0">
            No tasks yet
        </p>
        <table v-else class="table">
            <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Type</th>
                <th scope="col">Label</th>
                <th scope="col">Students</th>
                <th scope="col">Status</th>
                <th scope="col">Actions</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="e in projectInfo.tasks" :key="e.id" class="align-middle">
                <th scope="row">{{ e.id }}</th>
                <td>{{ e.tool }}</td>
                <td>{{ e.name }}</td>
                <td>{{ e.students }}</td>
                <td>{{ e.disabled ? "Disabled" : "Enabled" }}</td>
                <td>
                    <PicButton v-if="!e.disabled" @click="toggleTask(e.id)" text="Disable" color="warning"
                               icon="x-circle" :disabled="taskLoading.has(e.id)"/>
                    <PicButton v-else @click="toggleTask(e.id)" text="Enable" color="warning" icon="brightness-high"
                               :disabled="taskLoading.has(e.id)"/>

                    <PicButton @click="enterTask(e.id)" text="Manage" color="success" icon="box-arrow-in-right"/>
                    <PicButton @click="getUsersPasswords(e.id)" text="User passwords" color="info" icon="key"/>
                </td>
            </tr>
            </tbody>
        </table>
    </template>
</template>

<script setup>
import {defineProps, ref, defineEmits, onMounted, inject} from "vue";
import {useStore} from "vuex";

import PicButton from "@/components/PicButton";
import {useRoute, useRouter} from "vue-router";

const showModalWindow = inject('showModalWindow');
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');

const store = useStore();

const emit = defineEmits(['addTask']);
const props = defineProps({
    id: {
        type: String
    }
});
const id = ref(props.id);

const basicLoading = ref(true);
const projectInfo = ref({});
const taskLoading = ref(new Set());

const router = useRouter();
const route = useRoute();

function getUsersPasswords(taskID) {
    console.log("Passwords: " + taskID);
    let params = {
        "action": "taskPasswords",
        "project_id": id.value,
        "id": taskID,
        ...updateAxiosParams()
    };
    let usp = new URLSearchParams(params).toString();
    window.open(axios.defaults.baseURL + "?" + usp).focus();
}

function enterTask(taskID) {
    router.push('/project/' + route.params.id + '/' + taskID)
    console.log(taskID);
}

// function toggleTask(taskID) {
//     console.log("Toggle: " + taskID);
// }

function toggleTask(id) {
    taskAction(id, "taskToggleAvailability");
}

function taskAction(id, action) {
    taskLoading.value.add(id);
    axios.post("?", {"action": action, id: id, ...updateAxiosParams()})
        .then(() => {
            updateProject();
        })
        .catch((reason) => {
            let debugText = reason.response.statusText + " - " + reason.response.data.error;
            showModalWindow(debugText);
        })
        .then(() => {
            taskLoading.value.delete(id);
        });

}
function addTask() {
    emit("addTask");
}

function updateProject() {
    axios.get("?", {
        "params": {
            "action": "projectInfo", "id": id.value, ...updateAxiosParams()
        }
    })
        .then((response) => {
            basicLoading.value = false;
            projectInfo.value = response.data.info;
        })
        .catch((reason) => {
            let debugText = reason.response.statusText + " - " + reason.response.data.error;
            showModalWindow(debugText);
        })
        .then(() => {
            // mainLoaded.value = true;
        });
}

onMounted(function () {
    updateProject();
})
</script>

<style scoped>

</style>