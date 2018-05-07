<template>
    <div class="main-container">
        <transition name="fade" mode="out-in" appear>
            <router-view :key="$route.fullPath"></router-view>
        </transition>
        <Login></Login>
    </div>
</template>

<script>
    import store from './store'
    import Login from './components/Login'
    import { loadMessages } from './plugins/i18n'
    import Vue from 'vue'
    import { loadComponentMessages } from '~/plugins/i18n'

    export default {
        components: { Login },
        store:store,
        name : 'App',
        data: () => ({
            drawer: null,
            store:store
        }),
        computed:{
            snackbar: () => store.getters.snackbar,
            locales: () => store.getters.locales,
            page_name: () => store.getters.routeName,
            components: () => Vue.options.components
        },
        props: {
            source: String
        },
        methods: {
            close_snackbar: () => store.dispatch("closeSnackbar"),
            setLocale (locale) {
                    loadMessages(locale);
                    this.$store.dispatch('setLocale', { locale })
            }
        },
        watch: {
            page_name(new_page_name,old_page_name){
                if(new_page_name!==null){

                }
            },
        },
        mounted(){
            loadComponentMessages({
                "app":{
                    "tr":{
                        "snackbar_close":"KAPAT",
                        "snackbar_saved":"KAYDEDİLDİ",
                        "snackbar_updated":"GÜNCELLENDİ",
                        "snackbar_deleted":"SİLİNDİ",
                        "app_name":"App Name",
                        "search":"Ara",
                        "navigation_home":"Ana Sayfa",
                    },
                    "en":{
                        "snackbar_close":"CLOSE",
                        "snackbar_saved":"SAVED",
                        "snackbar_updated":"UPDATED",
                        "snackbar_deleted":"DELETED",
                        "app_name":"App Name",
                        "search":"Search",
                        "navigation_home":"Home",
                    }
                }
            });
            loadComponentMessages({
                "menu":{
                    "tr":{
                        "Home":"Ana Sayfa",

                    },
                    "en":{
                        "Home":"Home",

                    }
                }
            });
        }
    }
</script>