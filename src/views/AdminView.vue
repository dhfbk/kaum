<template>
    <h1 class="display-1 mt-3 mb-3">Task management</h1>

    <div class="accordion" id="accordionAdmin">

        <template v-for="(c, i) in adminComponents" :key="i">
            <component :is="c"/>
        </template>
    </div>
</template>

<script setup>

import {inject} from "vue";

// const typeOptions = inject('typeOptions');
const adminComponents = inject('adminComponents');

</script>

<style scoped>

</style>