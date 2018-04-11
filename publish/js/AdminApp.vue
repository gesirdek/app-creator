<template>
    <v-app>
        <v-toolbar color="indigo" dark fixed app>
            <v-toolbar-title style="width: 300px" class="ml-0 pl-3">
                <v-toolbar-side-icon @click.stop="drawer = !drawer"></v-toolbar-side-icon>
                <span class="hidden-sm-and-down">{{ $t("app.app_name") }}</span>
            </v-toolbar-title>
            <v-text-field
                    flat
                    solo-inverted
                    prepend-icon="search"
                    :label="$t('app.search')"
                    class="hidden-sm-and-down"
            ></v-text-field>
            <v-spacer></v-spacer>
            <v-btn icon @click="store.dispatch('logout')" v-if="store.getters.check">
                <v-icon>exit_to_app</v-icon>
            </v-btn>
            <v-btn icon @click="store.commit('update_dialog',true)" v-if="!store.getters.check">
                <v-icon>lock_outline</v-icon>
            </v-btn>
            <v-btn @click="setLocale(l)" v-for="l in locales" :key="l" v-if="l !== store.getters.locale" color="primary">
                {{ l }}
            </v-btn>
        </v-toolbar>
        <v-navigation-drawer
                fixed
                v-model="drawer"
                app
        >
            <v-list dense>
                <div v-for="nav_item in this.$router.options.routes" :key="nav_item.name">
                    <v-list-tile :to="nav_item.path" v-if="!nav_item.children && nav_item.name !=='404'">
                        <v-list-tile-content>
                            <v-list-tile-title>{{ $t('menu.'+nav_item.name) }}</v-list-tile-title>
                        </v-list-tile-content>
                    </v-list-tile>
                    <v-expansion-panel v-if="nav_item.children">
                        <v-expansion-panel-content>
                            <div slot="header">
                                {{ $t('menu.'+nav_item.name) }}
                            </div>
                            <v-list-tile :to="nav_item.path+'/'+n_item.path" v-for="n_item in nav_item.children" :key="n_item.name">
                                <v-list-tile-content>
                                    <v-list-tile-title>{{ $t('menu.'+n_item.name) }}</v-list-tile-title>
                                </v-list-tile-content>
                            </v-list-tile>
                        </v-expansion-panel-content>
                    </v-expansion-panel>
                </div>
            </v-list>
        </v-navigation-drawer>
        <v-content>
            <v-container fluid>
                <transition name="fade" mode="out-in" appear>
                    <router-view :key="$route.fullPath"></router-view>
                </transition>
                <v-progress-circular :indeterminate="true" :size="50" :width="3" color="purple" v-if="store.getters.loading" class="myloader elevation-10"></v-progress-circular>
            </v-container>
        </v-content>
        <v-footer color="indigo" app>
            <span class="white--text">&copy; 2018</span>
        </v-footer>
        <v-snackbar
                :timeout="snackbar.duration"
                bottom
                right
                v-model="snackbar.snackbar"
                color="success"
        >
            {{ snackbar.message }}
            <v-btn flat color="white" @click.native="close_snackbar">{{ $t("app.snackbar_close") }}</v-btn>
        </v-snackbar>
        <Login></Login>
    </v-app>
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
                        "app_name":"Fason Rehberi",
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
                        "app_name":"EN Fason Rehberi",
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
<style>
    .myloader{
        position: fixed;
        left:50%;
        top:50%;
        z-index:9999;
    }
</style>