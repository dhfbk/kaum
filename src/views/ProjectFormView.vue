<template>
  <h1>
    {{ title }}
  </h1>
  <ProjectForm :valuesProp="values" @submit="submit" />
</template>

<script setup>
import { inject, ref, defineProps, onMounted } from 'vue'
import ProjectForm from "@/components/ProjectForm.vue"
import { useStore } from 'vuex'
import { useRouter } from 'vue-router';

const store = useStore();
const router = useRouter();

const props = defineProps({
  action: String
});
const axios = inject('axios')
const updateAxiosParams = inject('updateAxiosParams');

const values = ref({
  educators: store.state.options.project_default_educators,
  students: store.state.options.project_default_students,
  passwords: store.state.options.project_default_complexity
});
const title = ref("");

const showModalWindow = inject('showModalWindow');

function submit(v) {
  axios.post("?", {"action": "projectAdd", ...updateAxiosParams(), info: v})
    .then((response) => {
      showModalWindow("Project created successfully");
      router.push("/projects");
      console.log(response.data);
    })
    .catch((reason) => {
      let debugText = reason.response.statusText + " - " + reason.response.data.error;
      showModalWindow(debugText);
      // store.commit('logout');
      // if (reason.response.status !== 401) {
      //   showModal.value = true;
      //   modalMessage.value = reason.response.statusText;
      // }
    })
    .then(() => {
      // mainLoaded.value = true;
    });
}

onMounted(function() {
  if (props.action == "add") {
    title.value = "Insert new project";
  }
});
</script>
