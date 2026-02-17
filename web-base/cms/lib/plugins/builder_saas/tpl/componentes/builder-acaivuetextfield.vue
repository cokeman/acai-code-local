<template>
    <div class="relative">
        <input type="text" :placeholder="placeholder" class="p-4 w-full text-sm appearance-none bg-gray-200 border-gray-600 border-2 rounded-lg shadow" v-model="data[field].newValues.builder_custom.value" @change="saveData()">
        <div v-if="idiomas && Object.keys(idiomas).length > 1" class="absolute top-0 right-0">
            <button @click="idiomaLink(field)" class="bg-transparent text-gray-500 p-0 mt-1 mr-1">
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
