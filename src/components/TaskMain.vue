<template>
    <p v-if="basicLoading">
        <LoadingSpinner/>
        Loading
    </p>
    <template v-else>
        <h1>
            Task: {{ taskInfo.name }}
        </h1>
        <h2>
            Project: {{ taskInfo.project_info.name }}
        </h2>
        <div class="row mt-5">
            <div class="col-md-9">
                <h2>
                    Students
                </h2>
            </div>
            <div class="col-md-3 text-end">
                <!--       <button class="btn btn-primary btn-sm" @click="this.$router.push('/projects/new')">
                        <i class="bi bi-file-earmark-plus"></i> Add educator
                      </button> -->
            </div>
        </div>
        <p v-if="taskInfo.students.length == 0">
            No students
        </p>
        <table v-else class="table">
            <thead>
            <tr>
                <th scope="col">#</th>
                <th scope="col">Username</th>
                <th scope="col">Name</th>
                <th scope="col">Actions</th>
            </tr>
            </thead>
            <tbody>
            <tr v-for="e in taskInfo.students" :key="e.id" class="align-middle">
                <th scope="row">{{ e.id }}</th>
                <td>{{ e.username }}</td>
                <td class="dark-enable" :data-username="e.username">{{ e.data.name }}</td>
                <td>Actions</td>
            </tr>
            </tbody>
        </table>

        <component :is="component" :values="taskInfo"/>

        <div class="row mt-5">
            <div class="col text-end">
                <button class="btn btn-warning btn-lg ms-3" @click.prevent="back()">Back</button>
            </div>
        </div>
    </template>
</template>

<script setup>

// import {useRoute} from "vue-router";
import {defineAsyncComponent, shallowRef, ref, onMounted, defineProps, inject, nextTick} from "vue";
import {useRoute, useRouter} from "vue-router";
import LoadingSpinner from "@/components/objects/LoadingSpinner";
// import DarkEditable from '@/dark-editable'
const DarkEditable = require('@/dark-editable.js').default;
// const pippo = new DarkEditable(document.getElementById("ciao"));
// console.log(pippo);

// new dark.DarkEditable();
const router = useRouter();
const route = useRoute();
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const showModalWindow = inject('showModalWindow');

const component = shallowRef(null);
const basicLoading = ref(true);
const taskInfo = ref({});

const props = defineProps({
    id: {
        type: String
    },
    task: {
        type: String
    }
});

function back() {
    router.push("/project/" + route.params.id);
}

async function updateTask() {
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
                    title: 'Enter username'
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

