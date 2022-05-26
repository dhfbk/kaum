<template>
    <div class="my-3">
        <form id="projectForm" class="needs-validation" novalidate @submit.stop.prevent="submit">
            <div class="row">
                <div class="mb-3 col-md-6">
                    <label class="form-label" for="projectName">Project name</label>
                    <input v-model="values.name" :minlength="store.state.options.project_name_minlength"
                           name="name" class="form-control"
                           id="projectName" type="text" placeholder="Project name" required/>
                    <div class="invalid-feedback">Project name must be more than 3 characters long.</div>
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label" for="languageSelect">Language</label>
                    <select class="form-select" aria-label="Language" v-model="values.language">
                        <option v-for="lang in languages" :key="lang" :value="lang">{{ lang }}</option>
                    </select>
                </div>
                <div class="mb-3 col-md-3">
                    <label class="form-label" for="numberOfEducators">Number of educators</label>
                    <input v-model="values.educators" name="educators" class="form-control" id="numberOfEducators"
                           type="number" min="1" :max="store.state.options.project_max_educators"
                           placeholder="Number of educators" required/>
                    <div class="invalid-feedback">Number of educators is required and must be > 0.</div>
                </div>
                <!--          <div class="mb-3 col-md">
                              <label class="form-label" for="numberOfStudents">Number of students</label>
                              <input v-model="values.students" name="students" class="form-control" id="numberOfStudents" type="number" min="1" :max="store.state.options.project_max_students" placeholder="Number of students" required />
                              <div class="invalid-feedback">Number of students is required and must be > 0.</div>
                          </div>
                          <div class="mb-3 col-md">
                              <label class="form-label" for="defaultPasswords">Default password complexity</label>
                              <select v-model="values.passwords" name="passwords" class="form-select" required>
                                <option selected value="">[Select one]</option>
                                <option value="trivial">Trivial (same as username)</option>
                                <option value="easy">Easy (existing word plus random number)</option>
                                <option value="difficult">Difficult (random character sequence)</option>
                              </select>
                              <div class="invalid-feedback">Select a password complexity.</div>
                          </div>-->
            </div>
            <div class="text-end mt-md-5">
                <button :disabled="buttonDisabled" class="btn btn-primary btn-lg" type="submit">Submit</button>
                <button :disabled="buttonDisabled" class="btn btn-warning btn-lg ms-3" @click.prevent="back()">Back
                </button>
            </div>
        </form>
    </div>
</template>

<script setup>
import {defineEmits, defineProps, ref} from 'vue'
import {useStore} from 'vuex'

const store = useStore();
let languages = store.state.options.languages.split(",");

const emit = defineEmits(['submit', 'back']);
const props = defineProps({
    valuesProp: {
        type: Object
    },
    buttonDisabled: {
        type: Boolean
    }
});
const values = ref(props.valuesProp);

// const buttonDisabled = ref(props.buttonDisabled);

function back() {
    emit('back');
}

function submit(event) {
    let {srcElement: form} = event;
    form.classList.add('was-validated')
    if (!form.checkValidity()) {
        return;
    }

    emit('submit', values.value);
}

// onMounted(function() {
//   var form = document.getElementById("projectForm");
//   form.addEventListener('submit', function (event) {
//     event.preventDefault()
//     event.stopPropagation()

//     form.classList.add('was-validated')
//     if (!form.checkValidity()) {
//       return;
//     }

//     emit('submit');

//   }, false);
// });

</script>

<style>
</style>
