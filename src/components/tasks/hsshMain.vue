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

import {defineProps, inject} from "vue";

const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');

const props = defineProps({
    values: Object
});

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

</script>

<style scoped>

</style>