import './bootstrap';
import './theming_scripts';
import Vue from 'vue'
/*import App from './App.vue'*/
import AdminApp from './AdminApp'
import router from './router'
import store from './store'
import tr from 'vee-validate/dist/locale/tr'
import VeeValidate, { Validator } from 'vee-validate'
import i18n from './plugins/i18n'
import Vuetify from 'vuetify'
import BootstrapVue from 'bootstrap-vue'

Vue.use(BootstrapVue);

Vue.config.productionTip = false;
Validator.localize('tr', tr);
Vue.use(VeeValidate);
Vue.use(Vuetify);

import './plugins'

new Vue({
    el: '#app',
    i18n,
    router,
    store,
    components: { AdminApp },
    template: '<AdminApp/>'
});