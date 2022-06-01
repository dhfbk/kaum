<template>
        <p>{{ values }}</p>
    <div class="row">
        <div class="col-12">
            <div class="alert alert-warning">
                The task information cannot be updated at a later time.
            </div>
        </div>
        <!--        <div class="col-md-6 mb-3">-->
        <!--            <label class="form-label" for="annotationsPerInstance">Annotations per instance:</label>-->
        <!--            <input v-model="values.type_info['annotations']" name="annotations" class="form-control"-->
        <!--                   id="annotationsPerInstance"-->
        <!--                   type="number" min="1" :max="values.students"-->
        <!--                   placeholder="Number of annotations" required/>-->
        <!--            <div class="invalid-feedback">Number of annotations is required and must be > 0.</div>-->
        <!--        </div>-->
        <!--        <div class="col-md-6 mb-3">-->
        <!--            <label class="form-label" for="photosPerEducator">Photos in educator profile:</label>-->
        <!--            <input v-model="values.type_info['photos_educator']" name="photos_educator" class="form-control"-->
        <!--                   id="photosPerEducator"-->
        <!--                   type="number" min="1"-->
        <!--                   placeholder="Number of photos for educator profiles" required/>-->
        <!--            <div class="invalid-feedback">Number of photos for educator is required and must be > 0.</div>-->
        <!--        </div>-->
        <div class="col-12 col-md-6 mb-3">
            <label class="form-label" for="descriptionArea">Question text:</label>
            <textarea v-model="values.type_info['description']"
                      name="description" class="form-control"
                      id="descriptionArea" rows="3"
                      required>
            </textarea>
            <div class="invalid-feedback">Question text required.</div>
        </div>
        <div class="col-12 col-md-6 mb-3">
            <label class="form-label" for="answerArea">Choice question:</label>
            <textarea v-model="values.type_info['answer']"
                      name="answer" class="form-control"
                      id="answerArea" rows="3"
                      required>
            </textarea>
            <div class="invalid-feedback">Choice question required.</div>
        </div>
        <div class="col-12 col-md-6 mb-3">
            <div class="mb-3">
                <label class="form-label" for="commentArea">Comment question:</label>
                <textarea v-model="values.type_info['comment']"
                          name="comment" class="form-control"
                          id="commentArea" rows="3"
                          required>
                </textarea>
                <div class="invalid-feedback">Comment question required.</div>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" v-model="values.type_info['do_not_ask_for_comment']"
                       id="commentCheck">
                <label class="form-check-label" for="commentCheck">
                    Do not ask for a comment
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox"
                       :disabled="values.type_info['do_not_ask_for_comment']"
                       v-model="values.type_info['comment_is_mandatory']" id="commentMandatoryCheck">
                <label class="form-check-label" for="commentMandatoryCheck">
                    The comment is mandatory
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" v-model="values.type_info['no_show_question']"
                       id="noCheck">
                <label class="form-check-label" for="noCheck">
                    Show choices when user clicks 'No'
                </label>
            </div>
        </div>

        <div class="col-12 col-md-6 mb-3">
            <div class="mb-3">
                <label class="form-label" for="annotationsPerInstance">Annotations per instance:</label>
                <input v-model="values.type_info['annotations']" name="annotations" class="form-control"
                       id="annotationsPerInstance"
                       type="number" min="1" :max="values.students"
                       placeholder="Number of annotations" required/>
                <div class="invalid-feedback">Number of annotations is required and must be > 0.</div>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" v-model="values.type_info['no_delay']"
                       id="noDelayCheck">
                <label class="form-check-label" for="noDelayCheck">
                    Buttons are immediately available to users
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" v-model="values.type_info['no_dblclick']"
                       id="noDblClickCheck">
                <label class="form-check-label" for="noDblClickCheck">
                    One click is enough for the user to confirm
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" v-model="values.type_info['allow_multiple_choices']"
                       id="allowMultipleChoices">
                <label class="form-check-label" for="allowMultipleChoices">
                    Allow multiple choices
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" v-model="values.type_info['enable_demo_mode']"
                       id="enableDemo">
                <label class="form-check-label" for="enableDemo">
                    Enable demo mode
                </label>
            </div>

            <div v-if="values.type_info['enable_demo_mode']" class="row row-cols-lg-auto g-3 align-items-center mt-1">
                <div class="col-auto">
                    <label for="demoPassword" class="col-form-label">Password:</label>
                </div>
                <div class="col-auto">
                    <password-label id="demoPassword" :password="values.type_info['demo_password']"
                                    @change="updatePassword"></password-label>
                </div>
            </div>

        </div>

        <div class="col-12 mt-3 mb-1">
            <hr/>
            <h5>Choices</h5>
        </div>

        <creender-choice :use-button="false" :choices="values.type_info['choices']"
                         :list="choiceList" :save-values="values.type_info['save_values']"></creender-choice>

        <div class="col-12 mt-3 mb-1">
            <hr/>
            <h5>Choose photos</h5>
            <div v-if="!creenderLoading">
                <table class="table" v-if="Object.keys(datasets).length > 0">
                    <thead>
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">Name</th>
                        <th scope="col">Photos</th>
                        <th scope="col">Amount to use</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(dataset, index) in datasets" :key="index">
                        <td>
                            <div class="col-form-label">{{ index }}</div>
                        </td>
                        <td>
                            <div class="col-form-label">
                                {{ dataset.name }}
                            </div>
                        </td>
                        <td>
                            <div class="col-form-label">
                                {{ dataset.num }}
                            </div>
                        </td>
                        <td>
                            <div class="input-group">
                                <input v-model="values.type_info['dataset_' + index]" class="form-control"
                                       type="number" min="0" :max="dataset.num"
                                       required/>
                                <button class="btn btn-grey" type="button"
                                        @click="values.type_info['dataset_' + index] = 0">
                                    <i class="bi bi-x"></i>
                                </button>
                                <button class="btn btn-grey" type="button"
                                        @click="values.type_info['dataset_' + index] = dataset.num">
                                    <i class="bi bi-check-all"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    </tbody>
                </table>
                <p v-else>
                    No datasets
                </p>
            </div>
            <div class="row" v-else>
                <div class="col">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                </div>
            </div>

            <CreenderAddDataset v-if="!underValidation" :useSave="true"
                                @updateDatasets="updateDatasets()"></CreenderAddDataset>
        </div>
    </div>
</template>

<script setup>

import {onMounted, inject, ref, defineProps, onBeforeMount} from "vue";
import {useRoute} from "vue-router";
import {useStore} from "vuex";
import CreenderChoice from "@/components/tasks/creenderChoice";
import CreenderAddDataset from "@/components/tasks/creenderAddDataset";
import PasswordLabel from "@/components/objects/PasswordLabel";

const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const route = useRoute();
const creenderLoading = ref(true);
const datasets = ref({});
const store = useStore();
const choiceList = ref([]);

const props = defineProps({
    values: {
        type: Object
    },
    underValidation: {
        type: Boolean
    }
});
const values = ref(props.values);

// console.log("Loaded!");

function updatePassword(newPassword) {
    values.value.type_info['demo_password'] = newPassword;
    // console.log(newPassword);
}

async function updateChoices() {
    await axios.get("?", {
        "params": {
            "action": "task",
            "sub": "listChoices",
            "project_id": route.params.id,
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
            // creenderLoading.value = false;
        });

}

async function updateDatasets() {
    creenderLoading.value = true;
    await axios.get("?", {
        "params": {
            "action": "task",
            "sub": "listDatasets",
            "type": "creender",
            "project_id": route.params.id,
            ...updateAxiosParams()
        }
    })
        .then((response) => {
            // console.log(response.data);
            datasets.value = response.data.datasets;
            for (let k in datasets.value) {
                if (values.value.type_info['dataset_' + k] === undefined) {
                    values.value.type_info['dataset_' + k] = 0;
                }
            }
        })
        .catch((reason) => {
            console.log(reason);
        })
        .then(() => {
            creenderLoading.value = false;
        });
}

onBeforeMount(function () {
    if (!values.value['type_info']['choices']) {
        values.value['type_info']['choices'] = [];
    }
    if (!values.value['type_info']['comment']) {
        values.value['type_info']['comment'] = store.state.options.creender_default_comment;
    }
    if (!values.value['type_info']['answer']) {
        values.value['type_info']['answer'] = store.state.options.creender_default_answer;
    }
    if (!values.value['type_info']['description']) {
        values.value['type_info']['description'] = store.state.options.creender_default_question;
    }
    // if (!values.value['type_info']['photos_educator']) {
    //     values.value['type_info']['photos_educator'] = store.state.options.creender_photos_educator;
    // }
    if (!values.value['type_info']['annotations']) {
        values.value['type_info']['annotations'] = store.state.options.task_default_annotations;
    }
    if (!values.value['type_info']['save_values']) {
        values.value['type_info']['save_values'] = {save: false, name: ""};
    }
})

onMounted(async function () {
    // for (let t in fileTypes) {
    //     values.value['type_info']['save_' + t] = true;
    //     if (!values.value['type_info']['dataset_' + t]) {
    //         values.value['type_info']['dataset_' + t] = "";
    //     }
    //     if (!values.value['type_info']['annotations']) {
    //         values.value['type_info']['annotations'] = store.state.options.task_default_annotations;
    //     }
    // }

    await updateDatasets();
    await updateChoices();
    // creenderLoading.value = false;
})
</script>

<style>
</style>
