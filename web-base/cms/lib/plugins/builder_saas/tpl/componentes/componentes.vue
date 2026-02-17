<template>
    <div class="h-full flex flex-col bg-gray-300 overflow-auto p-12">
        <div id="docsModule">
            <ul class="lista-tabs flex pl-0">
                <li v-for="(page,index) in DocsPages"><button @click="activateDocTab(index)" class="p-4 px-12 text-3xl" :class="{'bg-white text-black' : docsActivated == index,'bg-gray-200 text-gray-600' : docsActivated != index}">{{page.title}}</button></li>
            </ul>
            <div v-for="(page,index) in DocsPages" v-if="docsActivated == index" class="p-20 bg-white">
                <ul class="lista-componentes pl-0">
                    <li v-for="(component, key) in page.data" class="flex flex-wrap mb-2 py-8">
                        <h3 class="text-black text-4xl font-normal w-2/12">{{key}}</h3>
                        <div class="w-10/12">
                            <p class="text-lg text-3xl text-gray-600">{{component.description}}</p>    
                            <pre class="w-full bg-gray-400 mt-8 rounded border-none p-8 text-md text-gray-700">{{component.example}}</pre>
                        </div>

                    </li>
                </ul>

            </div>
        </div>
    </div>
</template>
<script>
    module.exports = {
        data:function(){
            return {
                DocsPages:[
                    {
                        title:"Componentes",
                        data:appParser.components
                    },
                    {
                        title:"Filtros",
                        data:appParser.filters
                    },
                    {
                        title:"BuilderData",
                        data:appParser.builderData
                    }
                ],
                docsActivated:0
            }
        },
        methods:{
            activateDocTab:function(index){
                this.docsActivated = index;
            }
        }
    }
</script>