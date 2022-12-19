<template>
    <div class="accordion-item">
        <h2 class="accordion-header" id="collapseCreenderDatasets_head">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseRcScenarios" aria-expanded="false"
                    aria-controls="collapseRcScenarios">
                Rocket.Chat scenarios
            </button>
        </h2>
        <div id="collapseRcScenarios" class="accordion-collapse collapse"
             aria-labelledby="collapseRcScenarios_head"
             data-bs-parent="#accordionAdmin">
            <div class="accordion-body">
                <div class="row" v-if="isLoading">
                    <div class="col">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div v-else class="row mt-3">
                    <div class="col">
                        <p v-if="scenarioList.length === 0">
                            No scenarios yet
                        </p>
                        <table v-else class="table">
                            <thead>
                            <tr>
                                <th scope="col">#</th>
                                <th scope="col">Name</th>
                                <th scope="col">Channel name</th>
                                <th scope="col">School</th>
                                <th scope="col">Lang</th>
                                <th scope="col">Actions</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr v-for="d in scenarioList" :key="d.id" class="align-middle">
                                <th scope="row">{{ d.id }}</th>
                                <td>{{ d.name }}</td>
                                <td>{{ d.label }}</td>
                                <td>{{ schoolList[d.school] }}</td>
                                <td>{{ d.lang }}</td>
                                <td>
                                    <PicButton :text="$t('action.delete').capitalize()"
                                               @click="deleteScenario(d.id)" color="danger" icon="trash"/>
                                </td>
                            </tr>
                            </tbody>
                        </table>

                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col">
                        <h5 class="display-5">Add scenario</h5>
                    </div>
                </div>
                <rc-scenario :values="values" @update="updateScenarios" :schools="schoolList"/>
            </div>
        </div>
    </div>


</template>

<script setup>

import {onMounted, ref, inject} from "vue";
import PicButton from "@/components/objects/PicButton";
import rcScenario from "@/components/tasks/rcScenario";

const scenarioList = ref([]);
const schoolList = ref({});

const values = ref({"lang": "", "school": 2});
const isLoading = ref(true);
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const showModalWindow = inject('showModalWindow');

function deleteScenario(id) {
    if (confirm("Are you sure? This action cannot be undone")) {
        isLoading.value = true;
        axios.post("?", {
            "action": "task",
            "sub": "deleteScenario",
            "type": "rc",
            id: id,
            ...updateAxiosParams()
        })
            .then(() => {
                updateScenarios();
            })
            .catch((reason) => {
                let debugText = reason.response.statusText + " - " + reason.response.data.error;
                showModalWindow(debugText);
                isLoading.value = false;
            });
    }
}
async function updateScenarios() {
    isLoading.value = true;
    await axios.get("?", {
        "params": {
            "action": "task",
            "sub": "getScenarios",
            "type": "rc",
            ...updateAxiosParams()
        }
    })
        .then((response) => {
            scenarioList.value = response.data.data;
            schoolList.value = {};
            for (let index in response.data.schools) {
                let s = response.data.schools[index];
                schoolList.value[s["id"]] = s["name"];
            }
            // for (let index in response.data.datasets) {
            //     let d = response.data.datasets[index];
            //     d['test'] = !!d['test'];
            //     datasets.value[d.id] = d;
            // }

        })
        .catch((reason) => {
            console.log(reason);
        })
        .then(() => {
            isLoading.value = false;
        });
}

onMounted(async function () {
    await updateScenarios();
    // scenarioList.value.push({
    //     "id": 1,
    //     "name": "Danza classica",
    //     "label": "danza",
    //     "school": "Middle school",
    //     "lang": "it"
    // }, {
    //     "id": 2,
    //     "name": "Ginnastica",
    //     "label": "ginnastica",
    //     "school": "Middle school",
    //     "lang": "it"
    // }, {
    //     "id": 3,
    //     "name": "Staffetta",
    //     "label": "staffetta",
    //     "school": "Middle school",
    //     "lang": "it"
    // }, {
    //     "id": 4,
    //     "name": "Foto intime",
    //     "label": "foto",
    //     "school": "High school",
    //     "lang": "it"
    // }, {
    //     "id": 5,
    //     "name": "Bacio",
    //     "label": "bacio",
    //     "school": "High school",
    //     "lang": "it"
    // })
});

</script>

<style scoped>

</style>