<template>
    <div  v-if="typeof pagedata.leads !== 'undefined'">
        <div v-if="pagedata.leads" class="flex text-gray-600 items-center justify-between mt-8 px-4" :class="{'ml-12':pagedata.children && pagedata.children.length}">
            <h4>Leads : Solicitudes de información últimos 30 días</h4>  
            <a>Ver más</a>
        </div>
        <div v-if="pagedata.leads" class="relative cursor-pointer mt-4 p-4 bg-white rounded-lg shadow z-0 text-3xl" :class="{'ml-12':pagedata.children && pagedata.children.length}">
            <div class="flex flex-wrap sm:flex-no-wrap sm:justify-between sm:items-end">
                <div class="w-full text-center flex justify-between items-center">
                    <h2 class="text-center text-6xl w-32 m-0" :class="{'text-gray-400 border-gray-300':!pagedata.leads.score || pagedata.leads.score == 0,'text-red-500 border-red-300':pagedata.leads.score <= 60 && pagedata.leads > 0,'text-orange-500 border-orange-300':pagedata.leads.score > 60 && pagedata.leads <= 80,'text-green-500 border-green-300':pagedata.leads.score > 80}">{{pagedata.leads.score ? pagedata.leads.score : 0}}</h2>

                    <ul class="w-full p-0 text-2xl text-gray-500 flex flex-wrap lg:justify-between items-center">
                        <li class="w-full ml-4 text-left md:text-center mx-0 md:w-auto md:mx-8 md:my-2"><span class="text-green-400 w-12 text-center">Gran Canaria</span><br><span class="text-4xl">~</span></li>
                        <li class="w-full ml-4 text-left md:text-center mx-0 md:w-auto md:mx-8 md:my-2"><span class="text-green-400 w-12 text-center">Tenerife</span><br><span class="text-4xl">~</span></li>
                        <li class="w-full ml-4 text-left md:text-center mx-0 md:w-auto md:mx-8 md:my-2"><span class="text-green-400 w-12 text-center">Madrid</span><br><span class="text-4xl">~</span></li>
                        <li class="w-full ml-4 text-left md:text-center mx-0 md:w-auto md:mx-8 md:my-2"><span class="text-green-400 w-12 text-center">Málaga</span><br><span class="text-4xl">~</span></li>
                        <li class="w-full ml-4 text-left md:text-center mx-0 md:w-auto md:mx-8 md:my-2"><span class="text-green-400 w-12 text-center">Otros</span><br><span class="text-4xl">~</span></li>
                    </ul>

                </div> 
            </div>
        </div>
    </div>
</template>
<script>
    module.exports = {
        props:["dato","paginas"],
        data(){
            return{
                pagedata:{}
            }
        },
        created(){
            this.pagedata = this.dato;
            console.log("Componente Leads cargado...");
        },
        methods:{
        }
    }
</script>