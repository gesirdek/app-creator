import Vue from 'vue'
import store from '../store'
import VueI18n from 'vue-i18n'

Vue.use(VueI18n);

const i18n = new VueI18n({
  locale: 'tr',
  messages: {}
});

const components_messages = [];
/**
 * @param {String} locale
 */
export async function loadMessages (locale) {
    if (i18n.locale !== locale) {
        i18n.locale = locale
    }
    await axios.get('/gesirdek/get-component-translations/' + locale).then(response => {
        i18n.mergeLocaleMessage(locale, response.data);
    });
    if(components_messages.length > 0){
        let a = 0;
        components_messages.forEach((component_messages) => {
            let component_name = Object.keys(component_messages)[0];
            i18n.mergeLocaleMessage(locale,{[component_name]:component_messages[component_name][locale]});
            a++;
            if (components_messages.length === a){
                store.commit("update_loaded");
            }
        });
    }
}
/**
 * @param {object} messages
 * { component_name: { tr:{}, en:{} }}
 */
export function loadComponentMessages (messages) {
    let component_name = Object.keys(messages)[0];
    components_messages.push(messages);
    i18n.mergeLocaleMessage(i18n.locale, {[component_name]:messages[component_name][i18n.locale]});
}

(async function () {
  await loadMessages(store.getters.locale)
})();

export default i18n
