<template>
    <div class="flex justify-between relative">
        <div class="relative flex-shrink-0 text-sm ">
            <select class="p-4 w-full appearance-none bg-gray-100 border-gray-600 border-2 border-r-0 pr-10 rounded-l-lg shadow" v-if="data[field + '_tag'] && data[field + '_tag'].newValues" v-model="data[field + '_tag'].newValues.builder_custom.value" @change="saveData()">
                <option value="">Párrafo</option>
                <option value="H1">Encabezado 1</option>
                <option value="H2">Encabezado 2</option>
                <option value="H3">Encabezado 3</option>
                <option value="H4">Encabezado 4</option>
                <option value="H5">Encabezado 5</option>
                <option value="H6">Encabezado 6</option>
            </select>

            <span class="absolute top-0 right-0 h-full w-6 pointer-events-none flex items-center justify-center mr-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon w-6 h-6 text-black icon-tabler icon-tabler-chevron-down" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <polyline points="6 9 12 15 18 9" />
                </svg>
            </span>
        </div>
        <input type="text" :placeholder="placeholder" class="p-4 w-full text-sm appearance-none bg-gray-200 border-gray-600 border-2 rounded-r-lg shadow" v-if="data[field] && data[field].newValues" v-model="data[field].newValues.builder_custom.value" @change="saveData()">
        <div v-if="idiomas && Object.keys(idiomas).length > 1" class="absolute top-0 right-0">
            <button @click="idiomaLink" class="bg-transparent text-gray-500 p-0 mt-1 mr-1">
                <i class="fa fa-globe"></i>
            </button>
        </div>
    </div>
</template>
<script>
    module.exports = {
        props: [
            'builder',
            'data',
            'field',
            'placeholder'
        ],
        data(){
            return {
                idiomas: IDIOMAS,
            }
        },
        mounted() {
            if (!this.data[this.field + '_tag']){
                Vue.set(this.data,this.field + '_tag',{
                    newValues:{
                        builder_custom:{
                            value:''
                        }
                    }
                })
            }
        },
        methods: {
            saveData() {
                this.$emit("save-data");
            },
            idiomaLink() {
                tb_show("Translate", `?menu=${this.data[this.field].tableName}&action=translateModify&relationsFieldName=${this.builder.vars[this.field].relations.builder_custom}&fieldName=${this.field}&num=${this.data[this.field].recordNum ? this.data[this.field].recordNum : ''}&preSaveTempId=${this.data[this.field].preSaveTempId}&type=textfield&TB_iframe=true&width=900&height=600&modal=true`);
            }
        }
    };
</script>
