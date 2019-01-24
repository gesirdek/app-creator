<template>
    <v-app id="inspire">
        <v-content class="backgroundforme">
            <v-container fluid fill-height v-if="store.getters.locale_loaded">
                <v-layout align-center justify-center>
                    <v-flex xs12 sm8 md4>
                        <v-card class="elevation-12">
                            <v-toolbar dark color="primary">
                                <v-toolbar-title>{{ $t('Login.title') }}</v-toolbar-title>
                                <v-spacer></v-spacer>
                                <v-btn :loading="locale_loading" small @click="setLocale(l)" v-for="l in locales" :key="l" v-if="l !== store.getters.locale">
                                    {{ l }}
                                </v-btn>
                            </v-toolbar>
                            <v-card-text>
                                <v-form @submit.prevent="login">
                                    <v-text-field
                                            v-model="form.username"
                                            prepend-icon="person"
                                            name="login"
                                            type="text"
                                            v-validate="'required'"
                                            suffix="@boun.edu.tr"
                                            :label="$t('Login.username')"
                                            :error-messages="errors.collect($t('Login.username'))"
                                            :data-vv-name="$t('Login.username')"
                                    >
                                    </v-text-field>
                                    <v-text-field
                                            v-model="form.password"
                                            id="password"
                                            prepend-icon="lock"
                                            name="password"
                                            type="password"
                                            v-validate="'required'"
                                            :label="$t('Login.password')"
                                            :error-messages="errors.collect($t('Login.password'))"
                                            :data-vv-name="$t('Login.password')"
                                    >
                                    </v-text-field>
                                </v-form>
                            </v-card-text>
                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn color="primary" @click="login">{{ $t('Login.submit') }}</v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-flex>
                </v-layout>
            </v-container>
        </v-content>
    </v-app>
</template>

<script>
    import store from '../store'
    import axios from 'axios'
    import i18n from '~/plugins/i18n'
    import { loadMessages } from '../plugins/i18n'
    import Cookies from 'js-cookie'

    export default {
        store: store,
        name: 'Login',
        data: () => ({
            drawer: null,
            store:store,
            locale_loading:false,
            data:{},
            form: {
                username: '',
                password: ''
            },
        }),
        created(){
            Cookies.remove('token');
        },
        computed:{
            locales: () => store.getters.locales,
        },
        props: {
            source: String
        },
        methods: {
            async setLocale (locale) {
                this.locale_loading = true;
                await loadMessages(locale);
                this.$store.dispatch('setLocale', { locale });
                this.locale_loading = false;
            },
            login() {
                console.log("login", this.form);
                this.$validator.validateAll().then(async passes=> {
                    if(passes){
                        const {data} = await axios.post('/login', this.form);
                        // Save the token.
                        console.log(data);
                        this.data = data;
                        this.$store.dispatch('saveToken', { data: data});
                        this.$store.dispatch("setAllSnackbar", {snackbar: true, message: i18n.t("welcome"), duration: 0});
                    }
                });
                //todo put it into cookie
            }
        }
    }
</script>

<style scoped>
    .backgroundforme{
        background: url(/tms-bg.jpg);
        background-size: cover;
    }
</style>