import axios from 'axios'
import i18n from '~/plugins/i18n'
import store from '~/store'
import Confirm from '~/components/Confirm'
import FilterResults from '~/components/FilterResults'

/**
 * Crud Mixin
 * Usage: Add this mixin to provide crud actions
 */
export default {
    data: function () {
        return {
            items: [],
            item: {},
            loading: true,
            totalItems : 0,
            pagination:{},
            search: '',
            dialog: false,
            default_item: {},
            headers: [],
            names: [],
            filter:{},
        }
    },
    components: { Confirm, FilterResults },
    methods: {
        filterChanged(e){
            this.filter = Object.assign({}, e);
        },
        loadFilters(item_obj){
            this.$refs.filtered.loadFilters(item_obj);
        },
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
                            this[value.list] = response.data;
                        });
                });
            }
            this.resource = resource;
            this.item = item_obj;
            this.default_item = JSON.parse(JSON.stringify(item_obj));
        },

        getItems(){
            this.loading = true
            return new Promise((resolve, reject) => {
                axios.get(this.resource,{
                    params: {
                        page:this.pagination.page,
                        per_page:this.pagination.rowsPerPage,
                        filter:this.filter
                    },
                })
                    .then(response => {
                        const { descending, page, rowsPerPage } = this.pagination;

                        let items = response.data.data;
                        const total = response.data.total;

                        this.loading = false;
                        resolve({
                            items,
                            total
                        });
                    });
            })
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
                        this.getItems()
                            .then(data => {
                                this.items = data.items;
                                this.totalItems = data.total;
                            })
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
                        this.getItems()
                            .then(data => {
                                this.items = data.items;
                                this.totalItems = data.total;
                            })
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
        },
        pagination: {
            handler () {
                this.getItems()
                    .then(data => {
                        this.items = data.items;
                        this.totalItems = data.total;
                    })
            },
            deep: true
        },
        filter:{
            handler () {
                this.getItems()
                    .then(data => {
                        this.items = data.items;
                        this.totalItems = data.total;
                    })
            },
            deep: true
        },
    }
}