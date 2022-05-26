<template>
    <button :class="classes" type="button"
            class="btn btn-sm" :title="text" :disabled="props.disabled">
        <i class="bi" :class="'bi-' + props.icon"></i>
        <span class="ms-2 d-none" :class="[alwaysText ? 'd-sm-inline' : 'd-lg-inline']">{{ text }}</span>
    </button>
</template>

<script setup>
import {defineProps, onMounted, ref} from "vue";

const props = defineProps({
    text: String,
    color: String,
    disabled: Boolean,
    icon: String,
    alwaysText: Boolean,
    noMargin: Boolean
});

const classes = ref({});
// classes.value['disabled'] = propValues.disabled.value;

onMounted(function () {
    classes.value['btn-' + props.color] = true;
    classes.value['me-3'] = !props.noMargin;
});
</script>

<style scoped>

</style>