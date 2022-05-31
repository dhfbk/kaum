<template>
    <nav class="navbar navbar-light bg-light navbar-expand-md">
        <div class="container-fluid">
            <router-link to="/" class="navbar-brand">
                <img :src="`${publicPath}/img/logo_kidactions4horizontal.png`" alt="Kid Actions logo" height="30">
            </router-link>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                    data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent"
                    aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <router-link to="/" class="nav-link">Home</router-link>
                        <!-- <a class="nav-link active" aria-current="page" href="#">Home</a> -->
                    </li>
                    <li v-if="store.state.loggedAdmin">
                        <router-link to="/admin" class="nav-link">Task management</router-link>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Tools
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                            <li><a target="_blank" class="dropdown-item" href="/creender">Creender</a></li>
                            <li><a target="_blank" class="dropdown-item" href="/hssh">High school superhero</a></li>
                            <li><a target="_blank" class="dropdown-item" href="/chat">Rocket.Chat</a></li>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" @click="logout()">Logout</a>
                    </li>
                </ul>
                <div class="d-flex">
                    <label for="langSelect" class="col-form-label me-3">
                        <i class="bi bi-globe2"></i>
                    </label>
                    <select @change="changeLang" class="form-select" v-model="$i18n.locale" id="langSelect">
                        <option v-for="(lang, i) in langs" :key="`Lang${i}`" :value="lang">
                            {{ lang }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </nav>
</template>

<script setup>
import {inject} from 'vue'
import {useStore} from 'vuex'
import {useRouter} from 'vue-router';

import i18n from '../i18n.js';

const langs = i18n.global.availableLocales;

const axios = inject('axios')
const updateAxiosParams = inject('updateAxiosParams');
const store = useStore();
const router = useRouter();
const publicPath = process.env.BASE_URL;

function changeLang() {
    store.commit("setLanguage", i18n.global.locale.value);
}

function logout() {
    // No comment...
    const params = {"params": {"action": "logout", ...updateAxiosParams()}};
    store.commit("logout");
    axios.get("?", params)
        .then(() => {
            router.push("/");
        });
}

</script>

<style>
</style>
