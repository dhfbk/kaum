<template>
    <div class="row" v-if="creenderLoading">
        <div class="col">
            <div class="spinner-border" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    </div>
    <div v-else>
        <h3>Creender datasets</h3>

        <CreenderAddDataset :useSave="false" @updateDatasets="updateDatasets()"></CreenderAddDataset>

        <div class="row mt-3">
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
                        <th scope="col">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="d in datasets" :key="d.id" class="align-middle">
                        <th scope="row">{{ d.id }}</th>
                        <td>{{ d.name }}</td>
                        <td>{{ d.num }}</td>
                        <td>Actions</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>

import {onMounted, inject, ref} from "vue";
import CreenderAddDataset from "@/components/tasks/creenderAddDataset";

const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const creenderLoading = ref(true);
const datasets = ref([]);

async function updateDatasets() {
    await axios.get("?", {
        "params": {
            "action": "task",
            "sub": "listDatasets",
            "type": "creender",
            ...updateAxiosParams()
        }
    })
        .then((response) => {
            datasets.value = response.data.datasets;
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