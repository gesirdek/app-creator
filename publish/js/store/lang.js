import Cookies from 'js-cookie'

//const { locale, locales } = window.config;

// state
let cookie_locale=Cookies.get('locale');
if(typeof Cookies.get('locale') === 'undefined' || Cookies.get('locale') === 'undefined'){
    cookie_locale=false;
}

export const state = {
  locale: cookie_locale || 'tr',
  locales: ['tr','en'],
  loaded:0,
};

// getters
export const getters = {
  locale: state => state.locale,
  locales: state => state.locales,
  routeName: (state, getters, rootState) => rootState.route.name,
  locale_loaded : state => state.loaded
};

// mutations
export const mutations = {
  set_locale (state, { locale }) {
    state.locale = locale
  },
  update_loaded (state){
      state.loaded = state.loaded + 1;
  }
};

// actions
export const actions = {
  setLocale ({ commit }, { locale }) {
    commit("set_locale", { locale });
    Cookies.set('locale', locale, { expires: 365 })
  }
};

export default {
    state,
    getters,
    mutations,
    actions
}