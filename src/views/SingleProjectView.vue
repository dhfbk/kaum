<template>
  <div>
    Test
  </div>
</template>

<script setup>
import { onMounted } from 'vue'
import { useStore } from 'vuex'
import { useRoute, useRouter } from 'vue-router'

const store = useStore();
const route = useRoute();
const router = useRouter();

onMounted(function() {
  if (store.state.loggedAdmin && !route.params.id) {
    router.replace({path: "/projects"});
    return;
  }
  if (!route.params.id) {
    return;
  }
  console.log(route.params.id);
});
</script>
