<template>
    <div>
        <div class="row">
            <div class="col">
                <div class="alert alert-warning">
                    Some task information cannot be updated at a later time.
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-4 col-sm-6">
                <label class="form-label" for="numberOfStudents">Groups</label>
                <input v-model="values.type_info['rc_groups']" name="groups" class="form-control" id="groups"
                       type="number" min="1" :max="10" @change="updateGroups"
                       placeholder="Number of groups" required/>
                <div class="invalid-feedback">Number of groups is required and must be > 0.</div>
            </div>
            <div class="col-8 col-sm-6">
                <label class="form-label" for="uniqueScenario">Unique scenario</label>
                <select v-model="values.type_info['rc_uniqueScenario']" name="uniqueScenario"
                        class="form-control" id="uniqueScenario" required
                        :disabled="values.type_info['rc_groups'] <= 1">
                    <option value="1">Yes</option>
                    <option value="0">No</option>
                </select>
            </div>
        </div>
        <template v-if="values.type_info['rc_uniqueScenario'] == '1'">
            <rc-scenario-task :values="values.type_info" :show="false" :scenarios="scenarios" id=""/>
        </template>
        <template v-else>
            <template v-for="groupIndex in parseInt(values['type_info']['rc_groups'])" :key="groupIndex">
                <div class="row">
                    <div class="col-12 mt-3 mb-1">
                        <hr/>
                        <h5>Scenario {{ groupIndex }}</h5>
                    </div>
                </div>
                <rc-scenario-task :values="values.type_info['rc_scenario_groups'][groupIndex]" :show="false" :scenarios="scenarios" :id="groupIndex.toString()"/>
            </template>
        </template>
        <template v-if="values.type_info['rc_groups'] > 1">
            <div class="row">
                <div class="col-12 mt-3 mb-1">
                    <hr/>
                    <h5>Groups</h5>
                    <div class="row align-items-center row-cols-sm-auto mb-3">
                        <div class="col-12 pe-0"><small>
                            Genera automaticamente i gruppi
                        </small></div>
                        <div class="col-12 pe-0">
                            <select v-model="autoStrategy" class="form-control form-control-sm no-validation">
                                <option value="0">senza alternare</option>
                                <option value="1">alternando</option>
                            </select>
                        </div>
                        <div class="col-12 pe-0"><small>
                            esclusi degli ultimi
                        </small></div>
                        <div class="col-12 pe-0">
                            <input class="form-control form-control-sm no-validation" v-model="skipUsers"
                                   type="number" min="0" :max="values['students']"/>
                        </div>
                        <div class="col-12 pe-0"><small>
                            utenti.
                        </small></div>
                        <div class="col-12">
                            <button class="btn btn-sm btn-primary" @click.prevent="splitUsers">Genera</button>
                        </div>
                    </div>
                    <div class="alert alert-info">
                        Select group for each user. Use 0 (zero) if the user does not participate to any group.
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-12 col-sm-4 col-md-3" v-for="userIndex in parseInt(values.students)" :key="userIndex">
                    <div class="row align-items-center mt-2">
                        <div class="col-6 text-end">
                            User {{ userIndex }}:
                        </div>
                        <div class="col-6">
                            <input type="number" min="0" :max="values.type_info['rc_groups']" required
                                   class="form-control" v-model="values.type_info['rc_user_groups'][userIndex]">
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script setup>

import {defineProps, onBeforeMount, ref, watch, inject, onMounted} from "vue";
import RcScenarioTask from "@/components/tasks/rcScenarioTask";

// const store = useStore();
const props = defineProps({
    values: {
        type: Object
    }
});
const values = ref(props.values);
const skipUsers = ref(0);
const autoStrategy = ref(0);
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');
const scenarios = ref({});

watch(() => values.value['students'], () => {
    updateGroups();
});

function splitUsers() {
    // console.log(autoStrategy.value);
    // console.log(skipUsers.value);

    let totUsers = values.value['students'] - skipUsers.value;
    // console.log(totUsers);
    if (autoStrategy.value == "1") { // Alternate
        for (let i = 1; i <= values.value['students']; i++) {
            let group = ((i - 1) % values.value['type_info']['rc_groups']) + 1;
            if (i > totUsers) {
                group = 0;
            }
            values.value['type_info']['rc_user_groups'][i] = group;
        }
    } else {
        let r = totUsers % values.value['type_info']['rc_groups'];
        let d = Math.floor(totUsers / values.value['type_info']['rc_groups']);
        let groups = [];
        for (let g = 1; g <= values.value['type_info']['rc_groups']; g++) {
            let tot = d + (g <= r ? 1 : 0);
            for (let i = 0; i < tot; i++) {
                groups.push(g);
            }
        }
        for (let i = 0; i < skipUsers.value; i++) {
            groups.push(0);
        }
        for (let i = 0; i < groups.length; i++) {
            values.value['type_info']['rc_user_groups'][i + 1] = groups[i];
        }
    }
}

function updateGroups() {
    if (values.value['type_info']['rc_groups'] <= 1) {
        values.value['type_info']['rc_uniqueScenario'] = "1";
    }
    for (let i = 1; i <= values.value['students']; i++) {
        if (values.value['type_info']['rc_user_groups'][i] === undefined) {
            values.value['type_info']['rc_user_groups'][i] = 0;
        }
    }
    for (let i = 1; i <= values.value['type_info']['rc_groups']; i++) {
        if (values.value['type_info']['rc_scenario_groups'][i] === undefined) {
            values.value['type_info']['rc_scenario_groups'][i] = {
                "description": "",
                "teacher_can_join": false,
                "channel_name": ""
            };
        }
    }
}

onMounted(async function() {
    await axios.get("?", {
        "params": {
            "action": "task",
            "sub": "getScenarios",
            "type": "rc",
            ...updateAxiosParams()
        }
    })
        .then((response) => {
            let schools = {};
            for (let s of response.data.schools) {
                schools[s.id] = s.name;
            }
            scenarios.value = {};
            for (let s of response.data.data) {
                s.goodname = "[" + s.lang + "] " + s.name + " (" + schools[s.school] + ")";
                scenarios.value[s.id] = s;
            }
            // console.log(schools);
            // scenarios.value = response.data.data;
        })
        .catch((reason) => {
            console.log(reason);
        });
});

onBeforeMount(async function () {
    if (values.value['type_info']['rc_uniqueScenario'] === undefined) {
        values.value['type_info']['rc_uniqueScenario'] = "1";
    }
    if (values.value['type_info']['rc_groups'] === undefined) {
        values.value['type_info']['rc_groups'] = "1";
    }
    if (values.value['type_info']['rc_user_groups'] === undefined) {
        values.value['type_info']['rc_user_groups'] = {};
    }
    if (values.value['type_info']['rc_scenario_groups'] === undefined) {
        values.value['type_info']['rc_scenario_groups'] = {};
    }
    updateGroups();
});

</script>

<style>
</style>
