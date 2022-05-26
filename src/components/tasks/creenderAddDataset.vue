<template>
    <div class="row needs-validation row-cols-md-auto g-3 align-items-center" id="formCreenderAddDataset">
        <div class="col-12 col-form-label">
            <i v-if="useSave" class="bi bi-file-earmark-plus"></i>
            <strong class="col-form-label" v-else>
                Add dataset:
            </strong>
        </div>
        <div class="col-12">
            <input v-model="values.name" :minlength="store.state.options.creender_dataset_name_minlength"
                   name="name" class="form-control" :class="{'form-control-sm': useSave, 'no-validation': useSave}"
                   type="text" placeholder="Dataset name" id="formCreenderAddDataset_name" :required="useSave ? null : true"/>
            <div class="invalid-feedback">Invalid name.</div>
        </div>
        <div class="col-12">
            <input ref="creenderFileInput" class="form-control"
                   :class="{'form-control-sm': useSave, 'no-validation': useSave}" type="file"
                   @change="handleFileUpload( fileType, $event )" id="formCreenderAddDataset_file" :required="useSave ? null : true">
            <div class="invalid-feedback">You must select a file.</div>
        </div>
        <div v-if="props.useSave" class="col-12">
            <div class="form-check">
                <input v-model="save" class="form-check-input" type="checkbox" id="creenderSaveDataset"
                       :class="{'no-validation-cb': useSave}">
                <label class="form-check-label" for="creenderSaveDataset">
                    Save dataset
                </label>
            </div>
        </div>
        <div class="col-12">
            <!--                    <button v-if="disableSubmit" class="btn btn-danger btn-lg ms-3" @click="cancel">Cancel</button>-->
            <button :disabled="disableSubmit" class="btn btn-primary" :class="{'btn-sm': useSave}" @click.prevent="addDataset">
                Submit
            </button>
            <button v-if="disableSubmit" class="btn btn-danger ms-3" :class="{'btn-sm': useSave}" @click.prevent="cancel">
                Cancel
            </button>
        </div>
    </div>
    <div class="row mt-3" v-if="disableSubmit">
        <div class="col-12">
            <div class="progress">
                <div class="progress-bar progress-bar-striped progress-bar-animated"
                     :style="{width: formLoadingPercent + '%'}"></div>
            </div>
        </div>
    </div>
</template>

<script setup>

import {defineProps, defineEmits, inject, ref} from "vue";
import {useStore} from "vuex";

const props = defineProps({
    useSave: {
        type: Boolean
    }
});

const emit = defineEmits(['updateDatasets']);

const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const disableSubmit = ref(false);
const values = ref({});
const store = useStore();
const showModalWindow = inject('showModalWindow');
const creenderFileInput = ref(null);
const formLoadingPercent = ref(0);
const abortController = ref(new AbortController());
const save = ref(true);

function cancel() {
    abortController.value.abort();
}

function handleFileUpload(type, event) {
    values.value['files'] = event.target.files;
}

function addDataset() {
    let form = document.getElementById("formCreenderAddDataset");
    form.classList.add('was-validated');

    // It's not a form any more, therefore this command does not work
    // if (!form.checkValidity()) {
    //     return;
    // }

    // Manually checking input fields
    let inputList = document.querySelectorAll("#formCreenderAddDataset input");
    for (let i = 0; i < inputList.length; i++) {
        let inputElement = inputList[i];
        if (!inputElement.checkValidity()) {
            if (props.useSave) {
                alert("Please insert both name and attachment");
            }
            return;
        }
    }

    // let i;
    // i = document.getElementById("formCreenderAddDataset_name");
    // if (!i.checkValidity()) {
    //     return;
    // }
    // i = document.getElementById("formCreenderAddDataset_file");
    // if (!i.checkValidity()) {
    //     return;
    // }

    abortController.value = new AbortController();
    const data = {
        "action": "task",
        "type": "creender",
        "sub": "addDataset",
        ...updateAxiosParams(),
        info: JSON.stringify(values.value)
    };
    if (save.value) {
        data['save'] = save.value;
    }
    let formData = new FormData();
    for (let k in data) {
        formData.append(k, data[k]);
    }

    for (let f of values.value['files']) {
        if (f.type !== "application/zip") {
            showModalWindow("Wrong file format. Please select a ZIP file");
            disableSubmit.value = false;
            return;
        }
        if (f.size > store.state.options.max_size) {
            let thisSize = (f.size / (1024 * 1024)).toFixed(2) + "M";
            let maxSize = (store.state.options.max_size / (1024 * 1024)).toFixed(2) + "M";
            showModalWindow(`File ${f.name} is too big (size: ${thisSize}; max allowed size: ${maxSize})`);
            return;
        }
        formData.append("f[]", f);
    }
    disableSubmit.value = true;
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
            if (response.data.result === "OK") {
                showModalWindow("Dataset added successfully");
                form.classList.remove('was-validated');
                values.value = {};
                creenderFileInput.value.value = "";
                emit("updateDatasets");
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
            disableSubmit.value = false;
            formLoadingPercent.value = 0;
        });
}
</script>

<style scoped>

</style>