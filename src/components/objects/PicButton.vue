<template>
    <button :class="classes" type="button"
            class="me-3 btn btn-sm" :title="text" :disabled="props.disabled">
        <i class="bi" :class="'bi-' + props.icon"></i>
        <span class="d-none d-lg-inline">&nbsp;{{ text }}</span>
    </button>
</template>

<script setup>
import {defineProps, ref} from "vue";
import {toRefs} from "vue";

const props = defineProps({
    text: String,
    color: String,
    disabled: Boolean,
    icon: String
});

const propValues = toRefs(props);

const classes = ref({});
// classes.value['disabled'] = propValues.disabled.value;
classes.value['btn-' + propValues.color.value] = true;

</script>

<style scoped>

</style>