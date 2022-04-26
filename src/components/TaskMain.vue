<template>
    <p v-if="basicLoading">Loading</p>
    <template v-else>
        <h1>
            Task: {{ taskInfo.name }}
            <small class="text-muted">Project: {{ taskInfo.project_info.name }}</small>
        </h1>
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
    </template>
</template>

<script setup>

// import {useRoute} from "vue-router";
import {ref, onMounted, defineProps, inject, nextTick} from "vue";
// import DarkEditable from '@/dark-editable'
const DarkEditable = require('@/dark-editable.js').default;
// const pippo = new DarkEditable(document.getElementById("ciao"));
// console.log(pippo);

// new dark.DarkEditable();
// const route = useRoute();
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const showModalWindow = inject('showModalWindow');

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

async function updateTask() {
    axios.get("?", {
        "params": {
            "action": "task", "sub": "info", "project_id": props.id, "id": props.task, ...updateAxiosParams()
        }
    })
        .then(async (response) => {
            basicLoading.value = false;
            taskInfo.value = response.data.info;
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
                    ajaxOptions: {dataType: "json"},
                    error: function(response) {
                        return response.json().then(function(data) {
                            showModalWindow(data.error);
                        });
                    },
                    success: function (response, newValue) {
                        response.json().then(function(data) {
                            console.log(data);
                        });
                    },
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

