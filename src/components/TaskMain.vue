<template>
    <p v-if="basicLoading">
        <LoadingSpinner/>
        Loading
    </p>
    <template v-else>
        <h1 class="display-1">
            <small class="text-muted">
                {{ taskInfo.project_info.name }}
                <i class="bi bi-arrow-right-short"></i>
            </small>
            {{ taskInfo.name }}
        </h1>
        <div>
            <p>
                {{ $t("task.status").capitalize() }}:
                <task-badge :e="taskInfo"></task-badge>
            </p>
            <task-buttons @update="updateTask" @clone-task="addTask" :id="id" :e="taskInfo"
                          :inside="true"></task-buttons>
            <PicButton :always-text="true" @click="goBack"
                       :text="$t('task.back').capitalize()" color="yellow" icon="arrow-90deg-up"/>
        </div>

        <div class="accordion mt-5" id="accordionTaskStudents">
            <div class="accordion-item">
                <h2 class="accordion-header" id="headingTaskStudents">
                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                            data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                        {{ $t('student.plur').capitalize() }}
                    </button>
                </h2>
                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingTaskStudents"
                     data-bs-parent="#accordionTaskStudents">
                    <div class="accordion-body">
                        <div class="row mt-2">
                            <div class="col-md-9">
                                <h2 class="display-2">
                                    {{ $t('student.plur').capitalize() }}
                                </h2>
                            </div>
                            <div class="col-md-3 text-end">
                            </div>
                        </div>
                        <p v-if="taskInfo.students.length == 0">
                            {{ $t('student.no').capitalize() }}
                        </p>
                        <div v-else class="table-responsive">
                            <table class="table table-nowrap">
                                <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">{{ $t('user.username').capitalize() }}</th>
                                    <th scope="col">{{ $t('name').capitalize() }}</th>
                                    <th v-for="(t, index) in additionalUserData.titles" :key="index">{{ t }}</th>
                                    <th scope="col">{{ $t('status').capitalize() }}</th>
                                    <th scope="col">{{ $t('action.plur').capitalize() }}</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="e in taskInfo.students" :key="updateTotal + '_' + e.id" class="align-middle">
                                    <th scope="row">{{ e.id }}</th>
                                    <td>{{ e.username }}</td>
                                    <td>
                        <span class="dark-enable" :data-username="e.username" :id="e.username + '_change_name'"
                              data-title="Edit name">{{ e.data.name }}</span>
                                    </td>
                                    <td v-for="(obj, index) in additionalUserData.values" :key="index">{{
                                            obj[e.id]
                                        }}
                                    </td>
                                    <td>
                        <span v-if="e.data.disabled" class="badge bg-danger">{{
                                $t('user.disabled').capitalize()
                            }}</span>
                                        <span v-else class="badge bg-success">{{
                                                $t('user.active').capitalize()
                                            }}</span>
                                    </td>
                                    <td>
                                        <PicButton v-if="!e.data.disabled" @click="toggleUser(e.id)"
                                                   :text="$t('action.disable').capitalize()" color="warning"
                                                   icon="x-circle" :disabled="userLoading.has(e.id)"/>
                                        <PicButton v-else @click="toggleUser(e.id)"
                                                   :text="$t('action.enable').capitalize()"
                                                   color="warning" icon="brightness-high"
                                                   :disabled="userLoading.has(e.id)"/>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <component :is="component" :values="taskInfo" :additionalData="additionalUserData"/>

    </template>
</template>

<script setup>

// import {useRoute} from "vue-router";
import {defineAsyncComponent, shallowRef, ref, onMounted, defineProps, inject, nextTick, defineEmits} from "vue";
import {useRoute, useRouter} from "vue-router";
import LoadingSpinner from "@/components/objects/LoadingSpinner";
import PicButton from "@/components/objects/PicButton";
import TaskButtons from "@/components/objects/TaskButtons";
import TaskBadge from "@/components/objects/TaskBadge";
import {useI18n} from "vue-i18n";

const DarkEditable = require('@/dark-editable.js').default;

// new dark.DarkEditable();
const router = useRouter();
const route = useRoute();
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const showModalWindow = inject('showModalWindow');
const {t} = useI18n();

const component = shallowRef(null);
const basicLoading = ref(true);
const taskInfo = ref({});

const additionalUserData = ref({
    "titles": [],
    "values": []
});

// Force reload of dark-editable
const updateTotal = ref(0);

const userLoading = ref(new Set());

const props = defineProps({
    id: {
        type: String
    },
    task: {
        type: String
    }
});

const emit = defineEmits(['addTask']);

function addTask(cloneID) {
    emit("addTask", cloneID);
}

function toggleUser(id) {
    userLoading.value.add(id);
    axios.post("?", {"action": "userToggleAvailability", id: id, ...updateAxiosParams()})
        .then(() => {
            updateTask();
        })
        .catch((reason) => {
            let debugText = reason.response.statusText + " - " + reason.response.data.error;
            showModalWindow(debugText);
        })
        .then(() => {
            userLoading.value.delete(id);
        });
}


function goBack() {
    router.push("/project/" + route.params.id);
}

async function updateTask() {
    updateTotal.value++;
    axios.get("?", {
        "params": {
            "action": "task", "sub": "info", "project_id": props.id, "id": props.task, ...updateAxiosParams()
        }
    })
        .then(async (response) => {
            taskInfo.value = response.data.info;
            component.value = defineAsyncComponent(() =>
                import(`@/components/tasks/${taskInfo.value.tool}Main.vue`)
            )
            basicLoading.value = false;

            await nextTick();
            let darkList = document.getElementsByClassName("dark-enable");
            for (let d of darkList) {
                // console.log(d.dataset.username);
                // console.log(axios.defaults.baseURL);
                const params = {
                    "action": "task",
                    "sub": "changeUserName",
                    // "project_id": props.id,
                    // "id": props.task,
                    ...updateAxiosParams()
                };
                // axios.get("?", {
                //     "params": params
                // }).then(function (resp) {
                //     console.log(resp);
                // });
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
                    // success: function (response, newValue) {
                    //     response.json().then(function(data) {
                    //         console.log(data);
                    //     });
                    // },
                    title: d.dataset.title ? d.dataset.title : "Edit data"
                });
            }
        })
        .catch((reason) => {
            console.log(reason);
            // let debugText = reason.response.statusText + " - " + reason.response.data.error;
            // showModalWindow(debugText);
        })
        .then(() => {
            // mainLoaded.value = true;
        });
}

onMounted(async function () {
    // console.log(route);
    await updateTask();
});

</script>

<style scoped>

</style>

