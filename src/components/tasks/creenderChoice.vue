<template>
    <div class="col-12 col-md-6 mb-3">
        <div class="row row-cols-lg-auto g-3 align-items-center mt-1">
            <div class="col-auto">
                <label for="inputChoiceClone" class="col-form-label">Clone from:</label>
            </div>
            <div class="col-auto">
                <select v-model="cloneID" id="inputChoiceClone" class="form-control no-validation"
                        @change="loadToClone()">
                    <option value="0">[Select]</option>
                    <option v-for="cl in list" :key="cl.id" :value="cl.id">{{ cl.name }}</option>
                </select>
            </div>
        </div>

        <div class="row row-cols-lg-auto g-3 align-items-center mt-1" v-if="useButton" id="formCreenderAddChoice">
            <div class="col-auto">
                <label for="inputChoiceName" class="col-form-label">List name:</label>
            </div>
            <div class="col-auto">
                <input v-model="newChoiceName" type="text" id="inputChoiceName"
                       class="form-control" required
                       :minlength="store.state.options.creender_choicelist_name_minlength">
                <div class="invalid-feedback">It must be at least
                    {{ store.state.options.creender_choicelist_name_minlength }} chars long.
                </div>
            </div>
            <div class="col-auto">
                <button :disabled="disableSubmit" class="btn btn-primary" @click.prevent="addList">
                    Save
                </button>
            </div>
        </div>

        <template v-else>
            <div class="row row-cols-lg-auto g-3 align-items-center mt-1">
                <div class="col-auto">
                    <div class="form-check">
                        <input v-model="saveValues.save" class="form-check-input no-validation-cb" type="checkbox"
                               id="creenderSaveChoice">
                        <label class="form-check-label" for="creenderSaveChoice">
                            Save dataset
                        </label>
                    </div>
                </div>
            </div>

            <div class="row row-cols-lg-auto g-3 align-items-center mt-1" v-if="saveValues.save">
                <div class="col-auto">
                    <label for="inputChoiceName" class="col-form-label">New list name:</label>
                </div>
                <div class="col-auto">
                    <input v-model="saveValues.name" type="text" id="inputChoiceName"
                           class="form-control" required
                           :minlength="store.state.options.creender_choicelist_name_minlength">
                    <div class="invalid-feedback">It must be at least
                        {{ store.state.options.creender_choicelist_name_minlength }} chars long.
                    </div>
                </div>
            </div>
        </template>

    </div>
    <div class="col-12 col-md-6 mb-3">
        <div class="row row-cols-lg-auto g-3 align-items-center mb-3 mt-1">
            <div class="col-auto">
                <label for="inputChoice" class="col-form-label">Add choice (<i class="bi bi-arrow-return-left"></i> to
                    add):</label>
            </div>
            <div class="col-auto">
                <input v-model="newChoice" @keydown.enter.prevent="addChoice()" type="text" id="inputChoice"
                       class="form-control no-validation">
            </div>
        </div>
        <ul v-if="choices.length > 0" class="list-group">
            <li v-for="(choice, index) in choices" :key="index"
                class="list-group-item d-flex justify-content-between align-items-start">
                <div>
                    {{ choice }}
                </div>
                <div>
                    <a href="#" @click.prevent="moveUp(index)" class="ms-2 badge bg-primary rounded-pill"><i
                        class="bi bi-arrow-up"></i></a>
                    <a href="#" @click.prevent="moveDown(index)" class="ms-2 badge bg-primary rounded-pill"><i
                        class="bi bi-arrow-down"></i></a>
                    <a href="#" @click.prevent="deleteItem(index)" class="ms-2 badge bg-danger rounded-pill"><i
                        class="bi bi-trash"></i></a>
                </div>
            </li>
        </ul>
        <div class="alert alert-warning" v-else>
            The list is empty
        </div>
    </div>
</template>

<script setup>

import {defineProps, ref, inject, defineEmits} from "vue";
import {useStore} from "vuex";

const props = defineProps({
    choices: {
        type: Object
    },
    list: {
        type: Object
    },
    useButton: {
        type: Boolean
    },
    saveValues: {
        type: Object
    }
});
const choices = ref(props.choices);
const saveValues = ref(props.saveValues);

const newChoice = ref("");
const cloneID = ref(0);
const newChoiceName = ref("");
const disableSubmit = ref(false);
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const showModalWindow = inject('showModalWindow');
const store = useStore();
const emit = defineEmits(['updateChoices']);

function loadToClone() {
    choices.value.splice(0);
    for (let index in props.list[cloneID.value].data) {
        choices.value.push(props.list[cloneID.value].data[index]);
    }
    cloneID.value = 0;
}

function addChoice() {
    // console.log(newChoice.value);
    if (newChoice.value.trim().length === 0) {
        return;
    }
    choices.value.push(newChoice.value.trim());
    newChoice.value = "";
}

function deleteItem(index) {
    delete choices.value.splice(index, 1);
}

function switchItems(index1, index2) {
    let tmp = choices.value[index1];
    choices.value[index1] = choices.value[index2];
    choices.value[index2] = tmp;
}

function moveUp(index) {
    if (index <= 0) {
        return;
    }
    switchItems(index, index - 1);
}

function moveDown(index) {
    if (index >= choices.value.length - 1) {
        return;
    }
    switchItems(index, index + 1);
}

function addList() {
    let form = document.getElementById("formCreenderAddChoice");
    form.classList.add('was-validated');

    // It's not a form any more, therefore this command does not work
    // if (!form.checkValidity()) {
    //     return;
    // }

    // Manually checking input fields
    let inputList = document.querySelectorAll("#formCreenderAddChoice input");
    for (let i = 0; i < inputList.length; i++) {
        let inputElement = inputList[i];
        if (!inputElement.checkValidity()) {
            return;
        }
    }

    const data = {
        "action": "task",
        "type": "creender",
        "sub": "addChoice",
        ...updateAxiosParams(),
        data: {
            name: newChoiceName.value,
            choices: choices.value
        }
    };
    disableSubmit.value = true;
    axios.post("?", data)
        .then((response) => {
            if (response.data.result === "OK") {
                showModalWindow("Choice list added successfully");
                form.classList.remove('was-validated');
                choices.value = [];
                newChoice.value = "";
                newChoiceName.value = "";
                emit("updateChoices");
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
        });
}
</script>

<style scoped>

</style>