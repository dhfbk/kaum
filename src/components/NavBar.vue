<template>
  <nav class="navbar navbar-light bg-light navbar-expand-md">
    <div class="container-fluid">
      <router-link to="/" class="navbar-brand">
        <img src="img/logo_kidactions4horizontal.png" alt="Kid Actions logo" height="30">
      </router-link>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto">
          <li class="nav-item">
            <router-link to="/" class="nav-link">Home</router-link>
            <!-- <a class="nav-link active" aria-current="page" href="#">Home</a> -->
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#" @click="logout()">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</template>

<script setup>
import { inject } from 'vue'
import { useStore } from 'vuex'

const axios = inject('axios')
const updateAxiosParams = inject('updateAxiosParams');
const store = useStore();

function logout() {
  axios.get("?", {"params": {"action": "logout", ...updateAxiosParams()}})
    .then(() => {
      store.commit("logout");
    });
  // console.log("logout");
  // store.commit("logout");
}

</script>

<style>
</style>
