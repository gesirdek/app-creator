<template>
    <v-expansion-panel>
        <v-expansion-panel-content>
            <div slot="header">{{ $t("filtering.title") }}</div>
            <v-container fluid grid-list-xl>
                <template v-for="(statement, index) in statements">
                    <v-layout row wrap>
                        <v-flex xs12 offset-sm4 sm2 d-flex v-if="statement.operator != '' && index > 0">
                            <v-btn depressed small color="error" v-model="statement.btn" v-on:click="removeFilters(index)">{{ statement.operator }}</v-btn>
                        </v-flex>
                    </v-layout>
                    <v-layout row wrap>
                        <v-flex xs12 sm4 d-flex>
                            <v-select
                                    v-model="statement.field"
                                    :items="statement_fields"
                                    label="$t('filtering.fields')"
                            ></v-select>
                        </v-flex>
                        <v-flex xs12 sm3 d-flex>
                            <v-select
                                    v-model="statement.comparison"
                                    :items="statement_comparisons"
                                    label="$t('filtering.compare')"
                            ></v-select>
                        </v-flex>
                        <v-flex xs12 sm3 d-flex>
                            <v-text-field
                                    placeholder="$t('filtering.value')"
                                    v-model="statement.value"
                            ></v-text-field>
                        </v-flex>
                    </v-layout>

                </template>
                <v-layout row wrap>
                    <v-flex xs12 sm1 d-flex>
                        <v-select
                                v-model="statement.operator"
                                :items="statement_operators"
                                item-text="name"
                                item-value="value"
                                label="$t('filtering.operator')"
                        ></v-select>
                    </v-flex>
                    <v-flex xs12 sm1 d-flex>
                        <v-btn depressed small color="primary" v-on:click="cloneFilters">+</v-btn>
                    </v-flex>
                </v-layout>
                <v-layout row wrap>
                    <v-flex xs12 sm2 d-flex>
                        <v-btn depressed small color="primary" v-on:click="setFilters">{{ $t('filtering.button') }}</v-btn>
                    </v-flex>
                </v-layout>

            </v-container>

        </v-expansion-panel-content>
    </v-expansion-panel>
</template>


<script>
    import i18n from '~/plugins/i18n'
    export default {
        name:'FilterResults',
        props: {
            filter:{},
        },
        data() {
            return {
                statements:[],
                statement_comparisons:[i18n.t('filtering.like'), '=', '>', '<','>=','<='],
                statement_fields:[],
                statement_operators:[{name:i18n.t('filtering.and_operator'),value:'and'},{name:i18n.t('filtering.or_operator'),value:'or'}],
                statement:{field:'',comparison:'',value:'',operator:i18n.t('filtering.and_operator'),btn:{}},
            }
        },

        methods: {
            setFilters(){
                $emit('filterChanged', this.statements);
            },
            cloneFilters(){
                this.statements.push(Object.assign({},this.statement));
            },
            removeFilters(i){
                this.statements.splice(i,1);
            },
            loadFilters(item_obj){
                this.statement_fields = Object.keys(Object.assign({}, item_obj));
                this.statements.push(Object.assign({},this.statement));
            },
        }
    }
</script>