<template>
    <div class="row mt-5">
        <div class="col">
            <h2>
                SOS calls
            </h2>
            <p v-if="!values.data.type_info.sos_info">No SOS calls.</p>
            <ul v-else>
                <li v-for="(info, i) in values.data.type_info.sos_info" :key="i">
                    {{ getGoodDate(info.datetime) }} from <code>{{ info.username }}</code>
                </li>
            </ul>
        </div>
    </div>
    <div class="row mt-5">
        <div class="col">
            <h2>
                Chat text
            </h2>
            <p v-if="!rcLoaded">Loading...</p>
            <template v-else>
                <p v-if="messages.length === 0">{{ noMessages }}</p>
                <ul v-else class="list-group">
                    <li v-for="(message, i) in messages" :key="i" class="list-group-item">
                        <div class="row">
                            <div class="col-12 col-md-5 col-lg-4 text-end">
                                {{ getGoodDate(message.ts) }} - <code>{{ message.username }}</code>
                            </div>
                            <div class="col-12 col-md-7 col-lg-8">
                                {{ message.text }}
                            </div>
                        </div>
                    </li>
                </ul>
            </template>
        </div>
    </div>
</template>

<script setup>

import {inject, defineProps, onMounted, ref} from "vue";
import {useStore} from "vuex";
// import {useRoute} from "vue-router";

const props = defineProps({
    infoData: Object,
    "values": {
        type: Object
    }
});
const values = ref(props.values);
// const route = useRoute();
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const messages = ref([]);
const rcLoaded = ref(false);
const store = useStore();
const noMessages = ref("No messages");

const infoData = ref(props.infoData);

function getGoodDate(value) {
    const date = new Date(Date.parse(value));
    return date.toLocaleString();
}

onMounted(async function () {
    infoData.value['_titles']['rc_description'] = "Description";
    infoData.value['rc_description'] = infoData.value['_data']['type_info']['description'];
    infoData.value['_titles']['cr_channel_name'] = "Channel name";
    infoData.value['cr_channel_name'] = infoData.value['_data']['type_info']['channel_name'];

    infoData.value['_boolTitles']['cr_teacher_can_join'] = "Educators can always enter the chat";
    infoData.value['cr_teacher_can_join'] = infoData.value['_data']['type_info']['teacher_can_join'] ? "Yes" : "No";

    if (store.state.loggedAdmin || values.value.closed) {
        axios.get("?", {
            "params": {
                "action": "task",
                "type": "rc",
                "sub": "chat",
                "project_id": values.value.project_id,
                "id": values.value.id,
                ...updateAxiosParams()
            }
        })
            .then(async (response) => {
                messages.value = response.data.messages;
            })
            .finally(() => {
                rcLoaded.value = true;
            });
    } else {
        noMessages.value = "Task must be closed to get chat texts.";
        rcLoaded.value = true;
    }
})

</script>

<style scoped>

</style>