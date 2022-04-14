<template>
  <p v-if="basicLoading">Loading</p>
  <template v-else>
    <div v-if="route.meta.action == 'add'">
      <h1>Add task</h1>
      <TaskForm :valuesProp="taskInitialValues" @submit="submitNewTask" />
    </div>

    <template v-else>
      <h1>{{ projectInfo.name }}</h1>
      <template v-if="store.state.loggedAdmin">
        <div class="row">
          <div class="col-md-9">
            <h2>
              Educators
            </h2>
          </div>
          <div class="col-md-3 text-end">
      <!--       <button class="btn btn-primary btn-sm" @click="this.$router.push('/projects/new')">
              <i class="bi bi-file-earmark-plus"></i> Add educator
            </button> -->
          </div>
        </div>
        <p v-if="projectInfo.educators.length == 0">
          No educators
        </p>
        <table v-else class="table">
          <thead>
            <tr>
              <th scope="col">#</th>
              <th scope="col">Username</th>
              <th scope="col">Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="e in projectInfo.educators" :key="e.id" class="align-middle">
              <th scope="row">{{ e.id }}</th>
              <td>{{ e.username }}</td>
              <td>Actions</td>
            </tr>
          </tbody>
        </table>
      </template>

      <div class="row">
        <div class="col-md-9">
          <h2>
            Tasks
          </h2>
        </div>
        <div class="col-md-3 text-end">
          <button class="btn btn-primary btn-sm" @click="this.$router.push('/project/'+ route.params.id +'/new')">
            <i class="bi bi-file-earmark-plus"></i> Add task
          </button>
        </div>
      </div>
      <p v-if="projectInfo.tasks.length == 0">
        No tasks yet
      </p>
      <table v-else class="table">
        <thead>
          <tr>
            <th scope="col">#</th>
            <th scope="col">Label</th>
            <th scope="col">Type</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="e in projectInfo.tasks" :key="e.id" class="align-middle">
            <th scope="row">{{ e.id }}</th>
            <td>Label</td>
            <td>Actions</td>
          </tr>
        </tbody>
      </table>
    </template>

  </template>
</template>

<script setup>
import { onMounted, inject, ref } from 'vue'
import { useStore } from 'vuex'
import { useRoute, useRouter } from 'vue-router'

import TaskForm from '@/components/TaskForm.vue'

const store = useStore();
const route = useRoute();
const router = useRouter();

const showModalWindow = inject('showModalWindow');
const axios = inject('axios');
const updateAxiosParams = inject('updateAxiosParams');

const projectInfo = ref({});
const projectLoading = ref(false);
const basicLoading = ref(true);

const taskInitialValues = ref({
  type: '',
  students: store.state.options.task_default_students,
  // students: store.state.options.project_default_students,
  // passwords: store.state.options.project_default_complexity
});

function submitNewTask() {
  console.log("Submitted");
}

onMounted(function() {
  axios.get("?", {"params": {
      "action": "projectInfo", "id": route.params.id, ...updateAxiosParams()}
    })
    .then((response) => {
      basicLoading.value = false;
      projectInfo.value = response.data.info;
      // console.log(response.data);
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
  // if (store.state.loggedAdmin && !route.params.id) {
  //   router.replace({path: "/projects"});
  //   return;
  // }
  // if (!route.params.id) {
  //   return;
  // }
  // console.log(route.params.id);
});
</script>
