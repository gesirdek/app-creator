import axios from 'axios'
import store from '~/store'
import swal from 'sweetalert2'
import i18n from '~/plugins/i18n'

// Request interceptor
axios.interceptors.request.use(async request => {
  let url = request.url;
  const token = store.getters.token;
  if (token) {
    request.headers.common['Authorization'] = token
  }else{
      if(url.substr(0,10)!=="/api/guest"){
          let request_identifier = url+JSON.stringify(request.data);
          if(store.getters.waiting_requests.includes(request_identifier) === true){
              return false;
          }
          store.commit("update_dialog",true);
          store.commit("add_to_waiting_requests",request_identifier);
          await new Promise((resolve) => {
              console.log(url+" bekliyor");
              let refreshIntervalId = setInterval(function(){
                  if(store.getters.user){
                      clearInterval(refreshIntervalId);
                      request.headers.common['Authorization'] = store.getters.token;
                      console.log(url+" gönder");
                      resolve(request);
                  }
                  }, 1000);
          })
      }
  }

  // request.headers['X-Socket-Id'] = Echo.socketId()
    store.commit("loading",true);
  return request
});

// Response interceptor
axios.interceptors.response.use(response => {
    console.log("response came");
    if(store.getters.waiting_requests.length  === 0){
        console.log("set loading false");
        store.commit("loading",false);
    }
    return response;
}, async error => {
    console.log("response came");
    if(store.getters.waiting_requests.length  === 0){
        console.log("set loading false");
        store.commit("loading",false);
    }
  const { status } = error.response;
  if (status >= 500) {
      let error_message = error.response.data;
      if(typeof error_message === 'object'){
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
        commit("update_dialog",true);
    })
  }

  return Promise.reject(error)
});
