import {createStore} from 'vuex'

export default createStore({
    state: {
        language: "en",
        loggedIn: false,
        loggedAdmin: false,
        options: {},
        data: {}
    },
    getters: {},
    mutations: {
        setLanguage(state, lang) {
            state.language = lang;
            localStorage.setItem('language', lang);
        },
        sessionOnly(state, payload) {
            localStorage.setItem('php_sess_id', payload.sess_id);
            state.loggedAdmin = payload.admin;
        },
        login(state, payload) {
            state.loggedIn = true;
            state.loggedAdmin = payload.admin;
            state.options = payload.options;
            state.data = payload.data;
            if (payload.sess_id !== undefined) {
                localStorage.setItem('php_sess_id', payload.sess_id);
            }
        },
        logout(state) {
            state.loggedIn = false;
            state.loggedAdmin = false;
            state.options = {};
            state.data = {};
            localStorage.removeItem('php_sess_id');
        }
    },
    actions: {},
    modules: {}
})
