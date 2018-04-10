// state
export const state = {
  loading : false,
};

// getters
export const getters = {
    loading: state => state.loading
};

// mutations
export const mutations = {
  loading (state, new_state) {
    state.loading = new_state;
  }
};

export default {
    state,
    getters,
    mutations
}