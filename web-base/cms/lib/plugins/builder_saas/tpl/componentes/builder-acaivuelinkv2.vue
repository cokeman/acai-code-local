<template>
    <div>
        <div class="relative flex justify-between">
            <div class="relative flex-shrink-0 flex" >
                <select v-model="link.selectedType" @change="changeType" class="p-4 w-full appearance-none pr-16 text-sm bg-gray-100 border-gray-600 border-2 border-r-0 rounded-l-lg shadow" v-model="data[field].newValues.builder_custom.value" >
                    <option value="">Enlace externo</option>
                    <option value="1">Enlace a módulo</option>
                    <option value="3">Enlace a página</option>
                </select>

                <span class="absolute top-0 right-0 h-full w-8 pointer-events-none flex items-center justify-center mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon w-8 h-8 text-black icon-tabler icon-tabler-chevron-down" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                      <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                      <polyline points="6 9 12 15 18 9" />
                    </svg>
                </span>
            </div>
            <input v-if="!link.selectedType" type="text" v-model="link.label" placeholder="https://www.midominio.com" class="p-4 w-full appearance-none bg-gray-200 border-gray-600 border-2 rounded-r-lg shadow">
            
            <input v-if="link.selectedType == 3" type="text" v-model="link.labelHTML" readonly placeholder="Enlace a página" class="p-4 w-full text-xs truncate appearance-none bg-gray-200 border-gray-600 border-2 rounded-r-lg shadow">

            <button v-if="link.selectedType == 3" @click="modalLink" class="p-4 flex-shrink-0 ml-2 appearance-none bg-theme text-white border-transparent border-2 rounded-lg shadow">Elegir</button>
            
            <div v-if="link.selectedType == 1" class="relative flex w-full" >

                <select v-model="link.label" class="p-4 w-full appearance-none pr-16 bg-gray-200 border-gray-600 border-2 rounded-r-lg shadow" v-model="data[field].newValues.builder_custom.value" >
                    <option value="">Elige módulo</option>
                    <option v-for="module in modules" :value="module.value">{{ module.label }}</option>
                </select>

                <span class="absolute top-0 right-0 h-full w-8 pointer-events-none flex items-center justify-center mr-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon w-8 h-8 text-black icon-tabler icon-tabler-chevron-down" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                      <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                      <polyline points="6 9 12 15 18 9" />
                    </svg>
                </span>
            </div>
            <div v-if="!link.selectedType && idiomas && Object.keys(idiomas).length > 1" class="absolute top-0 right-0">
                <button @click="idiomaLink(field)" class="bg-transparent text-gray-500 p-0 mt-1 mr-1">
                    <i class="fa fa-globe"></i>
                </button>
            </div>
        </div>
        <input type="text" ref="enlace" v-model="data" class="text-xs appearance-none bg-transparent w-full" :class="{'hidden' :!indev}" readonly>
        
        <div v-if="show_text" class="w-full">
            <div class="relative mt-4 flex flex-col">
                <p class="text-sm flex-shrink-0 text-gray-600 mb-4">Texto del enlace : Escribe el texto que desees mostrar en el enlace</p>
                <div class="relative">
                    <input type="text" class="p-4 w-full appearance-none bg-gray-200 border-gray-600 border-2 rounded-lg shadow" placeholder="Texto del enlace" v-model="data[field + '_anchor'].newValues.builder_custom.value" @change="sendData()">
                    <div v-if="idiomas && Object.keys(idiomas).length > 1" class="absolute top-0 right-0">
                        <button @click="idiomaLink(field + '_anchor')" class="bg-transparent text-gray-500 p-0 mt-1 mr-1">
                            <i class="fa fa-globe"></i>
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>
<script>
    module.exports = {
        props:['data','builder','field','indev','save-data','show_text'],
        data(){
            return{
                link:{
                    selectedType:'',
                    label:"",
                    labelHTML:""
                },
                modules:[],
                idiomas: IDIOMAS
            }
        },
        watch:{
            'link.label':function(newVal,oldVal){
                this.sendData();
            }
        },
        mounted(){
            // console.log('mounted linkv2', JSON.parse(JSON.stringify({data: this.data, builder:this.builder, field: this.field})));
            this.modules = [];
            for (let cont in myConfig){
                this.modules.push({label:myConfig[cont].label,value:"#" + myConfig[cont].section_id});
            }
            this.parseLink();
            
            this.$refs.enlace.addEventListener("change",(e) => {
                this.link.label = e.target.value;
                this.link.selectedType = 3;
                
                modalLinkBuilder.getData(e.target.value).then((json) => {
                    this.link.labelHTML = json.length > 0 ? json[0].enlace : "Indefinido";
                });

                this.sendData();
            })
        },
        methods:{
            parseLink(){
                if (!this.data[this.field].newValues.builder_custom.value){
                    this.link.label = "";
                    return;
                }

                var sep = this.data[this.field].newValues.builder_custom.value.split("|");

                if (sep[1]){
                    this.link.label = sep[1];
                    this.link.selectedType = sep[0];
                }else{
                    this.link.label = sep[0];
                    this.link.selectedType = "";
                }
                
                if (this.link.selectedType == "3"){
                    modalLinkBuilder.getData(this.link.label).then((json) => {
                        this.link.labelHTML = json.length > 0 ? json[0].enlace : "Indefinido";
                    });
                }
                this.sendData();
            },
            modalLink(){
                modalLinkBuilder.open(this.$refs.enlace);
            },
            changeType(){
                
                switch(this.link.selectedType){
                    case "1":
                        this.link.label = this.modules[0].value;
                        break;
                    default:
                        this.link.label = ``;
                }
                
                this.sendData();
            },
            sendData(){
                this.data[this.field].newValues.builder_custom.value = (this.link.selectedType && this.link.label ? `${this.link.selectedType}|` : ``) + this.link.label;
                this.$emit("save-data");
            },
            idiomaLink(field) {
                if(!field) field = this.field;
                tb_show("Translate", `?menu=${this.data[field].tableName}&action=translateModify&relationsFieldName=${this.builder.vars[field].relations.builder_custom}&fieldName=${field}&num=${this.data[field].recordNum ? this.data[field].recordNum : ''}&preSaveTempId=${this.data[field].preSaveTempId}&type=textfield&TB_iframe=true&width=900&height=600&modal=true`);
            }
        }
    };
</script>