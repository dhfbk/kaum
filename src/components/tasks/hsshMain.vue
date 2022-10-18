<template>
    <div class="row mt-5">
        <div class="col">
            <h2>
                Data
            </h2>
            <p>
                Download data:
                <button class="btn btn-success ms-1" @click="downloadData"><i class="bi bi-filetype-xls"></i> Microsoft
                    Excel
                </button>
            </p>
        </div>
    </div>
</template>

<script setup>

import {defineProps, inject, onMounted, ref} from "vue";

const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const showModalWindow = inject('showModalWindow');

const props = defineProps({
    additionalData: Object,
    infoData: Object,
    values: Object
});
const additionalUserData = ref(props.additionalData);
const infoData = ref(props.infoData);

function downloadData() {
    let params = {
        "action": "task",
        "type": "hssh",
        "sub": "exportResults",
        "id": props.values.id,
        ...updateAxiosParams()
    };
    let usp = new URLSearchParams(params).toString();
    window.open(axios.defaults.baseURL + "?" + usp).focus();
}

onMounted(function () {
    infoData.value['_titles']['hs_annotations'] = "Ann. per instance";
    infoData.value['hs_annotations'] = infoData.value['_data']['type_info']['annotations'];

    infoData.value['_titles']['hs_dataset_ch'] = "Dataset chat";
    infoData.value['hs_dataset_ch'] = infoData.value['_data']['hssh_datasets']['ch'][infoData.value['_data']['type_info']['dataset_ch']];
    infoData.value['_titles']['hs_dataset_gr'] = "Dataset graffiti";
    infoData.value['hs_dataset_gr'] = infoData.value['_data']['hssh_datasets']['gr'][infoData.value['_data']['type_info']['dataset_gr']];

    axios.get("?", {
        "params": {
            "action": "task", "type": "hssh", "sub": "taskResults", "id": props.values.id, ...updateAxiosParams()
        }
    })
        .then((response) => {
            let creenderAnnotations = {};
            let clusterInfo = {};
            for (let index in response.data.info) {
                let info = response.data.info[index];
                let text = "";
                for (let t of ['ch', 'gr']) {
                    text += t + ": ";
                    text += info['annotations'][t]['annotated'] + "/" + info['annotations'][t]['total'];
                    if (t === "ch") {
                        text += " - ";
                    }
                }
                creenderAnnotations[index] = text.trim();
                clusterInfo[index] = info['cluster'];
            }
            additionalUserData.value.titles.push("Cluster");
            additionalUserData.value.titles.push("Annotated");
            additionalUserData.value.values.push(clusterInfo);
            additionalUserData.value.values.push(creenderAnnotations);
        })
        .catch((reason) => {
            let debugText = reason.response.statusText + " - " + reason.response.data.error;
            showModalWindow(debugText);
        })
});

</script>

<style scoped>

</style>