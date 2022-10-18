<template>
    <img :src="`${publicPath}/img/tasks/${toolImg}.png`" :alt="toolImg" :title="toolImg"
         height="20"/>
    <span class="ms-2 badge bg-primary">{{ tool }}</span>
</template>

<script setup>
import {defineProps, onMounted, ref} from "vue";

const props = defineProps({
    tool: String,
    toolImg: String
});
const toolImg = ref("");
const publicPath = process.env.BASE_URL;

onMounted(function() {
    toolImg.value = props.tool;
    if (props.toolImg) {
        toolImg.value = props.toolImg;
    }
});

</script>

<style scoped>

</style>