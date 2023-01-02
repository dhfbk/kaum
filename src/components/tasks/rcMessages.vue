<template>
    <ul class="list-group">
        <li class="list-group-item text-black">
            <div class="row d-none d-lg-flex">
                <div class="col-4 col-lg-2">
                    Time
                </div>
                <div class="col-4 col-lg-2">
                    User
                </div>
                <div class="col-4 col-lg-2">
                    Message ID
                </div>
                <div class="col-12 col-lg-6">
                    Text
                </div>
            </div>
            <div class="d-lg-none">
                Info
            </div>
        </li>
        <li v-for="(message, i) in props.messages" :key="i" class="list-group-item">
            <div class="row">
                <div class="col-4 col-lg-2">
                    {{ getGoodDate(message.ts) }}
                </div>
                <div class="col-4 col-lg-2">
                    <code>{{ message.username }}</code><span v-if="message.name"> - {{message.name}}</span>
                </div>
                <div class="col-4 col-lg-2">
                    <code>{{ message.id }}</code>
                </div>
                <div class="col-12 col-lg-6">
                    {{ message.text }}
                </div>
            </div>
        </li>
    </ul>
</template>

<script setup>
import {defineProps} from "vue";

const props = defineProps({
    messages: Array
});

function getGoodDate(value) {
    const date = new Date(Date.parse(value));
    return date.toLocaleString();
}

</script>

<style scoped>

</style>