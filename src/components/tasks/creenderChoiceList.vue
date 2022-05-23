<template>
    <div class="accordion-item">
        <h2 class="accordion-header" id="collapseCreenderChoiceList_head">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                    data-bs-target="#collapseCreenderChoiceList" aria-expanded="false"
                    aria-controls="collapseCreenderChoiceList">
                Creender choices lists
            </button>
        </h2>
        <div id="collapseCreenderChoiceList" class="accordion-collapse collapse"
             aria-labelledby="collapseCreenderChoiceList_head"
             data-bs-parent="#accordionAdmin">
            <div class="accordion-body">
                <div class="row" v-if="creenderLoading">
                    <div class="col">
                        <div class="spinner-border" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
                <div v-else>
                    <div class="row mt-3">
                        <div class="col">
                            <p v-if="choiceList.length === 0">
                                No datasets yet
                            </p>
                            <table v-else class="table">
                                <thead>
                                <tr>
                                    <th scope="col">#</th>
                                    <th scope="col">Name</th>
                                    <th scope="col">Categories</th>
                                    <th scope="col">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr v-for="d in choiceList" :key="d.id" class="align-middle">
                                    <th scope="row">{{ d.id }}</th>
                                    <td>{{ d.name }}</td>
                                    <td>{{ d.data }}</td>
                                    <td>Actions</td>
                                </tr>
                                </tbody>
                            </table>

                        </div>
                    </div>
                    <div class="row mt-3">
                        <h5 class="display-5">Add choice list</h5>
                        <creender-choice @updateChoices="updateChoices" :use-button="true" :choices="tmpChoices"
                                         :list="choiceList"></creender-choice>
                    </div>
                </div>
            </div>
        </div>

    </div>

</template>

<script setup>
import {onMounted, inject, ref} from "vue";
import CreenderChoice from "@/components/tasks/creenderChoice";

const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const creenderLoading = ref(true);
const choiceList = ref([]);
const tmpChoices = ref([]);

async function updateChoices() {
    creenderLoading.value = true;
    await axios.get("?", {
        "params": {
            "action": "task",
            "sub": "listChoices",
            "type": "creender",
            ...updateAxiosParams()
        }
    })
        .then((response) => {
            choiceList.value = response.data.datasets;
        })
        .catch((reason) => {
            console.log(reason);
        })
        .then(() => {
            creenderLoading.value = false;
        });
}

onMounted(async function () {
    await updateChoices();
})
</script>

<style scoped>

</style>