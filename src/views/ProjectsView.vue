<template>
  <div class="row">
    <div class="col-md-9">
      <h1>
        Project list
      </h1>
    </div>
    <div class="col-md-3 text-end">
      <button class="btn btn-primary btn-sm" @click="this.$router.push('/projects/new')">
        <i class="bi bi-file-earmark-plus"></i> Add project
      </button>
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
        <th scope="col">Status</th>
        <th scope="col">Actions</th>
      </tr>
    </thead>
    <tbody>
      <tr v-for="p in projectList" :key="p.id" class="align-middle">
        <th scope="row">{{ p.id }}</th>
        <td>{{ p.name }}</td>
        <td>{{ p.data.educators }}</td>
        <td>
          <span class="badge bg-warning" v-if="!p.confirmed">Unconfirmed</span>
          <span class="badge bg-success" v-else-if="!p.disabled">Active</span>
          <span class="badge bg-danger" v-else>Disabled</span>
        </td>
        <td>
          <span v-if="!p.confirmed">
            <button @click="confirmProject(p.id)" :class="{'disabled': projectLoading.has(p.id)}" type="button" class="me-3 btn btn-info btn-sm">
              <i class="bi bi-check-circle"></i>
              Confirm
            </button>
            <button @click="getPasswords(p.id)" type="button" class="me-3 btn btn-success btn-sm">
              <i class="bi bi-key"></i>
              Download passwords
            </button>
          </span>
          <span v-else>
            <button @click="enterProject(p.id)" v-if="!p.disabled" type="button" class="me-3 btn btn-success btn-sm"><i class="bi bi-box-arrow-in-right"></i> Enter</button>

            <button @click="toggleAvailability(p.id)" v-if="!p.disabled" type="button" class="me-3 btn btn-warning btn-sm" :class="{'disabled': projectLoading.has(p.id)}"><i class="bi bi-x-circle"></i> Disable</button>
            <button @click="toggleAvailability(p.id)" v-else type="button" class="me-3 btn btn-warning btn-sm" :class="{'disabled': projectLoading.has(p.id)}"><i class="bi bi-brightness-high"></i> Enable</button>
          </span>
          <button type="button" class="me-3 btn btn-danger btn-sm"><i class="bi-trash"></i> Delete</button>
        </td>
      </tr>
    </tbody>
  </table>
</template>

<script setup>
import { ref, inject, onMounted } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter();

// @ is an alias to /src
// import HelloWorld from '@/components/HelloWorld.vue'

const axios = inject('axios')
const updateAxiosParams = inject('updateAxiosParams');
const showModalWindow = inject('showModalWindow');

const projectList = ref([]);
const projectLoading = ref(new Set());

function getPasswords(id) {
  window.location.assign(axios.defaults.baseURL + "?action=projectPasswords&id=" + id);
}

function enterProject(id) {
  router.replace({path: "/project/" + id});
}

function confirmProject(id) {
  if (confirm("Are you sure? This action cannot be undone")) {
    projectAction(id, "projectConfirm");
  }
}

function toggleAvailability(id) {
  projectAction(id, "projectToggleAvailability");
}

function projectAction(id, action) {
  projectLoading.value.add(id);
  axios.post("?", {"action": action, id: id, ...updateAxiosParams()})
    .then(() => {
      updateProjects();
    })
    .catch((reason) => {
      let debugText = reason.response.statusText + " - " + reason.response.data.error;
      showModalWindow(debugText);
    })
    .then(() => {
      projectLoading.value.delete(id);
    });

}

function updateProjects() {
  axios.get("?", {"params": {"action": "projectList", ...updateAxiosParams()}})
    .then((response) => {
      projectList.value = response.data.records;
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
  updateProjects();
});

</script>
