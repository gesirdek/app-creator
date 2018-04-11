import axios from 'axios'
import i18n from '~/plugins/i18n'
import store from '~/store'
import Confirm from '~/components/Confirm'
/**
 * Crud Mixin
 * Usage: Add this mixin to provide crud actions
 */
export default {
    data: function () {
        return {
            items: [],
            item: {},
            dialog: false,
            default_item: {},
            headers: [],
            names: []
        }
    },
    components: { Confirm },
    methods: {
        setResource(resource,item_obj,names,multiples){
            this.names = names;
            names.forEach((value,key)=>{
                this.headers.push({ text: i18n.t(value), value: Object.keys(item_obj)[key] });
                if(key + 1 === names.length){
                    this.headers.push({ text:i18n.t("table_actions"),value:"table_actions" ,sortable:false, align:'right'});
                }
            });
            if(typeof multiples!=='undefined'){
                multiples.forEach((value)=>{
                    axios.get(value.source)
                        .then(response => {
                            this[value.list] = response.data
                        });
                });
            }
            this.resource = resource;
            this.item = item_obj;
            this.default_item = JSON.parse(JSON.stringify(item_obj));
            this.getItems();
        },
        getItems(){
            axios.get(this.resource,{

            })
            .then(response => {
                this.items = response.data
            });
        },
        editItem(item){
            this.item=JSON.parse(JSON.stringify(item));
            this.dialog=true;
        },
        deleteItem(item){
            let index = this.items.indexOf(item);
            axios({
                method: 'DELETE',
                url: this.resource+'/'+item.id
            })
            .then(response => {
                this.items.splice(index, 1);
                this.$store.dispatch("setAllSnackbar",{snackbar:true,message:i18n.t("app.snackbar_deleted"),duration:3000});
            })

        },
        submit(){
            if(typeof this.item.id === 'undefined' || this.item.id===0){
                axios.post(this.resource,this.item)
                    .then(response => {
                        this.getItems();
                        this.resetItem();
                        this.dialog=false;
                        this.$store.dispatch("setAllSnackbar",{snackbar:true,message:i18n.t("app.snackbar_saved"),duration:3000});
                    })
            }else{
                axios({
                    method: 'PUT',
                    url: this.resource+'/'+this.item.id,
                    data: this.item
                })
                .then(response => {
                    this.getItems();
                    this.resetItem();
                    this.dialog=false;
                    this.$store.dispatch("setAllSnackbar",{snackbar:true,message:i18n.t("app.snackbar_updated"),duration:3000});
                })
            }
        },
        resetItem(){
            this.item=this.default_item
        },
        deleteConfirm(i){
            this.$refs.confirm.open(i)
        }
    },
    computed: {
        lang: () => store.getters.locale_loaded
    },
    watch: {
        dialog (val) {
            if(val === false){
                this.resetItem();
            }
        },
        lang () {
            let new_header = [];
            this.names.forEach((value,key)=>{
                new_header.push({ text: i18n.t(value), value: Object.keys(this.default_item)[key] });
                if(key + 1 === this.names.length){
                    new_header.push({ text:i18n.t("table_actions"),value:"table_actions" ,sortable:false, align:'right'});
                    this.headers = JSON.parse(JSON.stringify(new_header));
                }
            });
        }
    }
}