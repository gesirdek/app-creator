import axios from 'axios'
import store from '~/store'
import swal from 'sweetalert2'
import i18n from '~/plugins/i18n'

// Request interceptor
axios.interceptors.request.use(async request => {
    return request
});

// Response interceptor
axios.interceptors.response.use(response => {
    console.log("response came");
    if (store.getters.waiting_requests.length === 0) {
        console.log("set loading false");
        store.commit("loading", false);
    }
    return response;
}, async error => {
    console.log("response came");
    if (store.getters.waiting_requests.length === 0) {
        console.log("set loading false");
        store.commit("loading", false);
    }
    if (typeof error.response !== 'undefined') {
        const {status} = error.response;
        if (status >= 500) {
            let error_message = error.response.data;
            if (typeof error_message === 'object') {
                error_message = error_message.message;
            }
            swal({
                type: 'error',
                title: error.message,
                text: error_message,
                reverseButtons: true,
                confirmButtonText: i18n.t('ok'),
                cancelButtonText: i18n.t('cancel')
            })
        }

        if (status === 401 && store.getters.check) {
            await store.dispatch("logout");
            swal({
                type: 'warning',
                title: i18n.t('token_expired_alert_title'),
                text: i18n.t('token_expired_alert_text'),
                reverseButtons: true,
                confirmButtonText: i18n.t('ok'),
                cancelButtonText: i18n.t('cancel')
            }).then(async () => {
                commit("update_dialog", true);
            })
        }
    }
    return Promise.reject(error)
});
