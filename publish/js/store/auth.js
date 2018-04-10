import axios from 'axios'
import Cookies from 'js-cookie'

// state
export const state = {
  user: {},
  token: Cookies.get('token'),
  waiting_requests: []
};

// getters
export const getters = {
  user: state => state.user,
  token: state => state.token,
  check: state => state.user !== null,
  loginDialog: state => state.loginDialog,
  waiting_requests: state => state.waiting_requests
};

// mutations
export const mutations = {
  save_token (state, { token, remember }) {
    state.token = token;
    Cookies.set('token', token, { expires: remember ? 365 : null })
  },

  fetch_user_success (state, { user }) {
    state.user = user
  },

  fetch_user_failure (state) {
    state.token = null
    Cookies.remove('token')
  },

  logout (state) {
    state.user = null
    state.token = null

    Cookies.remove('token')
  },

  update_user (state, { user }) {
    state.user = user
  },
  add_to_waiting_requests(state,request){
      state.waiting_requests.push(request);
  },
  empty_waiting_requests(state){
    state.waiting_requests = []
  }
}

// actions
export const actions = {
  saveToken ({ commit, dispatch }, payload) {
    commit("save_token", payload)
  },

  async fetchUser ({ commit }) {
    try {
      const { data } = await axios.get('/api/user');

      commit("fetch_user_success", { user: data.user })
      $('#loginModal').modal('hide');
      commit("empty_waiting_requests")
    } catch (e) {
      commit("fetch_user_failure")
    }
  },

  updateUser ({ commit }, payload) {
    commit("update_user", payload)
  },

  async logout ({ commit }) {
    try {
      await axios.post('/api/guest/logout')
      $('#loginModal').modal('show');
    } catch (e) { }

    commit("logout")
  }
};

export default {
    state,
    getters,
    mutations,
    actions
}