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
                        "app_name":"BUKart",
                        "search":"Ara",
                        "navigation_home":"Ana Sayfa",
                        "navigation_core":"Çekirdek",
                        "navigation_core_card_type":"Kart Türleri",
                    },
                    "en":{
                        "snackbar_close":"CLOSE",
                        "snackbar_saved":"SAVED",
                        "snackbar_updated":"UPDATED",
                        "snackbar_deleted":"DELETED",
                        "app_name":"BUCard",
                        "search":"Search",
                        "navigation_home":"Home",
                        "navigation_core":"Core",
                        "navigation_core_card_type":"Card Types",
                    }
                }
            });
            loadComponentMessages({
                "menu":{
                    "tr":{
                        "Core":"Çekirdek",
                        "Home":"Ana Sayfa",
                        "Core-Card":"Kartlar",
                        "Core-Card-Type":"Kart Türleri",
                        "Core-Capability":"İzinler",
                        "Core-Capability-Role":"Rol İzinleri",
                        "Core-Capability-User":"Kullanıcı İzinleri",
                        "Core-Capability-Card-Field":"Kart Alanı İzinleri",
                        "Core-Capability-Card-Group":"Kart Grubu İzinleri",
                        "Core-Card-Card-Field":"Kart Alanları İlişkilendirmeleri",
                        "Core-Card-Card-Group":"Kart Grubu İlişkilendirmeleri",
                        "Core-Card-Field":"Kart Alanları",
                        "Core-Card-Group":"Kart Grupları",
                        "Core-Card-Reader":"Kart Okuyucuları",
                        "Core-Card-Reader-Status":"Kart Okuyucu Durumları",
                        "Core-Card-Status":"Kart Durumları",
                        "Core-Device-Model":"Okuyucu Modelleri",
                        "Core-Device-Vendor":"Okuyucu Tedarikçileri",
                        "Core-Ip-Address":"Ip Adresleri",
                        "Core-Ip-Address-User":"Kullanıcı Ip Adresleri",
                        "Core-Role":"Roller",
                        "Core-Role-User":"Kullanıcı Rolleri",
                        "Core-Toll-Gate":"Kapılar",
                        "Core-Toll-Gate-Group":"Kapı Grupları",
                    },
                    "en":{
                        "Core":"Core",
                        "Home":"Home",
                        "Core-Card":"Cards",
                        "Core-Card-Type":"Card Types",
                        "Core-Capability":"Permissions",
                        "Core-Capability-Role":"Role Permissions",
                        "Core-Capability-User":"User Permissions",
                        "Core-Capability-Card-Field":"Card Field Permissions",
                        "Core-Capability-Card-Group":"Card Group Permissions",
                        "Core-Card-Card-Field":"Card - Field Attachments",
                        "Core-Card-Card-Group":"Card - Group Attachments",
                        "Core-Card-Field":"Card Fields",
                        "Core-Card-Group":"Card Groups",
                        "Core-Card-Reader":"Card Readers",
                        "Core-Card-Reader-Status":"Card Reader Statuses",
                        "Core-Card-Status":"Card Statuses",
                        "Core-Device-Model":"Device Models",
                        "Core-Device-Vendor":"Card Reader Suppliers",
                        "Core-Ip-Address":"IP Addresses",
                        "Core-Ip-Address-User":"User IP Addresses",
                        "Core-Role":"Roles",
                        "Core-Role-User":"User Roles",
                        "Core-Toll-Gate":"Gates",
                        "Core-Toll-Gate-Group":"Gate Groups",
                    }
                }
            });
        }
    }
</script>