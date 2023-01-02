<template>
    <div>
        <div class="row">
            <div class="col">
                <div class="alert alert-warning">
                    The task information cannot be updated at a later time.
                </div>
            </div>
        </div>
        <div class="row" v-if="!hsshLoading">
            <div class="col-12">
                <label class="form-label" for="annotationsPerInstance">Annotations per instance:</label>
                <input v-model="values.type_info['annotations']" name="annotations" class="form-control"
                       id="annotationsPerInstance"
                       type="number" min="1" :max="values.students"
                       placeholder="Number of annotations" required/>
                <div class="invalid-feedback">Number of annotations is required and must be > 0.</div>
            </div>


            <div class="col-12 mt-3 mb-1">
                <hr/>
                <h5>Choose sentences</h5>
            </div>

            <template v-if="!hsshLoading">
                <div class="col-12 col-xl-6">
                    <h6>
                        Graffiti
                    </h6>
                    <hssh-table-list v-model="values.type_info['datasets']" :form="true"
                                     :dataset-list="datasetList['gr']"></hssh-table-list>
                </div>
                <div class="col-12 col-xl-6">
                    <h6>
                        Dialogues
                    </h6>
                    <hssh-table-list v-model="values.type_info['datasets']" :form="true"
                                     :dataset-list="datasetList['ch']"></hssh-table-list>
                </div>
            </template>
            <div class="col-12" v-else>
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
            <div class="col-12">
                <hr/>
                <hssh-add-dataset :disable-validation="true" @update-datasets="updateDatasets"></hssh-add-dataset>
            </div>


            <!--            <div v-for="(fileTypeString, fileType) in fileTypes" :key="fileType" class="col-md mb-3">-->
            <!--                <div class="row">-->
            <!--                    <div class="col">-->
            <!--                        <div class="form-check">-->
            <!--                            <input v-model="values.type_info['custom_' + fileType]" class="form-check-input"-->
            <!--                                   type="checkbox"-->
            <!--                                   :id="'hsshCustomFileCheck_' + fileType">-->
            <!--                            <label class="form-check-label" :for="'hsshCustomFileCheck_' + fileType">-->
            <!--                                Upload {{ fileTypeString }} file-->
            <!--                            </label>-->
            <!--                        </div>-->
            <!--                    </div>-->
            <!--                </div>-->
            <!--                <div class="row mt-2">-->
            <!--                    <div class="col" v-if="values.type_info['custom_' + fileType]">-->
            <!--                        <input class="form-control" type="file" @change="handleFileUpload( fileType, $event )" required>-->
            <!--                        <div class="invalid-feedback">You must upload a file.</div>-->

            <!--                        <div class="form-check mt-2">-->
            <!--                            <input v-model="values.type_info['save_' + fileType]" class="form-check-input"-->
            <!--                                   type="checkbox"-->
            <!--                                   :id="'saveFileCheck_' + fileType">-->
            <!--                            <label class="form-check-label" :for="'saveFileCheck_' + fileType">-->
            <!--                                Save {{ fileTypeString }} dataset for future tasks-->
            <!--                            </label>-->
            <!--                        </div>-->
            <!--                    </div>-->
            <!--                    <div class="col" v-else>-->
            <!--                        <div v-if="datasets[fileType] == undefined || datasets[fileType].length == 0">-->
            <!--                            <span class="badge bg-danger">No datasets of this kind</span>-->
            <!--                        </div>-->
            <!--                        <select v-else v-model="values.type_info['dataset_' + fileType]" class="form-control" required>-->
            <!--                            <option value="">[Select one]</option>-->
            <!--                            <option v-for="(t, i) in datasets[fileType]" :value="i" :key="i">{{ t }}</option>-->
            <!--                        </select>-->
            <!--                    </div>-->
            <!--                </div>-->
            <!--            </div>-->
        </div>
        <div class="row" v-else>
            <div class="col">
                <div class="spinner-border" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>

import {defineProps, onMounted, ref, inject} from "vue";
import {useStore} from "vuex";
import {useRoute} from "vue-router";
import HsshTableList from "@/components/tasks/hsshTableList";
import HsshAddDataset from "@/components/tasks/hsshAddDataset";

const store = useStore();
const route = useRoute();

const props = defineProps({
    values: {
        type: Object
    }
});
const values = ref(props.values);
const datasetList = ref({});
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const hsshLoading = ref(true);

async function updateDatasets() {
    hsshLoading.value = true;
    await axios.get("?", {
        "params": {
            "action": "task",
            "sub": "listDatasets",
            "type": "hssh",
            "project_id": route.params.id,
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
            hsshLoading.value = false;
        });
}

onMounted(async function () {
    if (!values.value['type_info']['annotations']) {
        values.value['type_info']['annotations'] = store.state.options.task_default_annotations;
    }
    if (!values.value['type_info']['datasets']) {
        values.value['type_info']['datasets'] = {};
    }
    await updateDatasets();

})
</script>

<style>
</style>
