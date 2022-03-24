import { createApp } from 'vue'
import App from './App.vue'
import i18n from './i18n'
import router from './router'
import store from './store'
import 'bootstrap';
// import * as bootstrap from 'bootstrap';

import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap/dist/js/bootstrap.min.js';

createApp(App).use(store).use(router).use(i18n).mount('#app')
