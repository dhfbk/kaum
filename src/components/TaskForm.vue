<template>
    <h1>
        {{ props.title }}
    </h1>
    <p v-if="cloneText" class="text-muted">
        <LoadingSpinner v-show="loadingClone"/>
        <em>{{ cloneText }}</em>
    </p>
    <!--    <p>{{ values }}</p>-->
    <div class="my-3">
        <form id="taskForm" class="needs-validation" novalidate @submit.stop.prevent="submit">
            <div class="card mb-3">
                <h5 class="card-header">Main settings</h5>
                <div class="card-body">
                    <div class="row gy-3">
                        <div class="col-md-12">
                            <label class="form-label" for="taskName">Task name</label>
                            <input v-model="values.name" :minlength="store.state.options.task_name_minlength"
                                   name="name" class="form-control"
                                   id="taskName" type="text" placeholder="Task name" required/>
                            <div class="invalid-feedback">Task name must be at least
                                {{ store.state.options.task_name_minlength }} characters long.
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="taskType">Task type</label>
                            <select v-model="values.type" name="type"
                                    class="form-control" id="taskType" required>
                                <option value="">[Select one]</option>
                                <option v-for="(t, i) in typeOptions" :value="i" :key="i">{{ t }}</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="numberOfStudents">Students</label>
                            <input v-model="values.students" name="students" class="form-control" id="numberOfStudents"
                                   type="number" min="1" :max="store.state.options.task_max_students"
                                   placeholder="Number of students" required/>
                            <div class="invalid-feedback">Number of students is required and must be > 0.</div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="defaultPasswords">Default password complexity</label>
                            <select v-model="values.passwords" name="passwords" class="form-select" required>
                                <option value="">[Select one]</option>
                                <option value="trivial">Trivial (same as username)</option>
                                <option value="easy">Easy (existing word plus random number)</option>
                                <option value="difficult">Difficult (random character sequence)</option>
                            </select>
                            <div class="invalid-feedback">Select a password complexity.</div>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="values.type" class="card mb-3">
                <h5 class="card-header">{{ typeOptions[values.type] }} settings</h5>
                <div class="card-body">
                    <component :is="components[values.type]" :values="values"/>
                </div>
            </div>
            <div class="card mb-3">
                <h5 class="card-header">Temporal settings</h5>
                <div class="card-body">
                    <div class="row gy-3 mb-3">
                        <div class="col">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" v-model="values.automatic_timing"
                                       id="useAutomaticTiming">
                                <label class="form-check-label" for="useAutomaticTiming">
                                    Automatic activation
                                </label>
                            </div>
                        </div>
                    </div>
                    <!-- Used v-show because of DateRangePicker constructor -->
                    <div v-show="values.automatic_timing">
                        <div class="row gy-3 mb-3">
                            <div class="col">
                                <!--                                <label class="form-label" for="dr1">Dates</label>-->
                                <div class="input-group mb-3 date input-daterange" id="dr">
                                    <span class="input-group-text"><i class="bi bi-calendar-date"></i></span>
                                    <span class="input-group-text d-md-flex d-none">From</span>
                                    <Datepicker class="form-control" id="dr1"
                                                v-model="values.time.start_date" format="dd/MM/yyyy"
                                                autoApply :enableTimePicker="false"></Datepicker>
                                    <span class="input-group-text">to</span>
                                    <Datepicker class="form-control" id="dr1"
                                                v-model="values.time.end_date" format="dd/MM/yyyy"
                                                autoApply :enableTimePicker="false"></Datepicker>
                                </div>
                            </div>
                        </div>
                        <div class="row gy-3 mb-3">
                            <div class="col-3 col-md">
                                <p class="lead nobr select-buttons">
                                    <i @click="values.time.days = Array(7).fill(0).map((x, y) => x + y)"
                                       class="bi bi-check-circle me-2"></i>
                                    <i @click="values.time.days = []" class="bi bi-x-circle me-2"></i>
                                    <i @click="values.time.days = Array(5).fill(1).map((x, y) => x + y)"
                                       class="bi bi-hammer"></i>
                                </p>
                            </div>
                            <div class="col-3 col-md" v-for="(weekDay, i) in weekDays" :key="i">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" v-model="values.time.days"
                                           :id="'timing' + weekDay" :value="i">
                                    <label class="form-check-label" :for="'timing' + weekDay">
                                        {{ split(weekDay, 0) }}<span class="d-none d-md-inline">{{
                                            split(weekDay, 1)
                                        }}</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="row gy-3 mb-3">
                            <div class="col">
                                <label class="form-label" for="tm1">Time spans</label>
                                <div class="input-group mb-3">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="checkbox"
                                               v-model="values.time.use_morning">
                                    </div>
                                    <span class="input-group-text"><i class="bi bi-clock pe-2"></i> AM</span>
                                    <span class="input-group-text d-md-flex d-none">From</span>
                                    <select :disabled="!values.time.use_morning" v-model="values.time.morning_from"
                                            name="morning_from" class="form-control">
                                        <option v-for="(t, i) in timeSpans" :value="t" :key="i">{{ t }}</option>
                                    </select>
                                    <span class="input-group-text">to</span>
                                    <select :disabled="!values.time.use_morning" v-model="values.time.morning_to"
                                            name="morning_to" class="form-control">
                                        <option v-for="(t, i) in timeSpans" :value="t" :key="i">{{ t }}</option>
                                    </select>
                                </div>
                                <div class="input-group">
                                    <div class="input-group-text">
                                        <input class="form-check-input mt-0" type="checkbox"
                                               v-model="values.time.use_afternoon">
                                    </div>
                                    <span class="input-group-text"><i class="bi bi-clock pe-2"></i> PM</span>
                                    <span class="input-group-text d-md-flex d-none">From</span>
                                    <select :disabled="!values.time.use_afternoon" v-model="values.time.afternoon_from"
                                            name="afternoon_from" class="form-control">
                                        <option v-for="(t, i) in timeSpans" :value="t" :key="i">{{ t }}</option>
                                    </select>
                                    <span class="input-group-text">to</span>
                                    <select :disabled="!values.time.use_afternoon" v-model="values.time.afternoon_to"
                                            name="afternoon_to" class="form-control">
                                        <option v-for="(t, i) in timeSpans" :value="t" :key="i">{{ t }}</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row align-items-center">
                <div class="col">
                    <div class="progress" v-if="disableSubmit">
                        <div class="progress-bar progress-bar-striped progress-bar-animated"
                             :style="{width: formLoadingPercent + '%'}"></div>
                    </div>
                </div>
                <div class="col-auto">
                    <button v-if="disableSubmit" class="btn btn-danger btn-lg ms-3" @click="cancel()">Cancel</button>
                    <button :disabled="disableSubmit || loadingClone" class="btn btn-primary btn-lg ms-3" type="submit">Submit</button>
                    <button :disabled="disableSubmit" class="btn btn-warning btn-lg ms-3" @click.prevent="back()">Back
                    </button>
                </div>
            </div>
        </form>
    </div>
</template>

<script setup>
import {onMounted, defineEmits, defineProps, ref, inject} from 'vue'
import {useStore} from 'vuex'
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css'
import {useRoute} from "vue-router";
import LoadingSpinner from "@/components/objects/LoadingSpinner";

const store = useStore();
const route = useRoute();
const axios = inject('axios');
const updateAxiosParams = inject("updateAxiosParams");
const cloneText = ref("");

const emit = defineEmits(['submit', 'cancel', 'back']);
const props = defineProps({
    title: {
        type: String
    },
    valuesProp: {
        type: Object
    },
    disableSubmit: {
        type: Boolean
    },
    formLoadingPercent: {
        type: Number
    },
    typeOptions: {
        type: Object
    },
    components: {
        type: Object
    }
});
const values = ref(props.valuesProp);
const loadingClone = ref(false);

const split = (string, part) => {
    if (part == 0) {
        return string.substring(0, 3);
    } else {
        return string.substring(3);
    }
}

let weekDays = [];
weekDays.push("Sunday");
weekDays.push("Monday");
weekDays.push("Tuesday");
weekDays.push("Wednesday");
weekDays.push("Thursday");
weekDays.push("Friday");
weekDays.push("Saturday");

let timeSpans = [];
for (let i = 0; i < 24; i++) {
    let hs = `${i}`.padStart(2, '0');
    timeSpans.push(hs + ":00");
    timeSpans.push(hs + ":15");
    timeSpans.push(hs + ":30");
    timeSpans.push(hs + ":45");
}
timeSpans = ref(timeSpans);

function copyValuesForClonation(o1, o2, tiLabels) {
    o1.name = o2.name;
    o1.type = o2.type;
    o1.students = o2.students;
    o1.passwords = o2.passwords;
    o1.time = o2.time;

    for (let l of tiLabels) {
        o1.type_info[l] = o2.type_info[l];
    }
}

onMounted(async function () {
    if (route.params.cloneID) {
        cloneText.value = "Getting data to clone...";
        loadingClone.value = true;
        axios.get("?", {
            "params": {
                "action": "task",
                "sub": "info",
                "project_id": route.params.id,
                "id": route.params.cloneID, ...updateAxiosParams()
            }
        })
            .then(async (response) => {
                copyValuesForClonation(values.value, response.data.info.data, response.data.clone_values);
            })
            .finally(function() {
                cloneText.value = "Clone from task " + route.params.cloneID;
                loadingClone.value = false;
            });
    }
});

function back() {
    emit('back');
}

function cancel() {
    emit('cancel');
}

function submit(event) {
    let form = event.srcElement;
    form.classList.add('was-validated')
    if (!form.checkValidity()) {
        return;
    }

    emit('submit', values.value);
}

</script>

<style>
/* fix for DateRangePicker bug */
.input-daterange input[name="range-end"] {
    border-top-right-radius: 4px !important;
    border-bottom-right-radius: 4px !important;
}

.select-buttons {
    position: relative;
    top: -4px;
}
</style>
