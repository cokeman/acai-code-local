<template>
    <div>

        <ul v-if="layout" @click="modal=true" class="flex flex-col lg:flex-row justify-start p-2 w-full appearance-none bg-gray-100 border-gray-600 border-2 rounded-lg shadow hover:bg-gray-300 cursor-pointer">

            <img :src="getLayout('thumbnail')" class="flex-shrink-0 w-full lg:w-48 xl:w-48 rounded shadow-lg overflow-hidden mr-4 object-cover">
            <div class="text-sm flex items-center justify-center lg:justify-start relative w-full mt-4 font-bold lg:mt-0 overflow-hidden">
                {{ getLayout('label') }} 
            </div>
            <span class="w-8 mx-auto lg:mr-2 pointer-events-none flex-shrink-0 flex items-center justify-center opacity-50">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon w-8 h-8 text-black icon-tabler icon-tabler-chevron-down" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <polyline points="6 9 12 15 18 9" />
                </svg>
            </span>
        </ul>

        <div v-if="modal" class="fixed top-0 left-0 w-full h-full bg-gray-200 overflow-scroll pt-10 pb-20" style="z-index:5000">
            <div class="container mx-auto max-w-6xl px-6 lg:px-0">
                <svg xmlns="http://www.w3.org/2000/svg" @click="modal = false" class="mb-10 cursor-pointer hover:text-gray-600 mx-auto icon icon-tabler icon-tabler-x w-12 h-12 stroke-current" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                  <line x1="18" y1="6" x2="6" y2="18" />
                  <line x1="6" y1="6" x2="18" y2="18" />
                </svg>
                <ul class="w-full flex-wrap flex">
                    <li v-for="element in layout" class="w-full lg:w-1/2 px-4">
                        <div @click="clickHandler(element)" class="bg-white rounded-lg overflow-hidden mb-10 shadow-lg cursor-pointer">
                            <div class="border-b-2 border-gray-400 dark:border-body-color dark:border-opacity-50 overflow-hidden">
                                <div class="relative w-full pt-48 pb-32">
                                    <div style="display:block;overflow:hidden;position:absolute;top:0;left:0;bottom:0;right:0;box-sizing:border-box;margin:0">
                                        <img alt="preview" class="object-cover" :src="element.thumbnail + '?timestamp=' + getMyTime()" style="position:absolute;top:0;left:0;bottom:0;right:0;box-sizing:border-box;padding:0;border:none;margin:auto;display:block;width:0;height:0;min-width:100%;max-width:100%;min-height:100%;max-height:100%" />
                                        
                                    </div>
                                </div>
                            </div>
                            <div class="p-6 flex items-center justify-between">
                                <span class="font-semibold text-lg pr-4 text-black hover:text-primary hover:text-primary dark:text-white">{{ element.label }}</span>
                                <div class="py-2 px-4 rounded flex items-center justify-center text-white font-medium uppercase" :class="{'bg-red-500':element.id == data.layout.newValues.builder_custom.value,'bg-theme':element.id != data.layout.newValues.builder_custom.value}">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-click w-8 h-8 stroke-current" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                      <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                      <line x1="3" y1="12" x2="6" y2="12" />
                                      <line x1="12" y1="3" x2="12" y2="6" />
                                      <line x1="7.8" y1="7.8" x2="5.6" y2="5.6" />
                                      <line x1="16.2" y1="7.8" x2="18.4" y2="5.6" />
                                      <line x1="7.8" y1="16.2" x2="5.6" y2="18.4" />
                                      <path d="M12 12l9 3l-4 2l-2 4l-3 -9" />
                                    </svg>
                                    <span class="pl-1">{{ element.id == data.layout.newValues.builder_custom.value ? 'ELEGIDO' : 'ELEGIR' }}</span>
                                </div>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</template>
<script>
    module.exports = {
        props:['data','builder','layout','save-data'],
        data(){
            return{
                modal:false,
                selected:1
            }
        },
        mounted(){
            if (this.data.layout && this.data.layout.newValues && this.data.layout.newValues.builder_custom && !this.data.layout.newValues.builder_custom.value){
                this.data.layout.newValues.builder_custom.value = this.layout[0].id;
                this.data.layout.value = this.layout[0].id;
                this.$emit("save-data");
            }
        },
        methods:{
            clickHandler(el){
                let antData = this.data.layout.newValues.builder_custom.value;
                this.data.layout.newValues.builder_custom.value = el.id;
                this.data.layout.value = el.id;
                this.modal = false;
                var modulo = myConfig.find((rec) => {
                    return rec.builder.id == this.$parent.builder.id;
                });
                if (modulo){
                    var img = modulo.node.querySelector("img");
                    if (img && img.src.indexOf('thumbnail_') > -1) img.src = img.src.replace("thumbnail_" + antData + ".jpg","thumbnail_" + el.id + ".jpg");
                    if (img && img.src.indexOf('thumbnail.jpg') > -1) img.src = img.src.replace("thumbnail.jpg","thumbs/thumbnail_" + el.id + ".jpg");
                }
                this.$emit("save-data");
            },
            getLayout(field){
                var existe = this.layout.find(rec => rec.id == this.data.layout.newValues.builder_custom.value);
                
                return existe ? existe[field] : this.layout[0][field];
            },
            getMyTime(){
                return new Date().getTime();
            }
        }
    };
</script>