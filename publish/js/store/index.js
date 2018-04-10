import Vue from 'vue';
import Vuex from 'vuex';
import snackbar from './snackbar'
import auth from'./auth'
import lang from './lang'
import theme from './theme'

Vue.use(Vuex);

export default new Vuex.Store({
    modules: {
        auth: auth,
        snackbar: snackbar,
        lang: lang,
        theme: theme
    },
});