<template>
  <div class="container my-5">
    <form id="taskForm" class="needs-validation" novalidate @submit.stop.prevent="submit">
      <div class="row">
        <div class="mb-3 col-md-9">
          <label class="form-label" for="taskType">Task type</label>
          <select v-model="values.type" name="type"
            class="form-control" id="taskType" required>
            <option value="">[Select one]</option>
            <option v-for="(t, i) in typeOptions" :value="i" :key="i">{{ t }}</option>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label" for="numberOfStudents">Number of students</label>
          <input v-model="values.students" name="students" class="form-control" id="numberOfStudents" type="number" min="1" :max="store.state.options.task_max_students" placeholder="Number of students" required />
          <div class="invalid-feedback">Number of students is required and must be > 0.</div>
        </div>
      </div>
      <template v-if="values.type">
        {{ values.type }}
        <component :is="components[values.type]" />
      </template>
      <div class="text-end mt-5">
        <button class="btn btn-primary btn-lg" type="submit">Submit</button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { ref, defineEmits, defineProps, onMounted, defineAsyncComponent, shallowRef } from 'vue'
import { useStore } from 'vuex'

// import rcForm from '@/components/tasks/rcForm.vue'

const store = useStore();

const emit = defineEmits(['submit']);
const props = defineProps({
  valuesProp: {
    type: Object
  }
});
const values = ref(props.valuesProp);
const typeOptions = ref({});
const components = shallowRef({});

function submit(event) {
//   let form = event.srcElement;
//   form.classList.add('was-validated')
//   if (!form.checkValidity()) {
//     return;
//   }

  emit('submit', values.value);
}

onMounted(async function() {
  typeOptions.value = {};
  let types = store.state.options.task_types.split(/[\r\n]/);
  types = types.filter(word => word.trim().length > 0);
  for (let i = 0; i < types.length; i++) {
    let type = types[i];
    let parts = type.split(/\t/);
    typeOptions.value[parts[0]] = parts[1];
    // components.value[parts[0]] = await import(`@/components/tasks/${parts[0]}Form.vue`);
    components.value[parts[0]] = defineAsyncComponent(() =>
      import(`@/components/tasks/${parts[0]}Form.vue`)
    )
  }
});

</script>

<style>
</style>
