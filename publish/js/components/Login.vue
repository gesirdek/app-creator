<template>
    <div max-width="500px" id="loginModal" class="modal" role="dialog" tabindex="-1">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Login form</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form @submit.prevent="login">
                        <div class="form-group">
                            <label for="formName">Full Name:</label>
                            <input type="text" name="name" id="formName" v-model="form.name"
                                   v-validate="'required'"
                                   :class="{'form-control': true, 'is-invalid': errors.has('name'),  }">
                            <span v-show="errors.has('name')" class="invalid-feedback">{{ errors.first('name') }}</span>
                        </div>
                        <input class="primary" type="submit" value="Giriş">
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import store from '../store'
    import axios from 'axios'
    import i18n from '~/plugins/i18n'

    export default {
        store: store,
        name: 'Login',
        data: () => ({
            drawer: null,
            form: {
                email: '',
                password: '',
                name: ''
            },
        }),
        props: {
            source: String
        },
        methods: {
            async login() {
                console.log("login", this.form);
                // Submit the form.
                const {data} = await axios.post('/api/guest/login', this.form);

                // Save the token.
                console.log(data);
                this.$store.dispatch('saveToken', {
                    token: data.token,
                    remember: false
                });

                // Fetch the user.
                await this.$store.dispatch('fetchUser');
                this.$store.dispatch("setAllSnackbar", {snackbar: true, message: i18n.t("welcome"), duration: 0});
            }
        }
    }
</script>