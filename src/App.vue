<template>
  <div v-if="mainLoaded">
    <LoginForm v-if="!loggedIn" @submit="submit" />
    <div v-else>
      <div class="container-lg">
        <NavBar />
        <div class="my-3">
          <router-view />
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
    <template v-slot:title>Error</template>
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
import { ref, inject, computed, onMounted, provide } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router';

// @ is an alias to /src
import LoginForm from '@/components/LoginForm.vue'
import ModalWindow from '@/components/ModalWindow.vue'
import NavBar from '@/components/NavBar.vue'

const showModal = ref(false);
const modalMessage = ref("");

const mainLoaded = ref(false);

const axios = inject('axios')
const updateAxiosParams = inject('updateAxiosParams');
const store = useStore();
const router = useRouter();

const loggedIn = computed(() => store.state.loggedIn);

function showModalWindow(t) {
  showModal.value = true;
  modalMessage.value = t;
}

provide('showModalWindow', showModalWindow);

async function loadUserInfo() {
  await axios.get("?", {"params": {"action": "userinfo", ...updateAxiosParams()}})
    .then((response) => {
      store.commit("login", response.data);
    })
    .catch((reason) => {
      store.commit('logout');
      if (reason.response.status !== 401) {
        showModal.value = true;
        modalMessage.value = reason.response.statusText;
      }
    })
    .then(() => {
      mainLoaded.value = true;
    });
}

onMounted(function() {
  loadUserInfo();
});

function submit({username, password}) {
  axios.get("?", {"params": {"action": "login", "username": username, "password": password}})
  .then((response) => {
    let sess_id = response.data.session_id;
    axios.get("?", {"params": {"action": "userinfo"}})
      .then(() => {
        console.log("Not storing session ID");
        store.commit("login", {"sess_id": null, "admin": username == "admin"});
      })
      .catch((reason) => {
        if (reason.response.status === 401) {
          console.log("Storing ID");
          store.commit("login", {"sess_id": sess_id, "admin": username == "admin"});
          loadUserInfo();
        }
      });
  })
  .catch((reason) => {
    showModal.value = true;
    modalMessage.value = reason.response.data?.error
      ? reason.response.data.error
      : reason.response.statusText;
  }).then(() => {
    router.push('/');
  });
}
</script>
