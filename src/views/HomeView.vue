<template>
  <div>
    Home
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRouter } from 'vue-router'

const store = useStore();
const router = useRouter();

onMounted(function() {
  if (store.state.loggedAdmin) {
    router.replace({path: "/projects"});
  }
  else if (store.state.loggedIn) {
    router.replace({path: "/project"});
  }
});
</script>
