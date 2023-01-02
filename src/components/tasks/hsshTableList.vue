<template>
    <p v-if="Object.keys(props['datasetList']).length === 0">
        No datasets yet
    </p>
    <table class="table" v-else>
        <thead>
        <tr>
            <th scope="col">#</th>
            <th v-if="props.form" scope="col"><i class="bi bi-check2-square"></i></th>
            <th scope="col">Name</th>
            <th scope="col">Lang</th>
            <th scope="col">Num</th>
            <th v-if="!props.form" scope="col">Actions</th>
        </tr>
        </thead>
        <tbody>
        <tr v-for="d in props['datasetList']" :key="d.id" class="align-middle">
            <th scope="row">{{ d.id }}</th>
            <td v-if="props.form">
                <input class="form-check-input" type="checkbox" v-model="selections[d.id]">
            </td>
            <td>{{ d.title }}</td>
            <td>{{ d.lang }}</td>
            <td>{{ d.num }}</td>
            <td v-if="!props.form">
                <PicButton @click="deleteDataset(d.id)" color="danger" icon="trash"/>
            </td>
        </tr>
        </tbody>
    </table>
</template>

<script setup>

import {defineProps, defineEmits, computed} from "vue";
import PicButton from "@/components/objects/PicButton";

const props = defineProps({
    modelValue: {
        type: Object,
        "default": {}
    },
    datasetList: {
        type: Object
    },
    form: {
        type: Boolean
    }
});

const emit = defineEmits(['deleteDatasets', 'update:modelValue']);
const selections = computed({
    get: () => props.modelValue,
    set: (value) => emit('update:modelValue', value)
});
function deleteDataset(id) {
    emit("deleteDataset", id);
}

</script>

<style scoped>

</style>