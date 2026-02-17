<template>
    <div class="relative">
        <!--Componente nuevo-->
        <vue-select
            v-if="multiple"
            multiple
            class="border-2 border-gray-600 rounded-lg multiple"
            v-model="selected_values"
            :options="tableRecords"
            :off___options="paginated"
            :off___filterable="false"
            @off___search="onSearch"
            :label="label"
            :reduce="(option) => option[value]"
            :clearable="false"
            @input="onInput"
        >
            <li off___slot="list-footer" class="pagination">
              <button :disabled="!hasPrevPage" @click="offset -= limit">Prev</button>
              <button :disabled="!hasNextPage" @click="offset += limit">Next</button>
            </li>
        </vue-select>
        <vue-select
            v-else
            class="border-2 border-gray-600 rounded-lg"
            v-model="selected_values"
            :options="tableRecords"
            :off___options="paginated"
            :off___filterable="false"
            @off___search="onSearch"
            :label="label"
            :reduce="(option) => option[value]"
            :clearable="false"
            @input="onInput"
        >
            <li off___slot="list-footer" class="pagination">
              <button :disabled="!hasPrevPage" @click="offset -= limit">Prev</button>
              <button :disabled="!hasNextPage" @click="offset += limit">Next</button>
            </li>
        </vue-select>
    </div>
</template>

<script>
    module.exports = {
        components: {
            "vue-select": VueSelect.VueSelect,
        },
        props: [
            "builder",
            "data",
            "field",
            "options"
        ],
        data() {
            return {
                selected_values: '',
                tableRecords: [],
                search: '',
                offset: 0,
                limit: 10,
            };
        },
        created() {
            if(typeof this.data[this.field].newValues.builder_custom.value == 'undefined') {
                Vue.set(this.data[this.field].newValues.builder_custom, 'value', '');
            }
            /*
            console.log('created', {
                builder: this.builder,
                data: this.data,
                field: this.field,
            });
            /**/
            if (this.builder.vars[this.field].type === 'list') {
                const method = this.query ? "query" : "get";
                const param = this.query ? this.query : this.table;

                if(param) {
                    if(this.table) {
                        console.log('tipo list tabla:', this.builder.vars[this.field]);
                        Rest[method](param).then((response) => {
                            if(!this.multiple) {
                                let valor_vacio = {};
                                valor_vacio[this.label] = '(Sin valor asignado)';
                                valor_vacio[this.value] = '';
                                this.tableRecords = [valor_vacio].concat(response.data);
                            } else {
                                this.tableRecords = response.data;
                            }
                        })
                    } else {
                        // Query no ha sido probado.
                        let tableQuery = this.builder.vars[this.field].options.builder_custom.query;
                        Rest.query(tableQuery).then((response) => {
                            if(!response || !response.data || !response.data[0]) {
                                this.tableRecords = [];
                                return;
                            }
                            if(!this.multiple) {
                                let valor_vacio = {};
                                valor_vacio[this.label] = '(Sin valor asignado)';
                                valor_vacio[this.value] = '';
                                this.tableRecords = [valor_vacio].concat(response.data);
                            } else {
                                let tableLabel = Object.keys(response.data[0])[1];
                                let tableValue = Object.keys(response.data[0])[0];
                                this.tableRecords = response.data;
                            }
                        });
                    }
                    
                } else {
                    console.log('tipo list manual:', this.builder.vars[this.field]);
                    let all_values = Object.keys(this.builder.vars[this.field].options.builder_custom);
                    let all_labels = Object.values(this.builder.vars[this.field].options.builder_custom);
                    for (var i = 0; i < all_values.length; i++) {
                        let valor_posible = {};
                        valor_posible[this.label] = all_labels[i];
                        valor_posible[this.value] = all_values[i];
                        this.tableRecords.push(valor_posible);
                    }

                    if(!this.multiple) {
                        let valor_vacio = {};
                        valor_vacio[this.label] = '(Sin valor asignado)';
                        valor_vacio[this.value] = '';
                        this.tableRecords.unshift(valor_vacio);
                    }
                }

                if (this.multiple) {
                    this.selected_values = this.selectedValues();
                } else {
                    if(typeof this.data[this.field].newValues.builder_custom.value !== 'undefined') {
                        this.selected_values = this.data[this.field].newValues.builder_custom.value;
                    } else {
                        this.selected_values = '';
                    }
                }
            }
        },
        computed: {
            multiple() {
                return this.builder.vars[this.field].multi;
            },
            table() {
                return this.builder.vars[this.field].options.builder_custom.tableName;
            },
            label() {
                if(this.query) {
                    // Query no ha sido probado.
                    if(this.tableRecords.length === 0) {
                        return null;
                    } else {
                        return Object.keys(this.tableRecords[0])[1];
                    }
                }
                if(this.table) {
                    return this.builder.vars[this.field].options.builder_custom.fieldLabel;
                }
                return 'label';
            },
            value() {
                if(this.query) {
                    // Query no ha sido probado.
                    if(this.tableRecords.length === 0) {
                        return null;
                    } else {
                        return Object.keys(this.tableRecords[0])[0];
                    }
                }
                if(this.table) {
                    return this.builder.vars[this.field].options.builder_custom.fieldValue;
                }
                return 'value';
            },
            query() {
                return this.builder.vars[this.field].options.builder_custom.query;
            },
            filtered() {
                return this.tableRecords;
              return this.tableRecords.filter((each_record) =>
                each_record[this.value].toLocaleLowerCase().includes(this.search.toLocaleLowerCase())
              )
            },
            paginated() {
              return this.filtered.slice(this.offset, this.limit + this.offset)
            },
            hasNextPage() {
              const nextOffset = this.offset + this.limit
              return Boolean(
                this.filtered.slice(nextOffset, this.limit + nextOffset).length
              )
            },
            hasPrevPage() {
              const prevOffset = this.offset - this.limit
              return Boolean(
                this.filtered.slice(prevOffset, this.limit + prevOffset).length
              )
            },
        },
        methods: {
            onInput() {
                let value = '';
                if(this.multiple) {
                    value = this.selected_values.length === 0 ? "" : ["", ...this.selected_values, ""].join("\t");
                } else {
                    value = this.selected_values ? this.selected_values : "";
                }
                // Anael: Esto para el guardado de los normales
                this.data[this.field].newValues.builder_custom.value = value;
                // Anael: Esto para el guardado de los multi. ¿Por qué? Ah...
                this.data[this.field].value = value;
                this.$emit("save-data");
            },
            selectedValues() {
              const value = this.data[this.field].newValues.builder_custom.value;
              return (value && value.trim() !== "") ? value.trim().split("\t") : [];
            },
            onSearch(query) {
              this.search = query
              this.offset = 0
            },
        },
    };
</script>

<style scoped>
    .vs__dropdown-toggle{background: #edf2f7;}
    .multiple .vs__selected {background-color: #ffffff;border: 1px solid rgb(113 128 150); font-size:.8em;}
    .vs__dropdown-toggle { padding:10px; }
</style>
