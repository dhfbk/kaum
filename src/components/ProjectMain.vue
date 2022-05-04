<template>
    <p v-if="basicLoading">
        <LoadingSpinner/>
        Loading
    </p>
    <template v-else>
        <div class="row">
            <div class="col-md-9">
                <h1>{{ projectInfo.name }}</h1>
            </div>
            <div class="col-md-3 text-end" v-if="store.state.loggedAdmin">
                <button class="btn btn-warning btn-sm" @click="goBack()">
                    Back to project list
                </button>
            </div>
        </div>
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
                    <th scope="col">Name</th>
                    <th scope="col">E-mail</th>
                    <th scope="col">Status</th>
                    <th scope="col">Actions</th>
                </tr>
                </thead>
                <tbody>
                <tr v-for="e in projectInfo.educators" :key="updateTotal + '_' + e.id" class="align-middle">
                    <th scope="row">{{ e.id }}</th>
                    <td>{{ e.username }}</td>
                    <td><span class="dark-enable" :data-username="e.username" :id="e.username + '_change_name'"
                        data-title="Edit name">{{
                            e.name
                        }}
                    </span></td>
                    <td><span class="dark-enable" :data-username="e.username" :id="e.username + '_change_email'"
                        data-title="Edit e-mail address">{{
                            e.email
                        }}
                    </span></td>
                    <td v-if="e.disabled"><span class="badge bg-danger">Disabled</span></td>
                    <td v-else><span class="badge bg-success">Enabled</span></td>
                    <td>
                        <PicButton v-if="!e.disabled" @click="toggleUser(e.id)" text="Disable" color="warning"
                                   icon="x-circle" :disabled="userLoading.has(e.id)"/>
                        <PicButton v-else @click="toggleUser(e.id)" text="Enable" color="warning" icon="brightness-high"
                                   :disabled="userLoading.has(e.id)"/>
                    </td>
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
                <td v-if="e.closed"><span class="badge bg-dark">Closed</span></td>
                <td v-else-if="e.disabled"><span class="badge bg-danger">Disabled</span></td>
                <td v-else><span class="badge bg-success">Enabled</span></td>
                <td>
                    <PicButton @click="enterTask(e.id)" text="Manage" color="success" icon="box-arrow-in-right"/>
                    <PicButton @click="addTask(e.id)" text="Clone" color="secondary" icon="file-earmark-break"/>
                    <template v-if="!e.closed">
                        <PicButton v-if="!e.disabled" @click="toggleTask(e.id)" text="Disable" color="warning"
                                   icon="x-circle" :disabled="taskLoading.has(e.id)"/>
                        <PicButton v-else @click="toggleTask(e.id)" text="Enable" color="warning" icon="brightness-high"
                                   :disabled="taskLoading.has(e.id)"/>

                        <PicButton @click="getUsersPasswords(e.id)" text="User passwords" color="info" icon="key"/>
                        <PicButton v-if="e.disabled" :disabled="taskLoading.has(e.id)" @click="closeTask(e.id)"
                                   text="Close" color="dark" icon="door-closed-fill"/>
                    </template>
                </td>
            </tr>
            </tbody>
        </table>
    </template>
</template>

<script setup>
import {defineProps, ref, defineEmits, onMounted, inject, nextTick} from "vue";
import {useStore} from "vuex";

import PicButton from "@/components/objects/PicButton";
import {useRoute, useRouter} from "vue-router";
import LoadingSpinner from "@/components/objects/LoadingSpinner";
import DarkEditable from "@/dark-editable";

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
const userLoading = ref(new Set());

// Force reload of dark-editable
const updateTotal = ref(0);

const router = useRouter();
const route = useRoute();

function goBack() {
    router.push('/projects');
}

function getUsersPasswords(taskID) {
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
}

function closeTask(id) {
    taskAction(id, "closeTask");
}

function toggleTask(id) {
    taskAction(id, "taskToggleAvailability");
}

function toggleUser(id) {
    userLoading.value.add(id);
    axios.post("?", {"action": "userToggleAvailability", id: id, ...updateAxiosParams()})
        .then(() => {
            updateProject();
        })
        .catch((reason) => {
            let debugText = reason.response.statusText + " - " + reason.response.data.error;
            showModalWindow(debugText);
        })
        .then(() => {
            userLoading.value.delete(id);
        });
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

function addTask(cloneID) {
    emit("addTask", cloneID);
}

function updateProject() {
    updateTotal.value++;
    axios.get("?", {
        "params": {
            "action": "projectInfo", "id": id.value, ...updateAxiosParams()
        }
    })
        .then(async (response) => {
            basicLoading.value = false;
            projectInfo.value = response.data.info;
            await nextTick();

            let darkList = document.getElementsByClassName("dark-enable");
            // console.log(darkList);
            for (let d of darkList) {
                const params = {
                    "action": "task",
                    "sub": "changeUserName",
                    ...updateAxiosParams()
                };
                const u = new URLSearchParams(params).toString();
                new DarkEditable(d, {
                    type: 'text',
                    pk: d.dataset.username,
                    url: axios.defaults.baseURL + "?" + u,
                    ajaxOptions: {
                        method: "POST",
                        dataType: "json"
                    },
                    error: function (response) {
                        return response.json().then(function (data) {
                            showModalWindow(data.error);
                        });
                    },
                    title: d.dataset.title ? d.dataset.title : "Edit data"
                });
            }
        })
        .catch((reason) => {
            let debugText = reason;
            if (reason.response) {
                debugText = reason.response.statusText + " - " + reason.response.data.error;
            }
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