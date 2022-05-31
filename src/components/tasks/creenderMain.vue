<template>
    <p>Under construction</p>
</template>

<script setup>
import {defineProps, onMounted, ref, inject} from "vue";

const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const showModalWindow = inject('showModalWindow');

const props = defineProps({
    additionalData: Object,
    values: Object
});
const additionalUserData = ref(props.additionalData);

onMounted(function () {
    // additionalUserData.value.titles.push("Ciao");
    // additionalUserData.value.values.push("Ciao");
    axios.get("?", {
        "params": {
            "action": "task", "type": "creender", "sub": "taskResults", "id": props.values.id, ...updateAxiosParams()
        }
    })
        .then((response) => {
            let creenderAnnotations = {};
            let clusterInfo = {};
            for (let index in response.data.info) {
                let info = response.data.info[index];
                creenderAnnotations[index] = info['annotated'] + "/" + info['total'];
                clusterInfo[index] = info['cluster'];
            }
            additionalUserData.value.titles.push("Cluster");
            additionalUserData.value.titles.push("Annotated");
            additionalUserData.value.values.push(clusterInfo);
            additionalUserData.value.values.push(creenderAnnotations);
            console.log(response.data);
            console.log(additionalUserData.value);
            // projectList.value = response.data.records;
        })
        .catch((reason) => {
            let debugText = reason.response.statusText + " - " + reason.response.data.error;
            showModalWindow(debugText);
        })
});

</script>

<style scoped>

</style>