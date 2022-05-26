<template>
    <span class="badge bg-warning" v-if="!e.confirmed">{{ $t("task.unconfirmed").capitalize() }}</span>
    <span class="badge bg-dark" v-else-if="e.closed">{{ $t("task.closed").capitalize() }}</span>
    <span class="badge bg-danger" v-else-if="e.disabled">{{ $t("task.disabled").capitalize() }}</span>
    <span class="badge bg-success" v-else>{{ $t("task.active").capitalize() }}</span>
</template>

<script setup>
import {defineProps} from "vue";

defineProps({
    e: Object,
});

</script>

<style scoped>

</style>