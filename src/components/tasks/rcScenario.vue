<template>
    <form class="container" novalidate @submit.stop.prevent="submit">
        <div class="row mt-3">
            <div class="col-md-12">
                <label class="form-label" for="scenarioTitle">Title:</label>
                <input v-model="values['title']" :minlength="3"
                       name="scenariotitle" class="form-control"
                       id="scenarioTitle" type="text" placeholder="Scenario title" required/>
                <div class="invalid-feedback">Scenario title must be at least 3 characters long.</div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col">
                <label class="form-label" for="descriptionArea">Initial description of the activity:</label>
                <textarea v-model="values['description']"
                          name="description" class="form-control"
                          id="descriptionArea" rows="5"
                          placeholder="Initial description of the activity" required>
                    </textarea>
                <div class="invalid-feedback">Description required.</div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-md-3">
                <label class="form-label" for="scenarioLang">Language:</label>
                <select class="form-select" v-model="values['lang']" id="scenarioLang" required>
                    <option value="">[Select]</option>
                    <option v-for="(lang, i) in langs" :key="`Lang${i}`" :value="lang">
                        {{ lang }}
                    </option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="scenarioSchool">School:</label>
                <select class="form-select" v-model="values['school']" id="scenarioSchool" required>
                    <option value="">[Select]</option>
                    <option v-for="(value, k) in schools" :key="k" :value="k">{{ value }}</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="channelName">Channel name (letters, numbers and dashes):</label>
                <input v-model="values['channel_name']" :minlength="3"
                       name="channelname" class="form-control"
                       id="channelName" type="text" placeholder="Channel name" required/>
                <div class="invalid-feedback">Task name must be at least 3 characters long.</div>
            </div>
        </div>
        <div class="row mt-3">
            <div class="col-12 text-end">
                <button :disabled="isLoading" class="btn btn-primary btn-lg" type="submit">
                    Submit
                </button>
            </div>
        </div>
    </form>
</template>

<script setup>

import {defineProps, ref, defineEmits, inject, onMounted} from "vue";
import i18n from "@/i18n";

const props = defineProps({
    values: {
        type: Object
    },
    schools: {
        type: Object
    }
});
const values = ref({});
const isLoading = ref(false);
const langs = i18n.global.availableLocales;
const emit = defineEmits(['update']);
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const showModalWindow = inject('showModalWindow');

function submit(event) {
    let form = event.srcElement;
    form.classList.add('was-validated')
    if (!form.checkValidity()) {
        return;
    }

    // emit('submit', values.value);
    newScenario(values.value, form);
}

function newScenario(v, form) {
    isLoading.value = true;
    const data = {
        "action": "task",
        "type": "rc",
        "sub": "addScenario",
        ...updateAxiosParams(),
        data: v
    };
    axios.post("?", data)
        .then((response) => {
            if (response.data.result === "OK") {
                // values.value = {"lang": "", "school": 2};
                showModalWindow("Scenario added successfully");
                emit("update");
                values.value = {...props.values};
                form.classList.remove('was-validated');
                // form.classList.remove('was-validated');
                // choices.value = [];
                // newChoice.value = "";
                // newChoiceName.value = "";
                // emit("updateChoices");
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
            isLoading.value = false;
        });
}

onMounted(async function () {
    values.value = {...props.values};
});


</script>

<style scoped>

</style>