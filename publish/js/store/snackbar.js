// state
export const state = {
  snackbar : false,
  duration : 5000,
  message : "Başarılı"
};

// getters
export const getters = {
    snackbar: state => { return {
        snackbar : state.snackbar,
        duration : state.duration,
        message : state.message
    } }
};

// mutations
export const mutations = {
  set_snackbar (state, new_state) {
    state.snackbar = new_state;
  },

    set_snackbar_duration (state, duration) {
    state.duration = duration;
  },

    set_snackbar_message (state, message) {
    state.message = message;
  }
};

// actions
export const actions = {
  setAllSnackbar ({ commit },snackbar) {
    commit("set_snackbar", false);
    commit("set_snackbar", snackbar.snackbar);
    commit("set_snackbar_duration", snackbar.duration);
    commit("set_snackbar_message", snackbar.message);
  },
    closeSnackbar ({ commit }) {
    commit("set_snackbar", false);
    commit("set_snackbar_duration", 5000);
    commit("set_snackbar_message", "Başarılı");
  },
};

export default {
    state,
    getters,
    mutations,
    actions
}