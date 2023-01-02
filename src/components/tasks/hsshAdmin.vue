<template>
    <div class="accordion-item">
        <h2 class="accordion-header" id="collapseHsshDatasets_head">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseHsshScenarios" aria-expanded="false"
                    aria-controls="collapseHsshScenarios">
                High School Superhero datasets
            </button>
        </h2>
        <div id="collapseHsshScenarios" class="accordion-collapse collapse"
             aria-labelledby="collapseHsshScenarios_head"
             data-bs-parent="#accordionAdmin">
            <div class="accordion-body">
                <hssh-add-dataset :disable-validation="false" @update-datasets="updateDatasets"></hssh-add-dataset>
                <hr class="my-4"/>
                <div class="row" v-if="isLoading">
                    <div class="col">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div v-else class="row mt-3">
                    <div class="col-12 col-xl-6">
                        <h5 class="display-5">
                            Graffiti
                        </h5>
                        <hssh-table-list :dataset-list="datasetList['gr']" @delete-dataset="deleteDataset"></hssh-table-list>
                    </div>
                    <div class="col-12 col-xl-6">
                        <h5 class="display-5">
                            Dialogues
                        </h5>
                        <hssh-table-list :dataset-list="datasetList['ch']" @delete-dataset="deleteDataset"></hssh-table-list>
                    </div>
                </div>
            </div>
        </div>
    </div>


</template>

<script setup>

import {onMounted, ref, inject} from "vue";
import HsshAddDataset from "@/components/tasks/hsshAddDataset";
import HsshTableList from "@/components/tasks/hsshTableList";
// import PicButton from "@/components/objects/PicButton";

const datasetList = ref({});
// const schoolList = ref({});
//
// const values = ref({"lang": "", "school": 2});
const isLoading = ref(true);
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const showModalWindow = inject('showModalWindow');

async function updateDatasets() {
    isLoading.value = true;
    await axios.get("?", {
        "params": {
            "action": "task",
            "sub": "listDatasets",
            "type": "hssh",
            ...updateAxiosParams()
        }
    })
        .then((response) => {
            datasetList.value = response.data.datasets;
        })
        .catch((reason) => {
            console.log(reason);
        })
        .then(() => {
            isLoading.value = false;
        });
}

function deleteDataset(id) {
    if (confirm("Are you sure? This action cannot be undone")) {
        isLoading.value = true;
        axios.post("?", {
            "action": "task",
            "sub": "deleteDataset",
            "type": "hssh",
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
                isLoading.value = false;
            });
    }
}

onMounted(async function () {
    await updateDatasets();
});

</script>

<style scoped>

</style>