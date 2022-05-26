<template>
    <p v-if="basicLoading">
        <LoadingSpinner/>
        Loading
    </p>
    <template v-else>
        <div class="row align-items-baseline">
            <div class="col">
                <h1 class="display-1">{{ projectInfo.name }}</h1>
            </div>
        </div>
        <div class="row align-items-baseline">
            <div class="col" v-if="store.state.loggedAdmin">
                <p>
                    {{ $t("project.status").capitalize() }}:
                    <project-badge :p="projectInfo"></project-badge>
                </p>
                <project-buttons :p="projectInfo" :inside="true"
                                 @update="updateProject"></project-buttons>
                <PicButton :always-text="true" @click="goBack"
                           :text="$t('project.back').capitalize()" color="warning" icon="arrow-90deg-up"/>

            </div>
        </div>
        <template v-if="store.state.loggedAdmin">
            <div class="row mt-5 align-items-baseline">
                <div class="col-9">
                    <h2 class="display-2">
                        {{ $t("educator.plur").capitalize() }}
                    </h2>
                </div>
                <div class="col-3 text-end" v-if="store.state.loggedAdmin">
                    <PicButton :no-margin="true" :text="$t('educator.new').capitalize()" color="primary"
                               icon="file-earmark-plus" @click="addEducator"></PicButton>
                </div>
            </div>
            <p v-if="projectInfo.educators.length == 0">
                {{ $t('educator.no').capitalize() }}
            </p>
            <div class="table-responsive" v-else>
                <table class="table table-nowrap">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Username</th>
                        <th scope="col">{{ $t('name').capitalize() }}</th>
                        <th scope="col">E-mail</th>
                        <th scope="col">{{ $t('status').capitalize() }}</th>
                        <th scope="col">{{ $t('action.plur').capitalize() }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="e in projectInfo.educators" :key="updateTotal + '_' + e.id" class="align-middle">
                        <th scope="row">{{ e.id }}</th>
                        <td>{{ e.username }}</td>
                        <td>
                            <span class="dark-enable" :data-username="e.username" :id="e.username + '_change_name'"
                                  data-title="Edit name">{{ e.name }}</span>
                        </td>
                        <td>
                            <span class="dark-enable" :data-username="e.username" :id="e.username + '_change_email'"
                                  data-title="Edit e-mail address">{{ e.email }}</span>
                        </td>
                        <td>
                            <span v-if="e.disabled"
                                  class="badge bg-danger">{{ $t("user.disabled").capitalize() }}</span>
                            <span v-else class="badge bg-success">{{ $t("user.active").capitalize() }}</span>
                        </td>
                        <td>
                            <PicButton v-if="!e.disabled" @click="toggleUser(e.id)"
                                       :text="$t('action.disable').capitalize()" color="warning"
                                       icon="x-circle" :disabled="userLoading.has(e.id)"/>
                            <PicButton v-else @click="toggleUser(e.id)" :text="$t('action.enable').capitalize()"
                                       color="warning"
                                       icon="brightness-high"
                                       :disabled="userLoading.has(e.id)"/>
                            <PicButton @click="resetEducatorPassword(e.id, e.username)"
                                       :text="$t('action.reset_password').capitalize()"
                                       color="dark" icon="key"/>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </template>

        <div class="row mt-5 align-items-baseline">
            <div class="col-9">
                <h2 class="display-2">
                    {{ $t("task.plur").capitalize() }}
                </h2>
            </div>
            <div class="col-3 text-end" v-if="projectInfo.confirmed">
                <PicButton :no-margin="true" :text="$t('task.new').capitalize()" color="primary"
                           icon="file-earmark-plus" @click="addTask()"></PicButton>
            </div>
        </div>
        <template v-if="projectInfo.confirmed">
            <p v-if="projectInfo.tasks.length === 0">
                {{ $t("task.no") }}
            </p>
            <div v-else class="table-responsive">
                <table class="table table-nowrap">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">{{ $t("tool").capitalize() }}</th>
                        <th scope="col">{{ $t("name").capitalize() }}</th>
                        <th scope="col">{{ $t("student.plur").capitalize() }}</th>
                        <th scope="col">{{ $t("status").capitalize() }}</th>
                        <th scope="col">{{ $t("action.plur").capitalize() }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="e in projectInfo.tasks" :key="e.id" class="align-middle">
                        <th scope="row">{{ e.id }}</th>
                        <td>
                            <img :src="`${publicPath}/img/tasks/${e.tool}.png`" :alt="e.tool" :title="e.tool"
                                 height="20"/>
                            <span class="ms-2 badge bg-primary">{{ e.tool }}</span>
                        </td>
                        <td>{{ e.name }}</td>
                        <td>{{ e.students }}</td>
                        <td>
                            <task-badge :e="e"></task-badge>
                        </td>
                        <td>
                            <task-buttons @update="updateProject" @clone-task="addTask" @edit="editTask"
                                          :id="id" :e="e" :inside="false"></task-buttons>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </template>
        <div v-else>
            You need to confirm the project to manage the tasks.
        </div>
    </template>
</template>

<script setup>
import {defineProps, ref, defineEmits, onMounted, inject, nextTick} from "vue";
import {useStore} from "vuex";
import {useRouter} from "vue-router";

import PicButton from "@/components/objects/PicButton";
import ProjectButtons from "@/components/objects/ProjectButtons";
import LoadingSpinner from "@/components/objects/LoadingSpinner";
import DarkEditable from "@/dark-editable";
import TaskButtons from "@/components/objects/TaskButtons";
import TaskBadge from "@/components/objects/TaskBadge";
import ProjectBadge from "@/components/objects/ProjectBadge";
import {useI18n} from "vue-i18n";

const showModalWindow = inject('showModalWindow');
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const publicPath = process.env.BASE_URL;

const store = useStore();

const emit = defineEmits(['addTask', 'editTask']);
const props = defineProps({
    id: {
        type: String
    }
});

const basicLoading = ref(true);
const projectInfo = ref({});
const userLoading = ref(new Set());
const addEducatorLoading = ref(false);

// Force reload of dark-editable
const updateTotal = ref(0);

const router = useRouter();
const {t} = useI18n();

function goBack() {
    router.push('/projects');
}

function addTask(cloneID) {
    emit("addTask", cloneID);
}

function editTask(taskID) {
    emit("editTask", taskID);
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

function resetEducatorPassword(id, username) {
    userLoading.value.add(id);
    axios.post("?", {"action": "educatorResetPassword", id: id, project_id: props.id, ...updateAxiosParams()})
        .then((response) => {
            let text = `<p>Password reset successfully.</p>
                    <p>Username: ${username}<br />Password: ${response.data.password}</p>`;
            if (projectInfo.value.confirmed) {
                text += "<p>This is the last time you can see this password, save it in a safe place.</p>";
            }
            showModalWindow(text);
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

function addEducator() {
    addEducatorLoading.value = true;
    axios.post("?", {"action": "educatorAdd", id: props.id, ...updateAxiosParams()})
        .then((response) => {
            if (response.data.result === "OK") {
                let text = `<p>New educator created.</p>
                    <p>Username: ${response.data.username}<br />Password: ${response.data.password}</p>`;
                if (projectInfo.value.confirmed) {
                    text += "<p>This is the last time you can see this password, save it in a safe place.</p>";
                }
                showModalWindow(text);
            }
            updateProject();
        })
        .catch((reason) => {
            let debugText = reason.response.statusText + " - " + reason.response.data.error;
            showModalWindow(debugText);
        })
        .then(() => {
            addEducatorLoading.value = false;
        });
}

function updateProject() {
    updateTotal.value++;
    axios.get("?", {
        "params": {
            "action": "projectInfo", "id": props.id, ...updateAxiosParams()
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
                    emptytext: t('empty').capitalize(),
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