import { createApp } from 'vue'
import App from './App.vue'
import i18n from './i18n'
import router from './router'
import store from './store'
import 'bootstrap';
// import * as bootstrap from 'bootstrap';
import axios from "./axios";

import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';

const app = createApp(App)
	.use(store)
	.use(router)
	.use(i18n)

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

