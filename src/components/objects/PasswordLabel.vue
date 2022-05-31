<template>
    <div class="input-group">
        <input v-if="showPassword" type="text" class="form-control" v-model="password" :id="props.id"/>
        <input v-else type="password" class="form-control" v-model="password" :id="props.id">
        <button class="btn btn-outline-secondary btn-grey" type="button" @click="toggle">
            <i class="bi" :class="{'bi-eye' : !showPassword, 'bi-eye-slash': showPassword}"></i>
        </button>
    </div>
</template>

<script setup>
import {defineProps, ref} from "vue";

const props = defineProps({"password": String, "id": String});
const password = ref(props.password);
const showPassword = ref(false);

function toggle() {
    showPassword.value = !showPassword.value;
}

</script>

<style scoped>

</style>