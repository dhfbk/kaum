<template>
    <div class="row needs-validation g-3 align-items-center" id="formHsshAddDataset">
        <div class="col-12">
            <h5>
                Add dataset
            </h5>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label"
                   :class="{'col-form-label-sm': props.disableValidation}"
                   for="formHsshAddDataset_name">Name:</label>
            <input v-model="values.name" :minlength="store.state.options.hssh_dataset_name_minlength"
                   name="name" class="form-control"
                   :class="{'form-control-sm': props.disableValidation, 'no-validation': props.disableValidation}"
                   type="text" placeholder="Dataset name" id="formHsshAddDataset_name"
                   :required="props.disableValidation ? null : true"/>
            <div class="invalid-feedback">Invalid name.</div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label"
                   :class="{'col-form-label-sm': props.disableValidation}"
                   for="formHsshAddDataset_lang">Language:</label>
            <select class="form-select" v-model="values['lang']"
                    :class="{'form-select-sm': props.disableValidation, 'no-validation': props.disableValidation}"
                    id="formHsshAddDataset_lang"
                    :required="props.disableValidation ? null : true">
                <option value="">[Select]</option>
                <option v-for="(lang, i) in langs" :key="`Lang${i}`" :value="lang">
                    {{ lang }}
                </option>
            </select>
            <div class="invalid-feedback">Language is mandatory.</div>
        </div>
        <div class="col-12 col-md-6">
            <label class="form-label"
                   :class="{'col-form-label-sm': props.disableValidation}"
                   for="formHsshAddDataset_file">File:</label>
            <input ref="hsshFileInput" class="form-control" type="file"
                   :class="{'form-control-sm': props.disableValidation, 'no-validation': props.disableValidation}"
                   @change="handleFileUpload($event)"
                   id="formHsshAddDataset_file"
                   :required="props.disableValidation ? null : true">
            <div class="invalid-feedback">You must select a file.</div>
        </div>
        <div class="col-8 col-md-4">
            <label class="form-label"
                   :class="{'col-form-label-sm': props.disableValidation}">Category:</label>
            <div>
                <div class="form-check form-check-inline">
                    <input v-model="values.type" class="form-check-input"
                           type="radio" name="inlineRadioOptions"
                           :class="{'no-validation': props.disableValidation}"
                           id="inlineRadio1" value="gr"
                           :required="props.disableValidation ? null : true">
                    <label class="form-check-label"
                           :class="{'col-form-label-sm': props.disableValidation}"
                           for="inlineRadio1">Graffiti</label>
                </div>
                <div class="form-check form-check-inline">
                    <input v-model="values.type" class="form-check-input"
                           type="radio" name="inlineRadioOptions"
                           :class="{'no-validation': props.disableValidation}"
                           id="inlineRadio2" value="ch"
                           :required="props.disableValidation ? null : true">
                    <label class="form-check-label"
                           :class="{'col-form-label-sm': props.disableValidation}"
                           for="inlineRadio2">Dialogues</label>
                </div>
            </div>
        </div>
        <div class="col-4 col-md-2 text-end align-self-end">
            <button :disabled="disableSubmit" class="btn btn-primary" @click.prevent="addDataset">
                Submit
            </button>
        </div>
    </div>

</template>

<script setup>

import {ref, inject, defineEmits, defineProps} from "vue";
import {useStore} from "vuex";
import i18n from "@/i18n";

const emit = defineEmits(['updateDatasets']);

const inputValues = {lang: ""};

const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const disableSubmit = ref(false);
const values = ref({...inputValues});
const store = useStore();
const showModalWindow = inject('showModalWindow');
const hsshFileInput = ref(null);
const save = ref(true);
const langs = i18n.global.availableLocales;
const props = defineProps({
    disableValidation: {
        type: Boolean
    }
});

function handleFileUpload(event) {
    values.value['files'] = event.target.files;
}

function addDataset() {
    let form = document.getElementById("formHsshAddDataset");
    form.classList.add('was-validated');

    let inputList = document.querySelectorAll("#formHsshAddDataset input");
    for (let i = 0; i < inputList.length; i++) {
        let inputElement = inputList[i];
        if (!inputElement.checkValidity()) {
            if (props.disableValidation) {
                alert("All fields are mandatory, check again");
            }
            return;
        }
    }

    const data = {
        "action": "task",
        "type": "hssh",
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
        formData.append("f[]", f);
    }
    disableSubmit.value = true;
    axios.post("?", formData, {
        headers: {
            'Content-Type': 'multipart/form-data'
        }
    })
        .then((response) => {
            if (response.data.result === "OK") {
                showModalWindow("Dataset added successfully");
                form.classList.remove('was-validated');
                values.value = {...inputValues};
                hsshFileInput.value.value = "";
                emit("updateDatasets");
            } else {
                showModalWindow(response.data.error);
            }
        })
        .catch((reason) => {
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
        });
}

</script>

<style scoped>

</style>