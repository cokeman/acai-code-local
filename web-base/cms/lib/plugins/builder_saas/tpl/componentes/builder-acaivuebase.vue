<template>
    <div v-if="data">
        <!--
        <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
            <div class="flex justify-between w-full items-center">
                <div class="w-full">
                    <div class="flex justify-between items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 flex-shrink-0 mr-4 stroke-current text-primary icon icon-tabler icon-tabler-eye-check" width="40" height="40" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                          <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                          <circle cx="12" cy="12" r="2" />
                          <path d="M12 19c-4 0 -7.333 -2.333 -10 -7c2.667 -4.667 6 -7 10 -7s7.333 2.333 10 7c-.42 .736 -.858 1.414 -1.311 2.033" />
                          <path d="M15 19l2 2l4 -4" />
                        </svg>
                        <p class="w-full text-lg leading-snug text-gray-600 font-normal"><b class="text-black">Buscador</b> : ¿Quieres mostrar el buscador en el banner?</p>
                    </div>
                    <acai-vue-list :builder="builder" :data="data" :field="'nombrelist'" @save-data="saveData"></acai-vue-list>
                </div>
            </div>
        </div>
        -->


        <!-- Ejemplos de carga de componentes - Borrar a partir de aquí lo que no se use -->
        <div>
            <!--
            generaltextfield
            generalheadfield
            generallistoptions
            generallisttable
            generallistoptionsmulti
            generallisttablemulti
            generallink
            generaltextbox
            generalwysiwyg
            generalupload
            -->

            <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                <div class="flex justify-between w-full items-center">
                    <div class="w-full">
                        <acai-vue-textfield :builder="builder" :data="data" :field="'generaltextfield'" @save-data="saveData"></acai-vue-textfield>
                    </div>
                </div>
            </div>
            <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                <div class="flex justify-between w-full items-center">
                    <div class="w-full">
                        <acai-vue-title :builder="builder" :data="data" :field="'generalheadfield'" @save-data="saveData"></acai-vue-title>
                    </div>
                </div>
            </div>
            <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                <div class="flex justify-between w-full items-center">
                    <div class="w-full">
                        <acai-vue-select :builder="builder" :data="data" :field="'generallistoptions'" @save-data="saveData"></acai-vue-select>
                    </div>
                </div>
            </div>
            <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                <div class="flex justify-between w-full items-center">
                    <div class="w-full">
                        <acai-vue-select :builder="builder" :data="data" :field="'generallisttable'" @save-data="saveData"></acai-vue-select>
                    </div>
                </div>
            </div>
            <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                <div class="flex justify-between w-full items-center">
                    <div class="w-full">
                        <acai-vue-select :builder="builder" :data="data" :field="'generallistoptionsmulti'" @save-data="saveData"></acai-vue-select>
                    </div>
                </div>
            </div>
            <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                <div class="flex justify-between w-full items-center">
                    <div class="w-full">
                        <acai-vue-select :builder="builder" :data="data" :field="'generallisttablemulti'" @save-data="saveData"></acai-vue-select>
                    </div>
                </div>
            </div>
            <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                <div class="flex justify-between w-full items-center">
                    <div class="w-full">
                        <acai-vue-linkv2 :builder="builder" :data="data" :field="'generallink'" @save-data="saveData" :show_text="true"></acai-vue-linkv2>
                    </div>
                </div>
            </div>
            <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                <div class="flex justify-between w-full items-center">
                    <div class="w-full">
                        <acai-vue-textbox :builder="builder" :data="data" :field="'generaltextbox'" @save-data="saveData"></acai-vue-textbox>
                    </div>
                </div>
            </div>
            <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                <div class="flex justify-between w-full items-center">
                    <div class="w-full">
                        <acai-vue-wysiwyg :builder="builder" :data="data" :field="'generalwysiwyg'" @save-data="saveData"></acai-vue-wysiwyg>
                    </div>
                </div>
            </div>
            <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                <div class="flex justify-between w-full items-center">
                    <div class="w-full">
                        <acai-vue-upload 
                            ref="generalupload"
                            :reference="'generalupload'"
                            :tablename="'builder_custom'" 
                            :fieldname="builder.vars['generalupload'].relations.builder_custom"
                            :recordnum="data['generalupload'].recordNum" 
                            :field="data['generalupload']"
                            :builder_field="builder.vars['generalupload']"
                            :presavetempid="data['generalupload'].preSaveTempId"
                            :add_button="true"
                            @add_button_click="$parent.openCute('generalupload',data,false,'generalupload')"
                            class="border-2 px-3 py-2 border-gray-600 rounded-lg shadow bg-gray-200">
                        </acai-vue-upload>
                    </div>
                </div>
            </div>
            
            <!--
            multitextfield
            multiheadfield
            multilistoptions
            multilisttable
            multilistoptionsmulti
            multilisttablemulti
            multilink
            multitextbox
            multiwysiwyg
            multiupload 
            -->

            <acai-vue-records :data="data" :builder="builder" :active="active" :section_id="section_id" :root_builder_vue="$parent" ref="recordsNode">
                <template v-slot="{record,index,builder,data}">
                    <div class="bg-gray-300 p-2 rounded overflow-hidden">
                        <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                            <div class="flex justify-between w-full items-center">
                                <div class="w-full">
                                    <acai-vue-textfield :builder="builder.vars.records" :data="record" :field="'multitextfield'" @save-data="saveData"></acai-vue-textfield>
                                </div>
                            </div>
                        </div>
                        <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                            <div class="flex justify-between w-full items-center">
                                <div class="w-full">
                                    <acai-vue-title :builder="builder.vars.records" :data="record" :field="'multiheadfield'" @save-data="saveData"></acai-vue-title>
                                </div>
                            </div>
                        </div>
                        <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                            <div class="flex justify-between w-full items-center">
                                <div class="w-full">
                                    <acai-vue-select :builder="builder.vars.records" :data="record" :field="'multilistoptions'" @save-data="saveData"></acai-vue-select>
                                </div>
                            </div>
                        </div>
                        <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                            <div class="flex justify-between w-full items-center">
                                <div class="w-full">
                                    <acai-vue-select :builder="builder.vars.records" :data="record" :field="'multilisttable'" @save-data="saveData"></acai-vue-select>
                                </div>
                            </div>
                        </div>
                        <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                            <div class="flex justify-between w-full items-center">
                                <div class="w-full">
                                    <acai-vue-select :builder="builder.vars.records" :data="record" :field="'multilistoptionsmulti'" @save-data="saveData"></acai-vue-select>
                                </div>
                            </div>
                        </div>
                        <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                            <div class="flex justify-between w-full items-center">
                                <div class="w-full">
                                    <acai-vue-select :builder="builder.vars.records" :data="record" :field="'multilisttablemulti'" @save-data="saveData"></acai-vue-select>
                                </div>
                            </div>
                        </div>
                        <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                            <div class="flex justify-between w-full items-center">
                                <div class="w-full">
                                    <acai-vue-linkv2 :builder="builder.vars.records" :data="record" :field="'multilink'" @save-data="saveData" :show_text="true"></acai-vue-linkv2>
                                </div>
                            </div>
                        </div>
                        <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                            <div class="flex justify-between w-full items-center">
                                <div class="w-full">
                                    <acai-vue-textbox :builder="builder.vars.records" :data="record" :field="'multitextbox'" @save-data="saveData"></acai-vue-textbox>
                                </div>
                            </div>
                        </div>
                        <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                            <div class="flex justify-between w-full items-center">
                                <div class="w-full">
                                    <acai-vue-wysiwyg :builder="builder.vars.records" :data="record" :field="'multiwysiwyg'" @save-data="saveData"></acai-vue-wysiwyg>
                                </div>
                            </div>
                        </div>
                        <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mb-3">
                            <div class="flex justify-between w-full items-center">
                                <div class="w-full">
                                    <acai-vue-upload 
                                        :ref="'upload_multiupload_' + builder.vars.records.vars.multiupload.relations.builder_custom + '_' + record.multiupload.recordNum" 
                                        :reference="'upload_multiupload_' + builder.vars.records.vars.multiupload.relations.builder_custom + '_' + record.multiupload.recordNum" 
                                        :tablename="'builder_custom'" 
                                        :fieldname="builder.vars.records.vars.multiupload.relations.builder_custom" 
                                        :recordnum="record.multiupload.recordNum"
                                        :field="record.multiupload" 
                                        :builder_field="builder.vars.records.vars.multiupload" 
                                        :presavetempid="record.multiupload.preSaveTempId" 
                                        :add_button="true" 
                                        @add_button_click="$parent.openCute('multiupload',record,true,'upload_multiupload_' + builder.vars.records.vars.multiupload.relations.builder_custom + '_' + record.multiupload.recordNum)" class="border-2 px-3 py-2 border-gray-600 rounded-lg shadow bg-gray-200">
                                    </acai-vue-upload>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </acai-vue-records>
            

            <!-- Fin de ejemplos de carga de componentes -->


            <!-- Ejemplo de vista personalizada - Borrar a partir de aquí lo que no se use -->
            <!--
            <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg">
                <div class="flex justify-between w-full items-center mb-8">
                    <div class="flex-shrink-0 pr-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-20 h-20 text-primary icon icon-tabler icon-tabler-brand-kickstarter" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                          
                              <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                              <circle cx="12" cy="12" r="1" />
                              <circle cx="12" cy="12" r="5" />
                              <line x1="15.9" y1="20.11" x2="15.9" y2="20.12" />
                              <line x1="19.04" y1="17.61" x2="19.04" y2="17.62" />
                              <line x1="20.77" y1="14" x2="20.77" y2="14.01" />
                              <line x1="20.77" y1="10" x2="20.77" y2="10.01" />
                              <line x1="19.04" y1="6.39" x2="19.04" y2="6.4" />
                              <line x1="15.9" y1="3.89" x2="15.9" y2="3.9" />
                              <line x1="12" y1="3" x2="12" y2="3.01" />
                              <line x1="8.1" y1="3.89" x2="8.1" y2="3.9" />
                              <line x1="4.96" y1="6.39" x2="4.96" y2="6.4" />
                              <line x1="3.23" y1="10" x2="3.23" y2="10.01" />
                              <line x1="3.23" y1="14" x2="3.23" y2="14.01" />
                              <line x1="4.96" y1="17.61" x2="4.96" y2="17.62" />
                              <line x1="8.1" y1="20.11" x2="8.1" y2="20.12" />
                              <line x1="12" y1="21" x2="12" y2="21.01" />

                        </svg>
                    </div>
                    <div class="w-full pl-2">
                        <p class="text-sm leading-snug text-gray-600 font-normal">Sube la imagen que desees mostrar en el módulo. Si pones un icono recuerda que sea transparente y en formato PNG.</p>
                    </div>
                </div>
                <div class="flex justify-between w-full ">
                    <div class="w-full">

                        <acai-vue-upload 
                            ref="upload_imagen"
                            :reference="'upload_imagen'"
                            :tablename="'builder_custom'" 
                            :fieldname="builder.vars.imagen.relations.builder_custom"
                            :recordnum="data.imagen.recordNum" 
                            :field="data.imagen"
                            :builder_field="builder.vars.imagen"
                            :presavetempid="data.imagen.preSaveTempId"
                            :add_button="true"
                            @add_button_click="$parent.openCute('imagen',data,false,'upload_imagen')"
                            class="border-2 px-3 py-2 border-gray-600 rounded-lg shadow bg-gray-200">
                        </acai-vue-upload>

                    </div>
                </div>
                
                
            </div>
            
            <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mt-4">
                <div class="flex justify-between w-full items-center">
                    
                    <div class="w-full">
                        <div class="flex justify-between items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 flex-shrink-0 mr-4 stroke-current text-primary icon icon-tabler icon-tabler-brand-facebook" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.2"  fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <rect x="5" y="5" width="5" height="5" rx=".5" />
                                <rect x="5" y="14" width="5" height="5" rx=".5" />
                                <path d="M14 15l3 3l3 -3" />
                                <path d="M17 18v-12" />

                            </svg>
                            
                            <p class="w-full text-lg leading-snug text-gray-600 font-normal"><b class="text-black">Orden</b> : ¿ En que lado deseas mostrar la imagen ?</p>
                        </div>
                        
                        <div class="relative mt-4 ">
                            
                            <select class="p-4 w-full appearance-none bg-gray-200 border-gray-600 border-2 rounded-lg shadow" v-model="data.ladoimagen.newValues.builder_custom.value" @change="saveData()">
                                <option v-for="(label,value) in builder.vars.ladoimagen.options.builder_custom" :value="value">{{ label }}</option>
                            </select>

                            <span class="absolute top-0 right-0 h-full w-8 pointer-events-none flex items-center justify-center mr-4">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon w-8 h-8 text-black icon-tabler icon-tabler-chevron-down" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2c3e50" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                  <polyline points="6 9 12 15 18 9" />
                                </svg>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="w-full bg-white p-8 text-gray-700 rounded-lg flex flex-wrap text-lg mt-4">
                <div class="flex justify-between w-full ">
                    <div class="w-full">
                        <div class="flex justify-between items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 flex-shrink-0 mr-4 stroke-current text-primary icon icon-tabler icon-tabler-brand-facebook" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.2"  fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                <path d="M7 12h10" />
                                <path d="M7 4v16" />
                                <path d="M17 4v16" />
                                <path d="M15 20h4" />
                                <path d="M15 4h4" />
                                <path d="M5 20h4" />
                                <path d="M5 4h4" />
                            </svg>
                            <p class="text-sm leading-snug text-gray-600 font-normal"><b class="text-black">Titulo</b> :  Escribe el texto que desees mostrar al comienzo del módulo a modo de título. Utilizando la etiqueta &lt;strong&gt; &lt;/strong&gt; </p>
                        </div>
                        <div class="relative mt-4 ">
                            <acai-vue-title :data="data" :field="'titulo'" :builder="builder" @save-data="saveData"></acai-vue-title>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between w-full items-center mt-8 ">
                    
                    <div class="w-full">
                        <div class="flex justify-between items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 flex-shrink-0 mr-4 stroke-current text-primary icon icon-tabler icon-tabler-brand-facebook" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.2"  fill="none" stroke-linecap="round" stroke-linejoin="round">
                              
                                  <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                                  <line x1="4" y1="6" x2="20" y2="6" />
                                  <line x1="4" y1="12" x2="20" y2="12" />
                                  <line x1="4" y1="18" x2="16" y2="18" />
                                
                            </svg>
                            <p class="text-lg leading-snug text-gray-600 font-normal"><b class="text-black">Columna izquierda</b> : Escribe el texto que desees mostrar en la primera columna</p>
                        </div>
                        
                        <div class="relative mt-4 ">
                           
                            <acai-vue-wysiwyg  :data="data" :label="builder.vars.textocolumna1.label" :field="'textocolumna1'" :builder="builder" @save-data="saveData"></acai-vue-wysiwyg>
                        </div>
                    </div>
                </div>
                <div class="flex justify-between w-full items-center mt-4">
                    
                    <div class="w-full">
                        <div class="flex justify-between items-center">
                            <p class="text-lg leading-snug text-gray-600 font-normal"><b class="text-black">Columna derecha</b> :  Escribe el texto que desees mostrar en la segunda columna</p>
                        </div>
                        
                        <div class="relative mt-4 ">
                            <acai-vue-wysiwyg  :data="data" :label="builder.vars.textocolumna2.label" :field="'textocolumna2'" :builder="builder" @save-data="saveData"></acai-vue-wysiwyg>
                        </div>
                    </div>
                </div>
                
            </div>
            -->
            <!-- Fin de ejemplo de vista personalizada -->

        </div>
    </div>
</template>
<style scoped></style>
<script>
    module.exports = {
        props:["active","section_id"],
        data () {
            return {
                data:null,
                builder:null,
                idiomas: IDIOMAS
            }
        },
        components: {
            'acai-vue-upload': httpVueLoader( `https://cms.cocosolution.com/lib/plugins/builder_saas/tpl/componentes/builder-acaivueupload.vue?timestamp=` + (new Date().getTime())),
            'acai-vue-records': httpVueLoader( `https://cms.cocosolution.com/lib/plugins/builder_saas/tpl/componentes/builder-acaivuerecords.vue?timestamp=` + (new Date().getTime())),
            'acai-vue-title': httpVueLoader( `https://cms.cocosolution.com/lib/plugins/builder_saas/tpl/componentes/builder-acaivuetitle.vue?timestamp=` + (new Date().getTime())),
            'acai-vue-wysiwyg': httpVueLoader( `https://cms.cocosolution.com/lib/plugins/builder_saas/tpl/componentes/builder-acaivuewysiwyg.vue?timestamp=` + (new Date().getTime())),
            'acai-vue-linkv2': httpVueLoader( `https://cms.cocosolution.com/lib/plugins/builder_saas/tpl/componentes/builder-acaivuelinkv2.vue?timestamp=` + (new Date().getTime())),
            'acai-vue-list': httpVueLoader( `https://cms.cocosolution.com/lib/plugins/builder_saas/tpl/componentes/builder-acaivuelist.vue?timestamp=` + (new Date().getTime())),
            'acai-vue-textbox': httpVueLoader( `https://cms.cocosolution.com/lib/plugins/builder_saas/tpl/componentes/builder-acaivuetextbox.vue?timestamp=` + (new Date().getTime())),
            'acai-vue-textfield': httpVueLoader( `https://cms.cocosolution.com/lib/plugins/builder_saas/tpl/componentes/builder-acaivuetextfield.vue?timestamp=` + (new Date().getTime())),
            'acai-vue-select': httpVueLoader( `https://cms.cocosolution.com/lib/plugins/builder_saas/tpl/componentes/builder-acaivueselect-prueba.vue?timestamp=` + (new Date().getTime())),
        },
        mounted(){
            this.$emit("child-mounted");
        },
        methods:{
            saveData(){
                this.$emit("save-data");
            }
        }
    }
</script> 