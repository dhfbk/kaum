<template>
    <div class="row mt-5">
        <div class="col">
            <h2>
                Data
            </h2>
            <p>
                Download data:
                <button class="btn btn-success ms-1" @click="downloadData"><i class="bi bi-filetype-xls"></i> Microsoft Excel</button>
            </p>
        </div>
    </div>
</template>

<script setup>
import {defineProps, onMounted, ref, inject} from "vue";

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
        "type": "creender",
        "sub": "exportResults",
        "id": props.values.id,
        ...updateAxiosParams()
    };
    let usp = new URLSearchParams(params).toString();
    window.open(axios.defaults.baseURL + "?" + usp).focus();
}

onMounted(function () {
    additionalUserData.value.titles = [];
    additionalUserData.value.values = [];

    infoData.value['_titles']['cr_description'] = "Question text";
    infoData.value['cr_description'] = infoData.value['_data']['type_info']['description'];
    infoData.value['_titles']['cr_comment'] = "Comment question";
    infoData.value['cr_comment'] = infoData.value['_data']['type_info']['comment'];
    infoData.value['_titles']['cr_answer'] = "Choice question";
    infoData.value['cr_answer'] = infoData.value['_data']['type_info']['answer'];
    infoData.value['_titles']['cr_annotations'] = "Ann. per instance";
    infoData.value['cr_annotations'] = infoData.value['_data']['type_info']['annotations'];
    infoData.value['_titles']['cr_choices'] = "Choices";
    infoData.value['cr_choices'] = infoData.value['_data']['type_info']['choices'].join(", ");

    infoData.value['_titles']['cr_pictures'] = "Pictures";
    let pictureList = [];
    for (let d in infoData.value['_data']['creender_datasets']) {
        if (infoData.value['_data']['type_info']['dataset_' + d]) {
            let s = infoData.value['_data']['creender_datasets'][d]['name'];
            s += " "
            s += infoData.value['_data']['type_info']['dataset_' + d];
            s += "/"
            s += infoData.value['_data']['creender_datasets'][d]['num'];
            pictureList.push(s);
        }
    }
    infoData.value['cr_pictures'] = pictureList.join(", ");

    let boolValues = {
        "do_not_ask_for_comment": "Do not ask for comment",
        "comment_is_mandatory": "The comment is mandatory",
        "no_show_question": "Show choices when user clicks 'No'",
        "no_delay": "Buttons are immediately available to users",
        "no_dblclick": "One click is enough for the user to confirm",
        "allow_multiple_choices": "Allow multiple choices"
    }
    for (let b in boolValues) {
        infoData.value['_boolTitles']['cr_' + b] = boolValues[b];
        infoData.value['cr_' + b] = infoData.value['_data']['type_info'][b] ? "Yes" : "No";
    }

    infoData.value['_titles']['cr_enable_demo_mode'] = "Enable demo mode";
    infoData.value['cr_enable_demo_mode'] = infoData.value['_data']['type_info']['enable_demo_mode'] ?
        `Yes (password: ${infoData.value['_data']['type_info']['demo_password']})` : "No";

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
        })
        .catch((reason) => {
            let debugText = reason.response.statusText + " - " + reason.response.data.error;
            showModalWindow(debugText);
        })
});

</script>

<style scoped>

</style>