import {createApp} from 'vue'
import App from './App.vue'
import i18n from './i18n'
import router from './router'
import store from './store'
import 'bootstrap';
import axios from "./axios";

import '@/index.scss';

import 'bootstrap-icons/font/bootstrap-icons.css';

// todo: check because one needs to edit package.json
// import 'vanillajs-datepicker/dist/css/datepicker.css'
// import 'vanillajs-datepicker/dist/css/datepicker-bs5.css'

Object.defineProperty(String.prototype, 'capitalize', {
    value: function() {
        return this.charAt(0).toUpperCase() + this.slice(1);
    },
    enumerable: false
});

const app = createApp(App)
    .use(store)
    .use(router)
    .use(i18n);

router.store = store;

// https://stackoverflow.com/questions/65184107/how-to-use-vue-prototype-or-global-variable-in-vue-3
// app.config.globalProperties.$axios = axios;

// https://forum.vuejs.org/t/how-to-use-globalproperties-in-vue-3-setup-method/108387/4
app.provide('axios', axios);
app.provide('updateAxiosParams', (params) => {
    if (params == undefined) {
        params = {};
    }
    if (localStorage.getItem('php_sess_id')) {
        params["session_id"] = localStorage.getItem('php_sess_id');
    }
    return params;
});

app.mount('#app')

