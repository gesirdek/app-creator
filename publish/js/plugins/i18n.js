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
    const messages = await import(`~/lang/${locale}`);
    i18n.setLocaleMessage(locale, messages);
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
    if (i18n.locale !== locale) {
        i18n.locale = locale
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
    console.log("messages after component load ",i18n.messages);
}

(async function () {
  console.log("application loaded", store.getters.locale);
  await loadMessages(store.getters.locale)
})();

export default i18n
