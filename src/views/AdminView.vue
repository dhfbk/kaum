<template>
    <h1>Task management</h1>
<!--    <p>{{ adminComponents }}</p>-->
    <!--    <p>{{typeOptions}}</p>-->

    <div v-for="(c, i) in adminComponents" :key="i">
        <component :is="c"/>
    </div>
</template>

<script setup>

import {inject} from "vue";

// const typeOptions = inject('typeOptions');
const adminComponents = inject('adminComponents');

</script>

<style scoped>

</style>