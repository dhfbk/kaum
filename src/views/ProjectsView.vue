<template>
  <div class="row">
    <div class="col-md-9">
      <h1>
        Projects list
      </h1>
    </div>
    <div class="col-md-3 text-end">
      <button class="btn btn-primary" @click="this.$router.push('/projects/new')">Add project</button>
    </div>
  </div>
  <p v-if="projectList.length == 0">
    No projects yet
  </p>
  <table v-else class="table">
    <thead>
      <tr>
        <th scope="col">#</th>
        <th scope="col">Name</th>
        <th scope="col">Educators</th>
        <th scope="col">Students</th>
        <th scope="col">Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="p in projectList" :key="p.id" class="align-middle">
        <th scope="row">{{ p.id }}</th>
        <td>{{ p.name }}</td>
        <td>{{ p.data.educators }}</td>
        <td>{{ p.data.students }}</td>
        <td>
          <button type="button" class="btn btn-warning btn-sm"><i class="bi-pencil-square"></i> Edit</button>
          <button type="button" class="ms-3 btn btn-danger btn-sm"><i class="bi-trash"></i> Delete</button>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script setup>
import { ref, inject, onMounted } from 'vue'

// @ is an alias to /src
// import HelloWorld from '@/components/HelloWorld.vue'

const axios = inject('axios')
const updateAxiosParams = inject('updateAxiosParams');

const projectList = ref([]);

onMounted(function() {
  axios.get("?", {"params": {"action": "projectList", ...updateAxiosParams()}})
    .then((response) => {
      projectList.value = response.data.records;
      console.log(response.data.records);
    })
    .catch((reason) => {
      console.log(reason);
      // store.commit('logout');
      // if (reason.response.status !== 401) {
      //   showModal.value = true;
      //   modalMessage.value = reason.response.statusText;
      // }
    })
    .then(() => {
      // mainLoaded.value = true;
    });
});

</script>
