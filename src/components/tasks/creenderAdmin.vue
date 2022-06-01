<template>
    <CreenderChoiceList></CreenderChoiceList>

    <div class="accordion-item">
        <h2 class="accordion-header" id="collapseCreenderDatasets_head">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseCreenderDatasets" aria-expanded="false"
                    aria-controls="collapseCreenderDatasets">
                Creender datasets
            </button>
        </h2>
        <div id="collapseCreenderDatasets" class="accordion-collapse collapse"
             aria-labelledby="collapseCreenderDatasets_head"
             data-bs-parent="#accordionAdmin">
            <div class="accordion-body">
                <CreenderAddDataset :useSave="false" @updateDatasets="updateDatasets()"></CreenderAddDataset>

                <div class="row" v-if="creenderLoading">
                    <div class="col">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div class="row mt-3" v-else>
                    <div class="col">
                        <p v-if="datasets.length === 0">
                            No datasets yet
                        </p>
                        <table v-else class="table">
                            <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Records</th>
                                <th scope="col">Demo</th>
                                <th scope="col">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="d in datasets" :key="d.id" class="align-middle">
                                <th scope="row">{{ d.id }}</th>
                                <td>{{ d.name }}</td>
                                <td>{{ d.num }}</td>
                                <td>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch" v-model="d.test"
                                               @change="toggleDemo(d.id)" :id="'check_demo_' + d.id">
                                    </div>
                                </td>
                                <!--                                <td v-if="d.test"><span class="badge bg-success"><i class="bi bi-check-lg"></i></span></td>-->
                                <!--                                <td v-else><span class="badge bg-danger"><i class="bi bi-x-lg"></i></span></td>-->
                                <td>
                                    <PicButton :text="$t('action.delete').capitalize()"
                                               @click="deleteDataset(d.id)" color="danger" icon="trash"/>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


</template>

<script setup>

import {onMounted, inject, ref} from "vue";
import CreenderAddDataset from "@/components/tasks/creenderAddDataset";
import CreenderChoiceList from "@/components/tasks/creenderChoiceList";
import PicButton from "@/components/objects/PicButton";

const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const creenderLoading = ref(true);
const datasets = ref([]);
const showModalWindow = inject('showModalWindow');

function toggleDemo(id) {
    document.getElementById('check_demo_' + id)?.setAttribute('disabled', '');
    axios.post("?", {
        "action": "task",
        "sub": "toggleDemoDataset",
        "type": "creender",
        id: id,
        ...updateAxiosParams()
    })
        .then((response) => {
            console.log(response.data);
            if (response.data.result === "OK") {
                datasets.value[id].test = response.data.value;
            }
        })
        .catch((reason) => {
            let debugText = reason.response.statusText + " - " + reason.response.data.error;
            showModalWindow(debugText);
        })
        .then(() => {
            document.getElementById('check_demo_' + id)?.removeAttribute('disabled');
        });
}

function deleteDataset(id) {
    if (confirm("Are you sure? This action cannot be undone")) {
        creenderLoading.value = true;
        axios.post("?", {
            "action": "task",
            "sub": "deleteDataset",
            "type": "creender",
            id: id,
            ...updateAxiosParams()
        })
            .then(() => {
                updateDatasets();
            })
            .catch((reason) => {
                let debugText = reason.response.statusText + " - " + reason.response.data.error;
                showModalWindow(debugText);
            })
            .then(() => {
                creenderLoading.value = false;
            });
    }
}

async function updateDatasets() {
    creenderLoading.value = true;
    await axios.get("?", {
        "params": {
            "action": "task",
            "sub": "listDatasets",
            "type": "creender",
            ...updateAxiosParams()
        }
    })
        .then((response) => {
            datasets.value = {};
            for (let index in response.data.datasets) {
                let d = response.data.datasets[index];
                d['test'] = !!d['test'];
                datasets.value[d.id] = d;
            }
        })
        .catch((reason) => {
            console.log(reason);
        })
        .then(() => {
            creenderLoading.value = false;
        });
}

onMounted(async function () {
    await updateDatasets();
});
</script>

<style scoped>

</style>