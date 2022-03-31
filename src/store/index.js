import { createStore } from 'vuex'

export default createStore({
  state: {
    loggedIn: false,
    loggedAdmin: false,
    options: {}
  },
  getters: {
  },
  mutations: {
    login(state, payload) {
      state.loggedIn = true;
      state.loggedAdmin = payload.admin;
      state.options = payload.options;
      if (payload.sess_id !== undefined) {
        localStorage.setItem('php_sess_id', payload.sess_id);
      }
    },
    logout(state) {
      state.loggedIn = false;
      state.loggedAdmin = false;
      state.options = {};
      localStorage.removeItem('php_sess_id');
    }
  },
  actions: {
  },
  modules: {
  }
})
