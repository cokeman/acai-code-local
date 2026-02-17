<template>
    <div class="flex flex-col justify-between border-2 px-2 py-2 border-gray-600 rounded-lg shadow bg-gray-200 relative">
        <div ref="wysiwyg" class="ckeditor" :name="field" contenteditable="true" placeholder="label" v-html="data[field].newValues.builder_custom.value"></div>
        <div v-if="idiomas && Object.keys(idiomas).length > 1" class="absolute top-0 right-0">
            <button @click="idiomaLink" class="bg-transparent text-gray-500 p-0 mt-1 mr-1">
                <i class="fa fa-globe"></i>
            </button>
        </div>
    </div>
</template>
<style scoped>
    .ck-editor{ 
        border-radius: 0.5rem;
        overflow: hidden;
    }
</style>
<script>
    module.exports = {
        props:['data','field','builder','save-data','label'],
        data(){
            return{
                idiomas: IDIOMAS
            }
        },
        mounted(){
            var node = this.$refs.wysiwyg;
            CKEDITOR_COCO_Start(null,[node],(resultData) => {
                this.data[this.field].newValues.builder_custom.value = resultData;
                this.$emit("save-data");
            });
        },
        methods:{
            saveData(){
                this.$emit("save-data");
            },
            idiomaLink() {
                tb_show("Translate", `?menu=${this.data[this.field].tableName}&action=translateModify&relationsFieldName=${this.builder.vars[this.field].relations.builder_custom}&fieldName=${this.field}&num=${this.data[this.field].recordNum ? this.data[this.field].recordNum : ''}&preSaveTempId=${this.data[this.field].preSaveTempId}&type=wysiwyg&TB_iframe=true&width=900&height=600&modal=true`);
            }
        }
    };
</script>