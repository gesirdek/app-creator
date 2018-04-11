<template>
    <v-dialog v-model="confirmDialog" max-width="500px">
        <v-card class="elevation-12">
            <v-toolbar dark color="error">
                <v-toolbar-title>{{ $t(message) }}</v-toolbar-title>
            </v-toolbar>
            <v-card-actions>
                <v-spacer></v-spacer>
                <v-btn color="error" @click="confirm()">{{ $t(yes) }}</v-btn>
                <v-btn color="primary" @click="cancel()">{{ $t(no) }}</v-btn>
            </v-card-actions>
        </v-card>
    </v-dialog>
</template>

<script>
import { loadComponentMessages } from '~/plugins/i18n'

export default {
    name:'Confirm',
    props: {
        message: {
            type: String,
            default: 'confirmation.are_you_sure',
        },
        yes: {
            type: String,
            default: 'confirmation.yes',
        },
        no: {
            type: String,
            default: 'confirmation.no',
        }
    },

    data() {
        return {
            confirmDialog: false,
            delete_item: null
        }
    },

    methods: {
        open(item){
            this.delete_item = item;
            this.confirmDialog = true;
        },
        confirm() {
            this.$emit( 'confirm', this.delete_item );
            this.confirmDialog = false;
        },
        cancel() {
            this.$emit( 'cancel' );
            this.confirmDialog = false;
        },
    },
    mounted(){
        loadComponentMessages({
            "confirmation":{
                "tr":{
                    "are_you_sure":"Emin misiniz?",
                    "yes":"Evet",
                    "no":"Hayır"
                },
                "en":{
                    "are_you_sure":"Are you sure?",
                    "yes":"Yes",
                    "no":"No"
                }
            }
        });
    }
}
</script>