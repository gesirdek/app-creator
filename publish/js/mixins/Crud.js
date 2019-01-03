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
            searchText: "",
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
            search_meta:[],
            multiples:[],
            parent_id:-1,
            delayKeyUp:(function(){
                var timer = 0;
                return function(callback, ms){
                    clearTimeout (timer);
                    timer = setTimeout(callback, ms);
                };
            })(),
        }
    },
    components: { Confirm },
    created(){
        this.getSearchMetaList();
    },
    methods: {
        getSearchMetaList(){
            let local = this;
            axios.get('/api/search-key')
                .then(res => {
                    local.search_meta = res.data;
                })
                .catch(err => {
                    console.log(err)
                })//.finally(() => ( ));

        },
        getGroupTitle(groupable_type, ){
            if(groupable_type !== undefined && groupable_type !== ''){
                let foreign_key = groupable_type.split("\\")[3].split(/(?=[A-Z])/).join('_').toLowerCase() + "_id";
                return this.search_meta.find(x => x.foreign_key === foreign_key).search_in;
            }
            return "";
        },
        remove_chip (item,list) {
            let index = list.indexOf(item);
            if (index >= 0) list.splice(index, 1)
        },
        loadThis(api, list, item, event){
            let value = '';
            if(event.target === undefined){
                value = event;
            }else{
                value = event.target.value;
            }

            this.delayKeyUp(
                function () {
                    if(value.length > 1){
                        axios.get(api,{
                            params: {
                                keyword: value
                            },
                        })
                            .then(res => {
                                list[item] = res.data;
                            })
                            .catch(err => {
                                console.log(err)
                            })
                    }
                },10);
        },
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
                this.multiples = multiples;
                multiples.forEach((value)=>{
                    if(value.list === 'parent_list' || typeof value.preload!=='undefined'){
                        axios.get(value.source)
                            .then(response => {
                                this[value.list] = response.data;
                            });
                    }else{
                        this.$watch(value.list+'_search',
                            _.debounce(function () {
                                if(this[value.list+'_search']!=null && this[value.list+'_search'].length > 0){
                                    axios.get(value.source,{
                                        params: {
                                            page:1,
                                            per_page:5,
                                            keyword: this[value.list+'_search'],
                                            filter:{name:'name'}
                                        },
                                    }).then(response => {
                                        this[value.list] = response.data.data;
                                    })
                                }
                            }, 1000)
                        );
                    }
                });
            }
            this.resource = resource;
            this.item = item_obj;
            this.default_item = JSON.parse(JSON.stringify(item_obj));
        },

        getItems(){
            this.loading = true;
            let localthis = this;


            return new Promise((resolve, reject) => {
                axios.get(this.resource,{
                    params: {
                        parent_id:(this.parent_id ? this.parent_id: 0 ),
                        page:this.pagination.page,
                        per_page:this.pagination.rowsPerPage,
                        keyword: this.searchText,
                        filter:this.filter,
                        sort_by:this.pagination.sortBy,
                        sort_dir:this.pagination.descending,
                    },
                })
                    .then(response => {
                        const { sortBy, descending, page, rowsPerPage } = this.pagination;
                        localthis.pagination.totalItems = response.data.total;

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
            //todo with ile gelenleri id listesine çevirmeli mi çevirmemeli mi?
            this.multiples.forEach(value=>{
                this[value.list] = _.union(this[value.list],this.item[value.list]);
                if(typeof this.item[value.list] !== 'undefined')
                    this.item[value.list] = this.item[value.list].map(x=>x.id)
            });
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
            this.$validator.validateAll().then(passes=> {
                if (typeof this.item.id === 'undefined' || this.item.id === 0) {
                    axios.post(this.resource, this.item)
                        .then(response => {
                            this.getItems()
                                .then(data => {
                                    this.items = data.items;
                                    this.totalItems = data.total;
                                })
                            this.resetItem();
                            this.dialog = false;
                            this.$store.dispatch("setAllSnackbar", {
                                snackbar: true,
                                message: i18n.t("app.snackbar_saved"),
                                duration: 3000
                            });
                        })
                } else {
                    axios({
                        method: 'PUT',
                        url: this.resource + '/' + this.item.id,
                        data: this.item
                    })
                        .then(response => {
                            this.getItems()
                                .then(data => {
                                    this.items = data.items;
                                    this.totalItems = data.total;
                                })
                            this.resetItem();
                            this.dialog = false;
                            this.$store.dispatch("setAllSnackbar", {
                                snackbar: true,
                                message: i18n.t("app.snackbar_updated"),
                                duration: 3000
                            });
                        })
                }
            });
        },
        resetItem(){
            this.item=this.default_item
        },
        deleteConfirm(i){
            this.$refs.confirm.open(i)
        },
        setParent(id){
            this.parent_id = id;
            this.item.parent_id = id;
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
        parent_id: {
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
        currentSort:{
            handler () {
                this.getItems()
                    .then(data => {
                        this.items = data.items;
                        this.totalItems = data.total;
                    })
            },
            deep: true
        },
        currentSortDir:{
            handler () {
                this.getItems()
                    .then(data => {
                        this.items = data.items;
                        this.totalItems = data.total;
                    })
            },
            deep: true
        },
        searchText:
            _.debounce(function () {
                this.getItems()
                    .then(data => {
                        this.items = data.items;
                        this.totalItems = data.total;
                    })
            }, 1000)
    }
}