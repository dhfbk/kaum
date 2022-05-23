<template>
    <div v-if="mainLoaded">
        <LoginForm v-if="!loggedIn" @submit="submit"/>
        <div v-else>
            <div class="container-lg">
                <NavBar/>
                <div class="my-3">
                    <router-view/>
                </div>
            </div>
        </div>
    </div>
    <div v-else>
        <div class="spinner-border m-5" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <ModalWindow :show="showModal" :message="modalMessage" id="loginModalWindow" @close="showModal = false">
        <template v-slot:title>Message</template>
        <template v-slot:btn-text>Ok</template>
    </ModalWindow>

    <!--   <nav>
        <router-link to="/">Home</router-link> |
        <router-link to="/about">About</router-link> |
        <router-link to="/i18n">Language</router-link>
      </nav>
      <router-view/> -->
</template>

<script setup>
import {computed, inject, onMounted, provide, ref, defineAsyncComponent, shallowRef} from 'vue'
import {useStore} from 'vuex'
// import {useRouter} from 'vue-router';

// @ is an alias to /src
import LoginForm from '@/components/LoginForm.vue'
import ModalWindow from '@/components/objects/ModalWindow.vue'
import NavBar from '@/components/NavBar.vue'

const showModal = ref(false);
const modalMessage = ref("");

const mainLoaded = ref(false);

const axios = inject('axios')
const updateAxiosParams = inject('updateAxiosParams');
const store = useStore();
// const router = useRouter();

const loggedIn = computed(() => store.state.loggedIn);

const typeOptions = ref({});
const formComponents = shallowRef({});
const adminComponents = shallowRef({});

function showModalWindow(t) {
    showModal.value = true;
    modalMessage.value = t;
}

provide('showModalWindow', showModalWindow);
provide('typeOptions', typeOptions);
provide('formComponents', formComponents);
provide('adminComponents', adminComponents);

async function loadUserInfo() {
    await axios.get("?", {"params": {"action": "userinfo", ...updateAxiosParams()}})
        .then((response) => {
            store.commit("login", response.data);
        })
        .catch((reason) => {
            store.commit('logout');
            if (reason.response && reason.response.status !== 401) {
                showModal.value = true;
                modalMessage.value = reason.response.statusText;
            }
        })
        .then(() => {
            // mainLoaded.value = true;
        });
}

// This is here so that the types
// are loaded before the app is mounted
// onBeforeMount(async function() {
//     await loadTypeOptions();
// });

onMounted(async function () {
    await loadUserInfo();
    await loadTypeOptions();
    mainLoaded.value = true;
});

function submit({username, password}) {
    axios.get("?", {
        "params": {"action": "login", "username": username, "password": password}
    })
        .then((response) => {
            let sess_id = response.data.session_id;
            store.commit("sessionOnly", {"sess_id": sess_id, "admin": username === "admin"});

            loadUserInfo();
            // axios.get("?", {"params": {"action": "userinfo"}})
            //     .then(() => {
            //         console.log("Not storing session ID");
            //         store.commit("login", {"sess_id": null, "admin": username === "admin"});
            //         router.push('/');
            //     })
            //     .catch((reason) => {
            //         if (reason.response.status === 401) {
            //             console.log("Storing ID");
            //             store.commit("login", {"sess_id": sess_id, "admin": username === "admin"});
            //             loadUserInfo();
            //             router.push('/');
            //         }
            //     });
        })
        .catch((reason) => {
            showModal.value = true;
            modalMessage.value = reason.response.data?.error
                ? reason.response.data.error
                : reason.response.statusText;
        });
}

async function loadTypeOptions() {
    await axios.get("?", {
        "params": {
            "action": "taskTypes"
        }
    })
        .then((response) => {
            typeOptions.value = response.data.types;
            for (let prop in typeOptions.value) {
                formComponents.value[prop] = defineAsyncComponent(() =>
                    import(`@/components/tasks/${prop}Form.vue`)
                        .then()
                        .catch(() => {
                            // ignored
                        })
                )
                adminComponents.value[prop] = defineAsyncComponent(() =>
                    import(`@/components/tasks/${prop}Admin.vue`)
                        .then()
                        .catch(() => {
                            // ignored
                        })
                );
            }
        });

}
</script>

<style>
.accordion-header button {
    font-weight: bold;
}

.was-validated .no-validation:focus {
    color: #212529;
    background-color: #fff;
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgb(13 110 253 / 25%);
}

.was-validated .form-control.no-validation:valid,
.was-validated .form-control.no-validation:invalid {
    border-color: #212529;
    background: none;
    padding: .375rem .75rem;
}

.was-validated .form-control-sm.no-validation:valid,
.was-validated .form-control-sm.no-validation:invalid {
    border-color: #ced4da;
    background: none;
    padding: .25rem .5rem;
}

.was-validated .no-validation:invalid ~ .invalid-feedback,
.was-validated .no-validation:invalid ~ .invalid-tooltip {
    display: none;
}

.was-validated .form-check-input.no-validation-cb:valid ~ .form-check-label,
.was-validated .form-check-input.no-validation-cb:invalid ~ .form-check-label {
    color: inherit;
}

.was-validated .form-check-input.no-validation-cb:valid,
.was-validated .form-check-input.no-validation-cb:invalid {
    background-color: white;
    border: 1px solid rgba(0, 0, 0, .25);
}

.was-validated .form-check-input.no-validation-cb:valid:checked,
.was-validated .form-check-input.no-validation-cb:invalid:checked {
    background-color: #0d6efd;
    border-color: #0d6efd;
}
</style>
