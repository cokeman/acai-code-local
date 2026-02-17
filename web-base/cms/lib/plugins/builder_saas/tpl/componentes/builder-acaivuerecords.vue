<template>
    <div class="flex justify-between mt-4">
        <div class="flex flex-col mb-12">
            
            <ul class="flex-shrink-0 flex flex-col ">
                <li>
                    <button @click="root_builder_vue.newRecord()" class="hover:bg-black bg-theme py-2 px-2 rounded-l-lg text-white shadow-lg text-xl mb-1 shadow-inner cursor-pointer hover:bg-white" >
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-10 h-10 flex-shrink-0 stroke-current text-white icon icon-tabler icon-tabler-brand-facebook" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.2"  fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <line x1="9" y1="12" x2="15" y2="12" />
                            <line x1="12" y1="9" x2="12" y2="15" />
                            <path d="M4 6v-1a1 1 0 0 1 1 -1h1m5 0h2m5 0h1a1 1 0 0 1 1 1v1m0 5v2m0 5v1a1 1 0 0 1 -1 1h-1m-5 0h-2m-5 0h-1a1 1 0 0 1 -1 -1v-1m0 -5v-2m0 -5" />
                        </svg>
                    </button>
                </li>
            </ul>
            
            <draggable v-model="data.records" class="flex-shrink-0 flex flex-col">
                
                <li v-for="record,index in data.records" :key="ids[index]">
                    <div class="border border-gray-400 border-r-0 bg-white py-2 px-4 flex justify-center items-center rounded-l-lg text-gray-700 shadow-lg text-xl mb-1 shadow-inner cursor-pointer hover:bg-white" @click="selectedIndex = index" :class="{'bg-gray-400':selectedIndex != index}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 -ml-4 text-gray-500 flex-shrink-0 stroke-current icon icon-tabler icon-tabler-brand-facebook" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.2"  fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                            <circle cx="12" cy="12" r="1" />
                            <circle cx="12" cy="19" r="1" />
                            <circle cx="12" cy="5" r="1" />
                        </svg>
                        {{ index + 1 }}
                    </div>
                </li>
                
            </draggable>
        </div>
        
        <ul class="bg-white  p-8 text-gray-700 rounded-r-lg rounded-bl-lg shadow flex-auto overflow-auto border border-gray-500 relative">
            <li v-for="record,index in data.records" :key="ids[index]" v-if="index == selectedIndex" class="w-full  flex flex-wrap text-lg relative">
                <div class="w-full ">
                    <slot :record="record" :index="index" :builder="builder" :data="data">No se ha proporcionado template</slot>
                </div>
            </li>
            <li class="absolute top-0 right-0" v-if="data && data.records && data.records.length">
                <button @click="root_builder_vue.removeRecord(selectedIndex)" class=" hover:text-black text-gray-500 bg-transparent px-3 py-3 flex justify-between items-center" @click="openModal(tablename,fieldname,recordnum,presavetempid,id)">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 flex-shrink-0 stroke-current icon icon-tabler icon-tabler-brand-facebook" width="44" height="44" viewBox="0 0 24 24" stroke-width="1.2"  fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                        <line x1="4" y1="7" x2="20" y2="7" />
                        <line x1="10" y1="11" x2="10" y2="17" />
                        <line x1="14" y1="11" x2="14" y2="17" />
                        <path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12" />
                        <path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3" />
                    </svg>
                </button>
            </li>
            <li v-if="!data || !data.records || !data.records.length">
                <p class="relative w-full h-32 flex-shrink-0 text-gray-500 cursor-pointer flex items-center justify-center bg-transparent">Haz clic en el icono izquierdo para añadir nuevos bloques</p>
            </li>
        </ul>
        
    </div>
    
</template>
<script>
    module.exports = {
        props:['data','builder','active','section_id','root_builder_vue'],
        watch:{
            'data.records':function(newVal,oldVal){
                this.generateNewRecordsKeys();
                if (this.selectedIndex > (newVal.length - 1)) this.selectedIndex = newVal.length - 1;
            }
        },
        data(){
            return{
                ids:{},
                selectedIndex:0
            }
        },
        mounted(){
            this.generateNewRecordsKeys()
        },
        methods:{
            generateNewRecordsKeys(){
                this.ids = {};
                for (const index in this.data.records){
                    this.ids[index] = this.makeid(5);
                }
            },
            makeid(length){
                var result           = '';
                var characters       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
                var charactersLength = characters.length;
                for ( var i = 0; i < length; i++ ) {
                  result += characters.charAt(Math.floor(Math.random() * charactersLength));
               }
               return result;
            }
        }
    };
</script>