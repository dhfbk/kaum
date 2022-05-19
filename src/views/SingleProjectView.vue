<template>
    <template v-if="route.meta.action === 'add'">
        <TaskForm :formLoadingPercent="formLoadingPercent"
                  :disableSubmit="formLoading"
                  :valuesProp="getInitialValues()"
                  title="Add task"
                  @submit="submitNewTask"
                  @cancel="cancel"
                  @back="back"
        />
    </template>

    <template v-else-if="route.meta.action === 'list'">
        <ProjectMain :id="route.params.id" @addTask="addTask"/>
    </template>

    <template v-else-if="route.meta.action === 'task'">
        <TaskMain :id="route.params.id" :task="route.params.task"></TaskMain>
    </template>
</template>

<script setup>
import {inject, ref} from 'vue'
import {useStore} from 'vuex'
import {useRoute, useRouter} from 'vue-router'

import TaskForm from '@/components/TaskForm.vue'
import ProjectMain from '@/components/ProjectMain.vue'
import TaskMain from '@/components/TaskMain'

const store = useStore();
const route = useRoute();
const router = useRouter();

const showModalWindow = inject('showModalWindow');
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');

const formLoading = ref(false);
const formLoadingPercent = ref(0);
const abortController = ref(new AbortController());

const today = new Date();
const final_day = new Date();
final_day.setDate(today.getDate() + Number(store.state.options.task_default_days_duration));

function addTask(cloneID) {
    router.push({
        name: "projectIdNewTask",
        params: {cloneID: cloneID, id: route.params.id}
    });
}

function getInitialValues() {
    return {
        type: '',
        students: store.state.options.task_default_students,
        passwords: "",
        type_info: {files: {}},
        time: {
            days: Array(5).fill(1).map((x, y) => x + y),
            start_date: today,
            end_date: final_day,
            afternoon_from: store.state.options.task_default_afternoon_from,
            afternoon_to: store.state.options.task_default_afternoon_to,
            morning_from: store.state.options.task_default_morning_from,
            morning_to: store.state.options.task_default_morning_to,
            use_afternoon: true,
            use_morning: true
        }
    };
}

function back() {
    router.push("/project/" + route.params.id);
}

function cancel() {
    abortController.value.abort();
}

async function submitNewTask(v) {
    abortController.value = new AbortController();
    const data = {
        "action": "task",
        "type": v.type,
        "sub": "add",
        "project_id": route.params.id,
        ...updateAxiosParams(),
        info: JSON.stringify(v)
    };
    let formData = new FormData();
    for (let k in data) {
        formData.append(k, data[k]);
    }

    let abortByCheck = false;

    formData.append("check_only", "1");
    await axios.post("?", formData, {}).then((response) => {
            if (response.data.result !== "OK") {
                console.log("Error!");
            }
        }
    ).catch((reason) => {
        let debugText = "Unknown error";
        if (reason.response) {
            debugText = reason.response.statusText + " - " + reason.response.data.error;
        } else if (reason) {
            debugText = reason;
        }
        showModalWindow(debugText);
        abortByCheck = true;
    });

    if (abortByCheck) {
        return;
    }
    formData.delete("check_only");

    for (let k in v['type_info']['files']) {
        for (let f of v['type_info']['files'][k]) {
            if (f.size > store.state.options.max_size) {
                let thisSize = (f.size / (1024 * 1024)).toFixed(2) + "M";
                let maxSize = (store.state.options.max_size / (1024 * 1024)).toFixed(2) + "M";
                showModalWindow(`File ${f.name} is too big (size: ${thisSize}; max allowed size: ${maxSize})`);
                return;
            }
            formData.append(k + "[]", f);
        }
    }
    formLoading.value = true;
    axios.post("?", formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        },
        onUploadProgress: progressEvent => {
            formLoadingPercent.value = 100 * progressEvent.loaded / progressEvent.total;
        },
        signal: abortController.value.signal
    })
        .then((response) => {
            console.log(response.data);
            if (response.data.result === "OK") {
                // resetInitialValues();
                router.push("/project/" + route.params.id);
                // activate();
                showModalWindow("Task added successfully");
            } else {
                showModalWindow(response.data.error);
            }
        })
        .catch((reason) => {
            if (reason.message === "canceled") {
                return;
            }
            let debugText = "Unknown error";
            if (reason.response) {
                debugText = reason.response.statusText + " - " + reason.response.data.error;
            } else if (reason) {
                debugText = reason;
            }
            showModalWindow(debugText);
        })
        .then(() => {
            formLoading.value = false;
        });
}

// onMounted(async function () {
//     await axios.get("?", {
//         "params": {
//             "action": "taskTypes"
//         }
//     })
//         .then((response) => {
//             typeOptions.value = response.data.types;
//             for (let prop in typeOptions.value) {
//                 components.value[prop] = defineAsyncComponent(() =>
//                     import(`@/components/tasks/${prop}Form.vue`)
//                 )
//             }
//         });
// });

</script>
