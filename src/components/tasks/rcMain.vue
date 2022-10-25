<template>
    <div class="row mt-5">
        <div class="col">
            <h2>
                SOS calls
            </h2>
            <ul v-if="sos_calls.length">
                <li v-for="(info, i) in sos_calls" :key="i">
                    {{ getGoodDate(info.datetime) }} from <code>{{ info.username }}</code> in group {{ info.group }}
                </li>
            </ul>
            <p v-else>No SOS calls.</p>
        </div>
    </div>
    <div class="row mt-5">
        <div class="col">
            <h2>
                Chat text
            </h2>
            <p v-if="!rcLoaded">Loading...</p>
            <template v-else>
                <div v-if="messages.length > 0">
                    <ul class="list-group">
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
                </div>
                <div v-else-if="groups.length > 0">
                    <template v-for="(g, i) in groups" :key="'group-' + i">
                        <h3 class="mt-3">Group {{ i + 1 }}</h3>
                        <ul class="list-group">
                            <li v-for="(message, i) in g" :key="i" class="list-group-item">
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
                <p v-else>{{ noMessages }}</p>
            </template>
        </div>
    </div>
</template>

<script setup>
/* eslint-disable */
import {inject, defineProps, onMounted, ref} from "vue";
import {useStore} from "vuex";
// import {useRoute} from "vue-router";

const props = defineProps({
    additionalData: Object,
    infoData: Object,
    values: Object
});
const values = ref(props.values);
// const route = useRoute();
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const messages = ref([]);
const groups = ref([]);
const rcLoaded = ref(false);
const store = useStore();
const noMessages = ref("No messages");

const sos_calls = ref([]);

const infoData = ref(props.infoData);
const additionalUserData = ref(props.additionalData);

function getGoodDate(value) {
    const date = new Date(Date.parse(value));
    return date.toLocaleString();
}

onMounted(async function () {
    additionalUserData.value.titles = [];
    additionalUserData.value.values = [];

    let groupsNo = 1;
    if (infoData.value['_data']['type_info']['rc_groups']) {
        groupsNo = infoData.value['_data']['type_info']['rc_groups'];
    }
    infoData.value['_titles']['rc_groups'] = "Groups";
    infoData.value['rc_groups'] = groupsNo;

    if (groupsNo > 1 && infoData.value['_data']['type_info']['rc_uniqueScenario'] != 1) {
        for (let g in infoData.value['_data']['type_info']['rc_groups_info']) {
            let groupStr = parseInt(g) + 1;
            infoData.value['_titles']['rc_description_' + groupStr] = `Description (${groupStr})`;
            infoData.value['rc_description_' + groupStr] = infoData.value['_data']['type_info']['rc_groups_info'][g]['description'];
            infoData.value['_titles']['cr_channel_name_' + groupStr] = `Channel name (${groupStr})`;
            infoData.value['cr_channel_name_' + groupStr] = infoData.value['_data']['type_info']['rc_groups_info'][g]['channel_name'];
            infoData.value['_boolTitles']['cr_teacher_can_join_' + groupStr] = `Educators can always enter the chat (${groupStr})`;
            infoData.value['cr_teacher_can_join_' + groupStr] = infoData.value['_data']['type_info']['rc_groups_info'][g]['teacher_can_join'] ? "Yes" : "No";
        }
    }
    else {
        infoData.value['_titles']['rc_description'] = "Description";
        infoData.value['rc_description'] = infoData.value['_data']['type_info']['description'];
        infoData.value['_titles']['cr_channel_name'] = "Channel name";
        infoData.value['cr_channel_name'] = infoData.value['_data']['type_info']['channel_name'];
        infoData.value['_boolTitles']['cr_teacher_can_join'] = "Educators can always enter the chat";
        infoData.value['cr_teacher_can_join'] = infoData.value['_data']['type_info']['teacher_can_join'] ? "Yes" : "No";
    }

    if (values.value['data']['type_info']['sos_info']) {
        for (let k in values.value['data']['type_info']['sos_info']) {
            let c = values.value['data']['type_info']['sos_info'][k];
            c['group'] = 1;
            sos_calls.value.push(c);
        }
    } else if (values.value['data']['type_info']['rc_groups_info']) {
        for (let g in values.value['data']['type_info']['rc_groups_info']) {
            for (let k in values.value['data']['type_info']['rc_groups_info'][g]['sos_info']) {
                let c = values.value['data']['type_info']['rc_groups_info'][g]['sos_info'][k];
                c['group'] = parseInt(g) + 1;
                sos_calls.value.push(c);
            }
        }
    }

    // console.log(infoData.value['_data']['type_info']['rc_user_channels']);
    let studentGroups = {};
    for (let s of props.values['students']) {
        if (infoData.value['_data']['type_info']['rc_user_channels']) {
            studentGroups[s['id']] = infoData.value['_data']['type_info']['rc_user_channels'][s['username']]['group_index'] + 1;
        } else {
            studentGroups[s['id']] = 1;
        }
    }
    additionalUserData.value.titles.push("Group");
    additionalUserData.value.values.push(studentGroups);

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
                if (response.data['groups']) {
                    groups.value = response.data.groups;
                } else {
                    messages.value = response.data.messages;
                }
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