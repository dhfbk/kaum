<template>
    <span class="badge bg-warning" v-if="!p.confirmed">{{ $t("project.unconfirmed").capitalize() }}</span>
    <span class="badge bg-success" v-else-if="!p.disabled">{{ $t("project.active").capitalize() }}</span>
    <span class="badge bg-danger" v-else>{{ $t("project.disabled").capitalize() }}</span>
</template>

<script setup>
import {defineProps} from "vue";

defineProps({
    p: Object,
});

</script>

<style scoped>

</style>