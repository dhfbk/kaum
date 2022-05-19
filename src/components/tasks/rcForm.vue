<template>
    <div class="row">
        <div class="col">
            <div class="alert alert-warning">
                Some task information cannot be updated at a later time.
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col">
            <label class="form-label" for="descriptionArea">Initial description of the activity:</label>
            <textarea v-model="values.type_info['description']"
                      name="description" class="form-control"
                      id="descriptionArea" rows="5"
                      placeholder="Initial description of the activity" required>
            </textarea>
            <div class="invalid-feedback">Description required.</div>
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-6">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" v-model="values.type_info['teacher_can_join']" id="masterCheck">
                <label class="form-check-label" for="masterCheck">
                    Educators can always enter the chat
                </label>
                <p><em>
                    If this checkbox is not selected, educators can enter the chat only when
                    one user calls <code>/sos</code> during the session.
                </em></p>
            </div>
        </div>
        <div class="col-md-6">
            <label class="form-label" for="channelName">Channel name (letters, numbers and dashes):</label>
            <input v-model="values.type_info['channel_name']" :minlength="3"
                   name="channelname" class="form-control"
                   id="channelName" type="text" placeholder="Channel name" required/>
            <div class="invalid-feedback">Task name must be at least 3 characters long.</div>
        </div>
    </div>
</template>

<script setup>

import {defineProps, ref} from "vue";

const props = defineProps({
    values: {
        type: Object
    }
});
const values = ref(props.values);

// console.log("Loaded!");

</script>

<style>
</style>
