import Cookies from 'js-cookie'
import {Validator} from 'vee-validate'
import tr from 'vee-validate/dist/locale/tr'
import en from 'vee-validate/dist/locale/en'
import {loadMessages} from "../plugins/i18n";
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
    async setLocale({commit}, {locale}) {
        await axios.post('/gesirdek/set-language/' + locale);
        Cookies.set('locale', locale, {expires: 365});
        Validator.localize(locale, {locale});
        loadMessages(locale);
        commit("set_locale",{locale})
    }
};

export default {
    state,
    getters,
    mutations,
    actions
}