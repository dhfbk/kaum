<template>
    <div class="container mt-5">
        <div class="row d-flex justify-content-center">
            <div class="col" id="loginForm">
                <div class="card px-5 py-5" id="form1">
                    <form class="form-data">
                        <p>
                            <img id="imglogo" src="img/logo_kidactions4.png"/>
                        </p>
                        <h1>
                            User Management
                        </h1>
                        <div class="forms-inputs mb-4">
                            <span>Username</span>
                            <input autocomplete="off" type="text" v-model="username"
                                   class="form-control"
                                   v-bind:class="{'is-invalid' : !validField(username) && usernameBlurred}"
                                   v-on:blur="usernameBlurred = true">
                            <div class="invalid-feedback">A valid username is required!</div>
                        </div>
                        <div class="forms-inputs mb-4">
                            <span>Password</span>
                            <input autocomplete="off" type="password" v-model="password"
                                   class="form-control"
                                   v-bind:class="{'is-invalid' : !validField(password) && passwordBlurred}"
                                   v-on:blur="passwordBlurred = true">
                            <div class="invalid-feedback">A valid password is required!</div>
                        </div>
                        <div class="mb-3">
                            <button v-on:click.stop.prevent="submit" class="btn btn-dark w-100">Login</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {defineEmits, ref} from 'vue'

const emit = defineEmits(['submit'])

const username = ref("");
const password = ref("");
const usernameBlurred = ref(false);
const passwordBlurred = ref(false);

// watch(username, (newValue) => {
//   console.log(newValue);
// });

function validField(content) {
    if (content.length > 0) {
        return true;
    }
    return false;
}

function submit() {
    usernameBlurred.value = true;
    passwordBlurred.value = true;
    if (validField(username.value) && validField(password.value)) {
        emit("submit", {username: username.value, password: password.value});
    }
}

</script>

<style>
#imglogo {
    width: 200px;
}

#loginForm {
    max-width: 500px;
}
</style>
