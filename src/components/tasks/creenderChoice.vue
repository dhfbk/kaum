<template>
    <div class="col-12 col-md-6 mb-3">
        <div class="row row-cols-lg-auto g-3 align-items-center">
            <div class="col-auto">
                <label for="inputChoice" class="col-form-label">Add choice:</label>
            </div>
            <div class="col-auto">
                <input v-model="newChoice" @keydown.enter.prevent="addChoice()" type="text" id="inputChoice" class="form-control no-validation">
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
        <ul v-if="choices.length > 0" class="list-group">
            <li v-for="(choice, index) in choices" :key="index" class="list-group-item d-flex justify-content-between align-items-start">
                <div>
                    {{ choice }}
                </div>
                <div>
                    <a href="#" @click.prevent="moveUp(index)" class="ms-2 badge bg-primary rounded-pill"><i class="bi bi-arrow-up"></i></a>
                    <a href="#" @click.prevent="moveDown(index)" class="ms-2 badge bg-primary rounded-pill"><i class="bi bi-arrow-down"></i></a>
                    <a href="#" @click.prevent="deleteItem(index)" class="ms-2 badge bg-danger rounded-pill"><i class="bi bi-trash"></i></a>
                </div>
            </li>
        </ul>
        <p v-else>
            The list is empty
        </p>
    </div>
</template>

<script setup>

import {defineProps, ref} from "vue";

const props = defineProps({
    choices: {
        type: Object
    }
});
const choices = ref(props.choices);
const newChoice = ref("");

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

</script>

<style scoped>

.was-validated .no-validation:focus {
    color: #212529;
    background-color: #fff;
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgb(13 110 253 / 25%);
}

.was-validated .no-validation:valid,
.was-validated .no-validation:invalid{
    border-color: #212529;
    background: none;
    padding: .375rem .75rem;
}
</style>