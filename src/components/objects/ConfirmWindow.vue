<template>
    <div class="modal fade" :id="id">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">
                        <slot name="title"></slot>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    {{ props.message }}
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <slot name="btn-text">Yes</slot>
                    </button>
                    <button type="button" class="btn btn-secondary ms-3" data-bs-dismiss="modal">
                        <slot name="btn-text">No</slot>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import {defineEmits, defineProps, onMounted, ref, toRef} from 'vue'
import {Modal} from 'bootstrap'

const emit = defineEmits(['close'])

const props = defineProps({
    id: {
        type: String,
        default: "idModal"
    },
    show: {
        type: Boolean,
        default: true
    },
    message: {
        type: String
    },
});

const show = toRef(props, 'show');
const pippo = ref(false);

onMounted(() => {
    const m = document.getElementById(props.id);
    m.addEventListener("hide.bs.modal", function () {
        emit("close");
    });
    pippo.value = new Modal(m);
    if (show.value) {
        pippo.value.show();
    }
});

// watch(show, (newValue) => {
//     if (newValue) {
//         pippo.value.show({keyboard: true});
//     }
// });


// console.log(document.getElementById('modalWindow'));
// let pippo = new Modal(document.getElementById('modalWindow'));
// pippo.show();

</script>